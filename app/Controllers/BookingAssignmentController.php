<?php

namespace App\Controllers;

use App\Libraries\FirebaseService;
use App\Libraries\FirestoreService;
use App\Models\BookingAssignmentModel;
use App\Models\BookingAssignmentRequestModel;
use App\Models\PartnerModel;
use App\Services\NotificationService;
use CodeIgniter\RESTful\ResourceController;

class BookingAssignmentController extends ResourceController
{
    protected $format = 'json';

    // 🔹 Admin creates assignment requests
    public function createAssignmentRequests()
    {
        // Define validation rules
        $rules = [
            'booking_service_id'         => 'required|integer',
            'partner_ids'                => 'required|is_array',
            'partner_ids.*'              => 'required|integer',
            'amount'                     => 'permit_empty|decimal',
            'helper_count'               => 'permit_empty|integer',
            'estimated_completion_date'  => 'permit_empty|valid_date' // Format: Y-m-d or Y-m-d H:i:s
        ];
        // Validate request
        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        // Extract validated data
        $bookingServiceId = $this->request->getVar('booking_service_id');
        $partnerIds       = $this->request->getVar('partner_ids');
        $assignedAmount   = $this->request->getVar('amount');
        $helperCount     = $this->request->getVar('helper_count') ?? 0;
        $estimatedCompletionDate = $this->request->getVar('estimated_completion_date') ?? null;

        $requestModel     = new BookingAssignmentRequestModel();
        $assignmentModel  = new BookingAssignmentModel();
        $bookingModel     = new \App\Models\BookingsModel();
        $bookingServiceModel = new \App\Models\BookingServicesModel();
        $customerModel    = new \App\Models\CustomerModel();
        $serviceModel     = new \App\Models\ServiceModel();

        $bookingService = $bookingServiceModel->find($bookingServiceId);
        if (!$bookingService) return $this->failNotFound('Booking service not found');

        $booking = $bookingModel->find($bookingService['booking_id']);
        if (!$booking) return $this->failNotFound('Booking not found');

        $customer = $customerModel->find($booking['user_id']);
        $service = $serviceModel->find($bookingService['service_id']);

        $serviceName   = $service['name'] ?? '';
        $customerName  = $customer['name'] ?? '';
        $slotDate      = $booking['slot_date'] ?? null;

        // Skip already assigned partners
        $existing = $requestModel
            ->where('booking_service_id', $bookingServiceId)
            ->whereIn('partner_id', $partnerIds)
            ->findAll();

        $existingPartnerIds = array_column($existing, 'partner_id');

        $assignedPartners = [];
        $skipped = [];
        $notificationsResult = [];

        try {
            foreach ($partnerIds as $partnerId) {
                if (in_array($partnerId, $existingPartnerIds)) continue;

                // Insert assignment request
                $requestModel->insert([
                    'booking_service_id' => $bookingServiceId,
                    'partner_id'         => $partnerId,
                    'status'             => 'pending',
                    'sent_at'            => date('Y-m-d H:i:s')
                ]);

                $result = $this->pushToFirestore(
                    $bookingServiceId,
                    $partnerId,
                    $assignedAmount,
                    $serviceName,
                    $customerName,
                    $slotDate,
                    $estimatedCompletionDate
                );

                if (!$result['success']) {
                    $skipped[] = [
                        'partner_id' => $partnerId,
                        'reason'     => $result['reason'] ?? 'Firestore push failed'
                    ];
                    continue;
                }

                $res = $this->sendAssignmentNotification($partnerId, $bookingServiceId);
                if ($res) $notificationsResult[] = $res;

                $assignedPartners[] = $partnerId;
            }


            $existingAssignment = $assignmentModel
                ->where('booking_service_id', $bookingServiceId)
                ->first();

            if (!$existingAssignment) {
                $assignmentModel->insert([
                    'booking_service_id' => $bookingServiceId,
                    'partner_id'         => null,
                    'status'             => 'unclaimed',
                    'assigned_amount'    => $assignedAmount ?? 0,
                    'assigned_at'       => date('Y-m-d H:i:s'),
                    'helper_count'      => $helperCount,
                    'estimated_start_date' => $slotDate ?? null,
                    'estimated_completion_date' => $estimatedCompletionDate ?? null,
                    'created_at'         => date('Y-m-d H:i:s')
                ]);
            }

            return $this->respond([
                'status' => 200,
                'message' => 'Assignment requests created',
                'data' => [
                    'booking_service_id' => $bookingServiceId,
                    'assigned_partners' => array_filter($assignedPartners ?? []),
                    'skipped_partners' => $skipped ?? []
                ],
                'notification_response' => $notificationsResult ?? []
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Assignment creation failed: ' . $e->getMessage());
            return $this->fail(['status' => 500, 'message' => $e->getMessage()]);
        }
    }


    // 🔹 Partner accepts job
    public function acceptAssignment()
    {
        $requestModel = new BookingAssignmentRequestModel();
        $assignmentModel = new BookingAssignmentModel();
        $bookingServiceId = $this->request->getVar('booking_service_id');
        $partnerId = $this->request->getVar('partner_id');

        if (!$bookingServiceId || !$partnerId) {
            return $this->failValidationErrors([
                'booking_service_id' => 'Required',
                'partner_id' => 'Required'
            ]);
        }


        try {
            // Lock request and expire others
            $requestModel->claimFirst($bookingServiceId, $partnerId);

            // Create final assignment record
            // Find unclaimed assignment
            $assignment = $assignmentModel
                ->where('booking_service_id', $bookingServiceId)
                ->where('status', 'unclaimed')
                ->first();

            if (!$assignment) {
                return $this->failNotFound('No unclaimed assignment found');
            }

            // Update it with partner info
            $assignmentModel->update($assignment['id'], [
                'partner_id'     => $partnerId,
                'status'         => 'assigned',
                'accepted_at'    => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s')
            ]);

            // 🔥 Optional: notify Firestore
            $this->updateFirestoreOnClaim($bookingServiceId, $partnerId);

            return $this->respond(['status' => 200, 'message' => 'Assignment accepted']);
        } catch (\Exception $e) {

            return $this->fail(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    // 🔥 Firestore broadcast per partner (you can call Firebase PHP SDK or use REST API)
    private function pushToFirestore($bookingServiceId, $partnerId, $assignedAmount, $serviceName, $customerName, $slotDate, $estimatedCompletionDate)
    {
        $partner = (new \App\Models\PartnerModel())->find($partnerId);
        if (!$partner || empty($partner['firebase_uid'])) {
            log_message('error', "❌ No firebase_uid found for partner ID: $partnerId");

            // return a flag indicating failure
            return [
                'success' => 500,
                'partner_id' => $partnerId,
                'name' => $partner['name'] ?? '',
                'reason' => 'Missing firebase_uid'
            ];
        }

        $firebaseUid = $partner['firebase_uid'];
        $firestore = new FirestoreService();
        $firestore->pushAssignmentRequest($bookingServiceId, $firebaseUid, $partnerId, $assignedAmount, $serviceName, $customerName, $slotDate, $estimatedCompletionDate);

        return ['success' => 200, 'partner_id' => $partnerId];
    }

    private function updateFirestoreOnClaim($bookingServiceId, $acceptedPartnerId)
    {
        $requestModel = new BookingAssignmentRequestModel();
        $firestore = new FirestoreService();
        $partnerModel = new PartnerModel();

        $acceptedPartner = $partnerModel->find($acceptedPartnerId);
        if (!$acceptedPartner || empty($acceptedPartner['firebase_uid'])) {
            log_message('error', "Missing firebase_uid for accepted partner ID: $acceptedPartnerId");
            return [
                'success' => 500,
                'partner_id' => $acceptedPartnerId,
                'name' => $acceptedPartner['name'] ?? '',
                'reason' => 'Missing firebase_uid'
            ];
        }

        // Accept
        $firestore->updateStatus($bookingServiceId, $acceptedPartner['firebase_uid'], 'accepted');

        // Expire others
        $requests = $requestModel->getActiveRequests($bookingServiceId);
        foreach ($requests as $r) {
            if ($r['partner_id'] != $acceptedPartnerId) {
                $otherPartner = $partnerModel->find($r['partner_id']);
                if ($otherPartner && !empty($otherPartner['firebase_uid'])) {
                    $firestore->updateStatus($bookingServiceId, $otherPartner['firebase_uid'], 'expired');
                }
            }
        }
    }

    public function getAcceptedBookings($partnerId)
    {
        $assignmentModel = new \App\Models\BookingAssignmentModel();

        try {
            $assignments = $assignmentModel
                ->select('booking_assignments.*, services.name AS service_name')
                ->join('booking_services', 'booking_services.id = booking_assignments.booking_service_id')
                ->join('services', 'services.id = booking_services.service_id')
                ->where('booking_assignments.partner_id', $partnerId)
                ->where('booking_assignments.status', 'assigned')
                ->orderBy('booking_assignments.assigned_at', 'desc')
                ->findAll();

            return $this->respond([
                'status' => 200,
                'data' => $assignments
            ]);
        } catch (\Exception $e) {
            return $this->fail([
                'status' => 500,
                'message' => 'Failed to fetch accepted bookings',
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function getAssignmentDetails($assignmentId)
    {
        $assignmentModel          = new \App\Models\BookingAssignmentModel();
        $bookingServiceModel      = new \App\Models\BookingServicesModel();
        $bookingModel             = new \App\Models\BookingsModel();
        $customerModel            = new \App\Models\CustomerModel();
        $serviceModel             = new \App\Models\ServiceModel();
        $checklistStatusModel     = new \App\Models\BookingChecklistStatusModel();   // booking_assignment_checklist_status
        $serviceChecklistModel    = new \App\Models\ServiceChecklistModel();         // service_checklists
        $bookingUpdateModel       = new \App\Models\BookingUpdateModel();
        $bookingUpdateMediaModel  = new \App\Models\BookingUpdateMediaModel();
        $partnerPayoutModel       = new \App\Models\PartnerPayoutModel();            // optional
        
        try {
            // ----------------------
            // 1) Main assignment + heavy joins in one query
            // ----------------------
            // bookings.final_amount            AS booking_final_amount,
            // bookings.payment_status          AS booking_payment_status,
            // bookings.status                  AS booking_status,
            // customer_addresses.city          AS address_city,
            // customer_addresses.state         AS address_state,
            $assignment = $assignmentModel
            ->asArray()
            ->select("
            booking_assignments.*,
            booking_services.id              AS booking_service_id,
            booking_services.service_id      AS service_id,
            booking_services.booking_id      AS booking_id,
            services.name                    AS service_name,
            bookings.slot_date               AS booking_slot_date,
            af_customers.id                     AS customer_id,
            af_customers.name                   AS customer_name,
            af_customers.mobile_no              AS customer_mobile,
            customer_addresses.house AS address_line1,
            customer_addresses.address AS address_line2,
            customer_addresses.landmark AS landmark,
            customer_addresses.address_label AS address_label,
            customer_addresses.pincode       AS address_pincode
            ")
            ->join('booking_services', 'booking_services.id = booking_assignments.booking_service_id')
            ->join('services', 'services.id = booking_services.service_id')
            ->join('bookings', 'bookings.id = booking_services.booking_id')
            ->join('af_customers', 'af_customers.id = bookings.user_id')
            ->join('customer_addresses', 'customer_addresses.id = bookings.address_id', 'left')
            ->where('booking_assignments.id', $assignmentId)
            ->first();
            
            if (!$assignment) {
                return $this->failNotFound('Assignment not found');
            }

            $bookingServiceId = (int) $assignment['booking_service_id'];

            // ----------------------
            // 2) Checklist status + master checklist
            // ----------------------
            $checklistStatuses = $checklistStatusModel
                ->asArray()
                ->select('booking_assignment_checklist_status.*, service_checklists.title, service_checklists.is_required')
                ->join('service_checklists', 'service_checklists.id = booking_assignment_checklist_status.checklist_id')
                ->where('booking_assignment_checklist_status.booking_service_id', $bookingServiceId)
                ->orderBy('service_checklists.sort_order', 'asc')
                ->findAll();

            // (Optional) overall completion %
            $totalItems     = count($checklistStatuses);
            $doneItems      = array_reduce($checklistStatuses, fn($c, $r) => $c + (int)$r['is_done'], 0);
            $completionPerc = $totalItems > 0 ? round(($doneItems / $totalItems) * 100, 2) : 0;

            // ----------------------
            // 3) Booking updates + media
            // ----------------------
            $updates = $bookingUpdateModel
                ->where('booking_service_id', $bookingServiceId)
                ->orderBy('created_at', 'asc')
                ->findAll();

            if (!empty($updates)) {
                $updateIds = array_column($updates, 'id');
                $medias = [];
                if (!empty($updateIds)) {
                    $medias = $bookingUpdateMediaModel
                        ->whereIn('booking_update_id', $updateIds)
                        ->findAll();
                }
                // Attach media to updates
                $mediaMap = [];
                foreach ($medias as $m) {
                    $mediaMap[$m['booking_update_id']][] = $m;
                }
                foreach ($updates as &$u) {
                    $u['media'] = $mediaMap[$u['id']] ?? [];
                }
            }

            // ----------------------
            // 4) Optional: payout info for this assignment
            // ----------------------
            $payouts = $partnerPayoutModel
                ->where('booking_service_id', $bookingServiceId)
                ->where('partner_id', $assignment['partner_id'] ?? null)
                ->orderBy('created_at', 'desc')
                ->findAll();

            // ----------------------
            // 5) Final response
            // ----------------------
            return $this->respond([
                'status' => 200,
                'data' => [
                    'assignment' => $assignment,
                    'checklist'  => [
                        'items'            => $checklistStatuses,
                        'total_items'      => $totalItems,
                        'completed_items'  => $doneItems,
                        'completion_pct'   => $completionPerc
                    ],
                    'updates'   => $updates,
                    'payouts'   => $payouts
                ]
            ]);
        } catch (\Throwable $e) {
            return $this->fail([
                'status'  => 500,
                'message' => 'Failed to fetch assignment details',
                'error'   => $e->getMessage()
            ]);
        }
    }



    private function sendAssignmentNotification($partnerId, $bookingServiceId)
    {
        $partner = (new \App\Models\PartnerModel())->find($partnerId);
        if (empty($partner['fcm_token'])) return null;

        $notificationService = new NotificationService();
        return $notificationService->notifyUser([
            'user_id'           => $partner['id'],
            'user_type'         => 'partner',
            'title'             => "New Booking Assigned!",
            'message'           => "You've received a new booking. Accept it to earn more.",
            'type'              => 'booking_assignment',
            'navigation_screen' => 'booking_assignment',
            'navigation_id'     => $bookingServiceId
        ]);
    }
}
