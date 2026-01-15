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

    // 🔹 Admin creates assignment requests (with partner rates calculated)
    public function createMultipleAssignmentRequests()
    {
        $data = $this->request->getJSON(true);

        if (!isset($data['assignments']) || !is_array($data['assignments'])) {
            return $this->failValidationErrors(['assignments' => 'Required array']);
        }

        $requestModel = new BookingAssignmentRequestModel();
        $assignmentModel = new BookingAssignmentModel();
        $bookingModel = new \App\Models\BookingsModel();
        $bookingServiceModel = new \App\Models\BookingServicesModel();
        $customerModel = new \App\Models\CustomerModel();
        $serviceModel = new \App\Models\ServiceModel();
        $partnerModel = new PartnerModel();
        $customerAddressModel = new \App\Models\AddressModel();

        $summary = [];

        foreach ($data['assignments'] as $item) {
            if (
                empty($item['booking_service_id']) ||
                empty($item['partner_ids']) ||
                !is_array($item['partner_ids'])
            ) {
                $summary[] = ['booking_service_id' => $item['booking_service_id'] ?? null, 'error' => 'Invalid structure'];
                continue;
            }

            $bookingServiceId = $item['booking_service_id'];
            $partnerIds = $item['partner_ids'];
            $helperCount = $item['helper_count'] ?? 1;
            $estimatedCompletionDate = $item['estimated_completion_date'] ?? null;

            // ========== FETCH BOOKING SERVICE ==========
            $bookingService = $bookingServiceModel->find($bookingServiceId);
            if (!$bookingService) {
                $summary[] = ['booking_service_id' => $bookingServiceId, 'error' => 'Booking service not found'];
                continue;
            }

            // ========== FETCH BOOKING & SERVICE FOR DETAILS ==========
            $booking = $bookingModel->find($bookingService['booking_id']);
            if (!$booking) {
                $summary[] = ['booking_service_id' => $bookingServiceId, 'error' => 'Booking not found'];
                continue;
            }

            $customer = $customerModel->find($booking['user_id']);
            $service = $serviceModel->find($bookingService['service_id']);

            // ========== FETCH CUSTOMER ADDRESS ==========
            $address = null;
            if (!empty($booking['address_id'])) {
                $address = $customerAddressModel->find($booking['address_id']);
            }

            if (!$service) {
                $summary[] = ['booking_service_id' => $bookingServiceId, 'error' => 'Service not found'];
                continue;
            }

            $serviceName = $service['name'] ?? '';
            $customerName = $customer['name'] ?? '';
            $slotDate = $booking['slot_date'] ?? null;
            $addressText = $address['address'] ?? null;

            // ========== CALCULATE PARTNER RATES ==========
            $rateType = $bookingService['rate_type'];
            $quantity = $bookingService['value'];

            // Parse quantity if dimensions
            if ($rateType === 'square_feet' && strpos($quantity, 'X') !== false) {
                [$w, $h] = explode('X', $quantity);
                $calculatedQuantity = floatval($w) * floatval($h);
            } else {
                $calculatedQuantity = floatval($quantity);
            }

            // Get rates
            $customerAmount = floatval($bookingService['amount']);
            $partnerRate = floatval($service['partner_price']);
            $partnerAmount = $calculatedQuantity * $partnerRate;
            $withMaterial = $service['with_material'];

            // ========== CHECK EXISTING REQUESTS ==========
            $existing = $requestModel
                ->where('booking_service_id', $bookingServiceId)
                ->whereIn('partner_id', $partnerIds)
                ->findAll();

            $existingPartnerIds = array_column($existing, 'partner_id');

            $assignedPartners = [];
            $skippedPartners = [];

            // ========== PROCESS EACH PARTNER ==========
            foreach ($partnerIds as $partnerId) {
                if (in_array($partnerId, $existingPartnerIds)) {
                    $skippedPartners[] = [
                        'partner_id' => $partnerId,
                        'reason'     => 'Request already exists'
                    ];
                    continue;
                }

                // ========== INSERT REQUEST (PENDING) ==========
                $requestModel->insert([
                    'booking_service_id' => $bookingServiceId,
                    'partner_id'         => $partnerId,
                    'status'             => 'pending',
                    'sent_at'            => date('Y-m-d H:i:s')
                ]);

                // ========== PUSH TO FIRESTORE FOR NOTIFICATION ==========
                $result = $this->pushToFirestore(
                    $bookingServiceId,
                    $partnerId,
                    $partnerAmount,  // Use calculated partner amount
                    $serviceName,
                    $customerName,
                    $slotDate,
                    $estimatedCompletionDate,
                    $addressText,  // Pass address data
                    $partnerRate,  // Partner rate
                    $rateType,  // Rate type (square_feet, unit, points)
                    $calculatedQuantity,  // Calculated quantity
                    $withMaterial  // Material included
                );

                if (!$result['success']) {
                    $skippedPartners[] = [
                        'partner_id' => $partnerId,
                        'reason'     => $result['reason'] ?? 'Firestore failed'
                    ];
                    continue;
                }

                // ========== SEND NOTIFICATION ==========
                $this->sendAssignmentNotification($partnerId, $bookingServiceId);
                $assignedPartners[] = $partnerId;
            }

            // ========== CREATE OR UPDATE UNCLAIMED ASSIGNMENT ==========
            $existingAssignment = $assignmentModel
                ->where('booking_service_id', $bookingServiceId)
                ->first();

            if (!$existingAssignment) {
                $assignmentModel->insert([
                    'booking_service_id'        => $bookingServiceId,
                    'partner_id'                => null,
                    'amount'                    => $partnerAmount,        // Partner amount
                    'rate'                      => $partnerRate,           // Partner rate
                    'rate_type'                 => $rateType,              // How calculated
                    'quantity'                  => $calculatedQuantity,    // Quantity used
                    'with_material'             => $withMaterial ? 1 : 0,  // Material included
                    'status'                    => 'unclaimed',
                    'assigned_at'               => date('Y-m-d H:i:s'),
                    'helper_count'              => $helperCount,
                    'estimated_start_date'      => $slotDate,
                    'estimated_completion_date' => $estimatedCompletionDate,
                    'created_at'                => date('Y-m-d H:i:s')
                ]);
            }

            $summary[] = [
                'booking_service_id'  => $bookingServiceId,
                'assigned_partners'   => $assignedPartners,
                'skipped_partners'    => $skippedPartners,
                'partner_details'     => [
                    'rate'              => $partnerRate,
                    'quantity'          => $calculatedQuantity,
                    'amount'            => round($partnerAmount, 2),
                    'with_material'     => $withMaterial ? 'Yes' : 'No',
                ]
            ];
        }

        return $this->respond([
            'status' => 200,
            'message' => 'Processed all assignment requests',
            'summary' => $summary
        ]);
    }


    // 🔹 Partner accepts job
    public function acceptAssignment()
    {
        $requestModel = new BookingAssignmentRequestModel();
        $assignmentModel = new BookingAssignmentModel();
        $bookingServiceModel = new \App\Models\BookingServicesModel();
        $serviceModel = new \App\Models\ServiceModel();

        $bookingServiceId = $this->request->getVar('booking_service_id');
        $partnerId = $this->request->getVar('partner_id');

        if (!$bookingServiceId || !$partnerId) {
            return $this->failValidationErrors([
                'booking_service_id' => 'Required',
                'partner_id' => 'Required'
            ]);
        }

        try {
            // ========== FETCH BOOKING SERVICE & SERVICE ==========
            $bookingService = $bookingServiceModel->find($bookingServiceId);
            if (!$bookingService) {
                return $this->failNotFound('Booking service not found');
            }

            $service = $serviceModel->find($bookingService['service_id']);
            if (!$service) {
                return $this->failNotFound('Service not found');
            }

            // ========== LOCK REQUEST AND EXPIRE OTHERS ==========
            $requestModel->claimFirst($bookingServiceId, $partnerId);

            // ========== FIND UNCLAIMED ASSIGNMENT ==========
            $assignment = $assignmentModel
                ->where('booking_service_id', $bookingServiceId)
                ->where('status', 'unclaimed')
                ->first();

            if (!$assignment) {
                return $this->failNotFound('No unclaimed assignment found');
            }

            // ========== UPDATE WITH PARTNER INFO AND RATES ==========
            $assignmentModel->update($assignment['id'], [
                'partner_id'     => $partnerId,
                'status'         => 'assigned',
                'accepted_at'    => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s')
            ]);

            // ========== UPDATE FIRESTORE ON CLAIM ==========
            $this->updateFirestoreOnClaim($bookingServiceId, $partnerId);

            // ========== RETURN SUCCESS WITH PARTNER RATE DETAILS ==========
            return $this->respond([
                'status'  => 200,
                'message' => 'Assignment accepted successfully',
                'data'    => [
                    'assignment_id' => $assignment['id'],
                    'partner_id'    => $partnerId,
                    'status'        => 'assigned',

                    'partner_earnings' => [
                        'rate'          => floatval($assignment['rate']),
                        'rate_type'     => $assignment['rate_type'],
                        'quantity'      => floatval($assignment['quantity']),
                        'amount'        => round(floatval($assignment['amount']), 2),
                        'with_material' => $assignment['with_material'] ? 'Yes' : 'No',
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Accept Assignment Error: ' . $e->getMessage());
            return $this->failServerError('Failed to accept assignment: ' . $e->getMessage());
        }
    }

    // 🔥 Firestore broadcast per partner (you can call Firebase PHP SDK or use REST API)
    private function pushToFirestore($bookingServiceId, $partnerId, $assignedAmount, $serviceName, $customerName, $slotDate, $estimatedCompletionDate, $address = null, $rate = null, $rateType = null, $quantity = null, $withMaterial = null)
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
        $firestore->pushAssignmentRequest($bookingServiceId, $firebaseUid, $partnerId, $assignedAmount, $serviceName, $customerName, $slotDate, $estimatedCompletionDate, $address, $rate, $rateType, $quantity, $withMaterial);

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
                ->select('
                    booking_assignments.*,
                    services.name AS service_name,
                    booking_services.amount AS customer_amount,
                    af_customers.name AS customer_name,
                    customer_addresses.address AS customer_address
                ')
                ->join('booking_services', 'booking_services.id = booking_assignments.booking_service_id')
                ->join('services', 'services.id = booking_services.service_id')
                ->join('bookings', 'bookings.id = booking_services.booking_id')
                ->join('af_customers', 'af_customers.id = bookings.user_id', 'left')
                ->join('customer_addresses', 'customer_addresses.id = bookings.address_id', 'left')
                ->where('booking_assignments.partner_id', $partnerId)
                ->where('booking_assignments.status', 'assigned')
                ->orderBy('booking_assignments.assigned_at', 'desc')
                ->findAll();

            if (empty($assignments)) {
                return $this->respond([
                    'status'  => 200,
                    'message' => 'No accepted bookings',
                    'data'    => []
                ]);
            }

            // Format response with rate comparison
            $bookings = [];
            foreach ($assignments as $a) {
                $bookings[] = [
                    'assignment_id'     => $a['id'],
                    'service_name'      => $a['service_name'],
                    'status'            => $a['status'],
                    'assigned_at'       => $a['assigned_at'],
                    'rate'              => floatval($a['rate']),
                    'rate_type'         => $a['rate_type'],
                    'quantity'          => floatval($a['quantity']),
                    'amount'            => round(floatval($a['amount']), 2),
                    'with_material'     => $a['with_material'] ? true : false,
                    'customer_name'     => $a['customer_name'],
                    'customer_address'  => $a['customer_address'],
                ];
            }

            return $this->respond([
                'status'  => 200,
                'message' => 'Accepted bookings retrieved',
                'data'    => $bookings
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error fetching accepted bookings: ' . $e->getMessage());
            return $this->failServerError('Failed to fetch accepted bookings: ' . $e->getMessage());
        }
    }

    /**
     * OLD METHOD: Get detailed assignment info with checklist, updates, etc.
     * Use this for comprehensive assignment details
     */
    public function getAssignmentDetailsWithChecklist($assignmentId)
    {
        $bookingServiceModel      = new \App\Models\BookingServicesModel();
        $bookingModel             = new \App\Models\BookingsModel();
        $customerModel            = new \App\Models\CustomerModel();
        $serviceModel             = new \App\Models\ServiceModel();
        $checklistStatusModel     = new \App\Models\BookingChecklistStatusModel();   // booking_assignment_checklist_status
        $serviceChecklistModel    = new \App\Models\ServiceChecklistModel();         // service_checklists
        $bookingUpdateModel       = new \App\Models\BookingUpdateModel();
        $bookingUpdateMediaModel  = new \App\Models\BookingUpdateMediaModel();
        $partnerPayoutModel       = new \App\Models\PartnerPayoutModel();            // optional
        $assignmentModel = new BookingAssignmentModel();
        try {
            // ----------------------
            // 1) Main assignment + heavy joins in one query
            // ----------------------
            // bookings.final_amount            AS booking_final_amount,
            // bookings.payment_status          AS booking_payment_status,
            // bookings.status                  AS booking_status,
            // customer_addresses.city          AS address_city,
            // customer_addresses.state         AS address_state,
            // customer_addresses.pincode       AS address_pincode
            $assignment = $assignmentModel
                ->asArray()
                ->select("
            booking_assignments.*,
            booking_services.id              AS booking_service_id,
            booking_services.service_id      AS service_id,
            booking_services.booking_id      AS booking_id,
            services.name                    AS service_name,
            bookings.slot_date               AS booking_slot_date,
            bookings.booking_id               AS booking_number,
            af_customers.id                     AS customer_id,
            af_customers.name                   AS customer_name,
            af_customers.mobile_no              AS customer_mobile,
            customer_addresses.house AS address_line1,
            customer_addresses.address AS address_line2,
            customer_addresses.landmark AS landmark,
            customer_addresses.address_label AS address_label,
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

    public function getRequestsByBookingServiceId($bookingServiceId)
    {
        if (!$bookingServiceId) {
            return $this->failValidationErrors([
                'booking_service_id' => 'Required'
            ]);
        }

        try {
            $db = \Config\Database::connect();

            $builder = $db->table('booking_assignment_requests r');
            $builder->select('r.id, r.booking_service_id, r.partner_id, r.status, r.created_at, p.name as partner_name');
            $builder->join('partners p', 'r.partner_id = p.id', 'left');
            $builder->where('r.booking_service_id', $bookingServiceId);
            $builder->orderBy('r.created_at', 'DESC');

            $results = $builder->get()->getResult();

            return $this->respond([
                'status'  => 200,
                'message' => 'Requests fetched successfully.',
                'data'    => $results
            ]);
        } catch (\Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    /**
     * STEP 1: Admin assigns booking service to partner
     * Calculates partner amount based on partner_price from service
     */
    public function assignToPartner()
    {
        try {
            $data = $this->request->getJSON(true);

            // ========== STEP 1: VALIDATE INPUT ==========
            if (empty($data['booking_service_id']) || empty($data['partner_id'])) {
                return $this->failValidationErrors([
                    'message' => 'booking_service_id and partner_id are required'
                ]);
            }

            // ========== STEP 2: FETCH BOOKING SERVICE (Customer Data) ==========
            $bookingServiceModel = new \App\Models\BookingServicesModel();
            $bookingService = $bookingServiceModel->find($data['booking_service_id']);

            if (!$bookingService) {
                return $this->failNotFound('Booking service not found');
            }

            // ========== STEP 3: FETCH SERVICE (Rates) ==========
            $serviceModel = new \App\Models\ServiceModel();
            $service = $serviceModel->find($bookingService['service_id']);

            if (!$service) {
                return $this->failNotFound('Service not found');
            }

            // ========== STEP 4: FETCH PARTNER ==========
            $partnerModel = new PartnerModel();
            $partner = $partnerModel->find($data['partner_id']);

            if (!$partner) {
                return $this->failNotFound('Partner not found');
            }

            // ========== STEP 5: CALCULATE PARTNER AMOUNT ==========
            // Get the rate_type and quantity from booking service
            $rateType = $bookingService['rate_type'];  // square_feet, unit, or points
            $quantity = $bookingService['value'];      // The quantity/area/points

            // Parse quantity if dimensions (e.g., "10X12")
            if ($rateType === 'square_feet' && strpos($quantity, 'X') !== false) {
                [$w, $h] = explode('X', $quantity);
                $calculatedQuantity = floatval($w) * floatval($h);
            } else {
                $calculatedQuantity = floatval($quantity);
            }

            // Get rates from service
            $partnerRate = floatval($service['partner_price']);      // What partner gets per unit
            $withMaterial = $service['with_material'];               // Material included?

            // Calculate partner amount (same calculation logic as customer)
            $partnerAmount = $calculatedQuantity * $partnerRate;

            // Customer amount (from booking service)
            $customerAmount = floatval($bookingService['amount']);

            // ========== STEP 6: CHECK IF ALREADY ASSIGNED ==========
            $assignmentModel = new BookingAssignmentModel();
            $existingAssignment = $assignmentModel
                ->where('booking_service_id', $data['booking_service_id'])
                ->first();

            if ($existingAssignment) {
                return $this->failValidationErrors([
                    'message' => 'This booking service is already assigned'
                ]);
            }

            // ========== STEP 7: PREPARE ASSIGNMENT DATA ==========
            $assignmentData = [
                'booking_service_id'  => $bookingService['id'],
                'partner_id'          => $partner['id'],
                'amount'              => $partnerAmount,           // What partner gets
                'rate'                => $partnerRate,             // Rate per sqft/unit
                'rate_type'           => $rateType,                // square_feet, unit, or points
                'quantity'            => $calculatedQuantity,      // Quantity used in calculation
                'with_material'       => $withMaterial ? 1 : 0,    // Material included?
                'helper_count'        => $data['helper_count'] ?? 1,
                'status'              => 'assigned',
                'assigned_at'         => date('Y-m-d H:i:s'),
                'estimated_start_date' => $data['estimated_start_date'] ?? null,
                'estimated_completion_date' => $data['estimated_completion_date'] ?? null,
                'admin_notes'         => $data['admin_notes'] ?? null,
            ];

            // ========== STEP 8: INSERT ASSIGNMENT ==========
            if (!$assignmentModel->insert($assignmentData)) {
                return $this->failValidationErrors([
                    'message' => 'Failed to create assignment',
                    'errors' => $assignmentModel->errors()
                ]);
            }

            $assignmentId = $assignmentModel->insertID();

            // ========== STEP 9: RETURN RESPONSE WITH CLEAR RATE COMPARISON ==========
            return $this->respondCreated([
                'status'  => 201,
                'message' => 'Booking assigned to partner successfully',
                'data'    => [
                    'assignment_id'  => $assignmentId,
                    'partner_name'   => $partner['name'],
                    'service_name'   => $service['name'],

                    // WHAT CUSTOMER PAID
                    'customer' => [
                        'rate'          => floatval($service['rate']),
                        'rate_type'     => $rateType,
                        'quantity'      => $calculatedQuantity,
                        'amount'        => round($customerAmount, 2),
                    ],

                    // WHAT PARTNER GETS
                    'partner' => [
                        'rate'          => $partnerRate,
                        'rate_type'     => $rateType,
                        'quantity'      => $calculatedQuantity,
                        'amount'        => round($partnerAmount, 2),
                    ],

                    // MATERIAL INFO
                    'material' => [
                        'with_material' => $withMaterial ? 'Yes' : 'No',
                    ],

                    // PROFIT CALCULATION
                    'profit' => [
                        'rate_difference'    => round(floatval($service['rate']) - $partnerRate, 2),
                        'total_profit'       => round($customerAmount - $partnerAmount, 2),
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Assignment Error: ' . $e->getMessage());
            return $this->failServerError('Failed to assign booking: ' . $e->getMessage());
        }
    }

    /**
     * STEP 2: Get assignment details with clear rate breakdown
     */
    public function getAssignmentDetails($assignmentId)
    {
        $assignmentModel = new BookingAssignmentModel();

        try {
            $assignment = $assignmentModel
                ->select('
                    booking_assignments.*,
                    services.name AS service_name,
                    partners.name AS partner_name,
                    af_customers.id AS customer_id,
                    af_customers.name AS customer_name,
                    af_customers.mobile_no AS customer_mobile,
                    customer_addresses.address AS customer_address
                ')
                ->join('booking_services', 'booking_services.id = booking_assignments.booking_service_id')
                ->join('services', 'services.id = booking_services.service_id')
                ->join('bookings', 'bookings.id = booking_services.booking_id')
                ->join('af_customers', 'af_customers.id = bookings.user_id', 'left')
                ->join('customer_addresses', 'customer_addresses.id = bookings.address_id', 'left')
                ->join('partners', 'partners.id = booking_assignments.partner_id', 'left')
                ->where('booking_assignments.id', $assignmentId)
                ->first();

            if (!$assignment) {
                return $this->failNotFound('Assignment not found');
            }

            // Calculate values
            $partnerRate = floatval($assignment['rate']);
            $partnerAmount = floatval($assignment['amount']);
            $quantity = floatval($assignment['quantity']);

            // ========== RETURN RESPONSE ==========
            return $this->respond([
                'status'  => 200,
                'message' => 'Assignment details',
                'data'    => [
                    'id'   => $assignment['id'],
                    'partner_name'    => $assignment['partner_name'],
                    'service_name'    => $assignment['service_name'],
                    'status'          => $assignment['status'],
                    'rate_type'       => $assignment['rate_type'],
                    'quantity'        => $quantity,
                    'with_material'   => $assignment['with_material'] ? true : false,
                    'customer'        => [
                        'id'      => $assignment['customer_id'],
                        'name'    => $assignment['customer_name'],
                        'mobile'  => $assignment['customer_mobile'],
                        'address' => $assignment['customer_address'],
                    ],
                    'rate'    => $partnerRate,
                    'amount'  => round($partnerAmount, 2),
                ]
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error fetching assignment: ' . $e->getMessage());
            return $this->failServerError('Failed to fetch assignment: ' . $e->getMessage());
        }
    }

    /**
     * STEP 3: Get all assignments for a booking with summary
     */
    public function getBookingAssignments($bookingId)
    {
        try {
            $db = \Config\Database::connect();

            // ========== STEP 1: FETCH ALL ASSIGNMENTS FOR BOOKING ==========
            $assignments = $db->table('booking_assignments ba')
                ->select('
                    ba.*,
                    p.name as partner_name,
                    s.name as service_name,
                    s.rate as customer_rate,
                    bs.amount as customer_amount
                ')
                ->join('partners p', 'p.id = ba.partner_id')
                ->join('booking_services bs', 'bs.id = ba.booking_service_id')
                ->join('services s', 's.id = bs.service_id')
                ->join('bookings b', 'b.id = bs.booking_id')
                ->where('b.id', $bookingId)
                ->get()
                ->getResultArray();

            if (empty($assignments)) {
                return $this->respond([
                    'status'  => 200,
                    'message' => 'No assignments for this booking',
                    'data'    => []
                ]);
            }

            // ========== STEP 2: BUILD ASSIGNMENT DETAILS ==========
            $assignmentDetails = [];
            $totalCustomer = 0;
            $totalPartner = 0;

            foreach ($assignments as $a) {
                $customerAmt = floatval($a['customer_amount']);
                $partnerAmt = floatval($a['amount']);
                $quantity = floatval($a['quantity']);

                $totalCustomer += $customerAmt;
                $totalPartner += $partnerAmt;

                $assignmentDetails[] = [
                    'assignment_id'  => $a['id'],
                    'partner_name'   => $a['partner_name'],
                    'service_name'   => $a['service_name'],
                    'rate_type'      => $a['rate_type'],
                    'with_material'  => $a['with_material'] ? true : false,
                    'rate_per_unit' => floatval($a['rate']),
                    'quantity'      => $quantity,
                    'amount'        => round($partnerAmt, 2),
                ];
            }

            // ========== STEP 3: RETURN WITH SUMMARY ==========
            return $this->respond([
                'status'  => 200,
                'message' => 'Booking assignments retrieved',
                'data'    => [
                    'assignments' => $assignmentDetails,
                ]
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error fetching booking assignments: ' . $e->getMessage());
            return $this->failServerError($e->getMessage());
        }
    }
}
