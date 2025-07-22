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
            'booking_service_id' => 'required|integer',
            'partner_ids'        => 'required|is_array',
            'partner_ids.*'      => 'required|integer',
            'amount'             => 'permit_empty|decimal'
        ];

        // Validate request
        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        // Extract validated data
        $bookingServiceId = $this->request->getVar('booking_service_id');
        $partnerIds       = $this->request->getVar('partner_ids');
        $assignedAmount   = $this->request->getVar('amount');

        $requestModel = new BookingAssignmentRequestModel();
        $partnerModel = new \App\Models\PartnerModel();
        $notificationService = new NotificationService();

        // Skip already assigned partners
        $existing = $requestModel
            ->where('booking_service_id', $bookingServiceId)
            ->whereIn('partner_id', $partnerIds)
            ->findAll();

        $existingPartnerIds = array_column($existing, 'partner_id');

        try {
            foreach ($partnerIds as $partnerId) {
                // if (in_array($partnerId, $existingPartnerIds)) continue;

                $requestModel->insert([
                    'booking_service_id' => $bookingServiceId,
                    'partner_id'         => $partnerId,
                    'status'             => 'pending',
                    'sent_at'            => date('Y-m-d H:i:s')
                ]);

                $result = $this->pushToFirestore($bookingServiceId, $partnerId, $assignedAmount);
                if (!$result['success']) {
                    $skipped[] = $result;
                }
                // ✅ Send notification
                $partner = $partnerModel->find($partnerId);
                if (!empty($partner['fcm_token'])) {
                    $title = "New Booking Request";
                    $body = "You've received a new assignment request.";
                    $res = $notificationService->notifyUser([
                        'user_id' => $partnerId,
                        'user_type' => 'partner',
                        'title' => $title,
                        'message' => $body,
                        'type' => 'booking_assignment',
                        'navigation_screen' => 'booking_assignment',
                        'navigation_id' => $bookingServiceId
                    ]);
                    $notificationsResult[] = $res;
                }

                $assignedPartners[] = $partnerId;
            }

            return $this->respond([
                'status' => true,
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
            return $this->fail(['status' => false, 'message' => $e->getMessage()]);
        }
    }


    // 🔹 Partner accepts job
    public function acceptAssignment()
    {
        $requestModel = new BookingAssignmentRequestModel();
        $assignmentModel = new BookingAssignmentModel();
        $bookingServiceId = $this->request->getVar('booking_service_id');
        $partnerId = $this->request->getVar('partner_id');

        try {
            // Lock request and expire others
            $requestModel->claimFirst($bookingServiceId, $partnerId);

            // Create final assignment record
            $assignmentModel->insert([
                'booking_service_id' => $bookingServiceId,
                'partner_id' => $partnerId,
                'assigned_amount' => 0, // update later via admin
                'status' => 'assigned',
                'assigned_at' => date('Y-m-d H:i:s')
            ]);

            $partnerModel = new \App\Models\PartnerModel();
            $partner = $partnerModel->find($partnerId);
            if (!empty($partner['fcm_token'])) {
                $title = "New Assignment";
                $body = "You’ve been assigned a new booking request.";
                $fcmToken = $partner['fcm_token'];

                // ✅ Call sendNotification
                $notificationService = new NotificationService();
                $notificationService->notifyUser([
                    'user_id' => $partnerId,
                    'user_type' => 'partner',
                    'title' => $title,
                    'message' => $body,
                    'type' => 'booking_assignment',
                    'navigation_screen' => 'booking_assignment',
                    'navigation_id' => $bookingServiceId
                ]);
            }

            // 🔥 Optional: notify Firestore
            $this->updateFirestoreOnClaim($bookingServiceId, $partnerId);

            return $this->respond(['status' => true, 'message' => 'Assignment accepted']);
        } catch (\Exception $e) {

            return $this->fail(['status' => false, 'message' => $e->getMessage()]);
        }
    }

    // 🔥 Firestore broadcast per partner (you can call Firebase PHP SDK or use REST API)
    private function pushToFirestore($bookingServiceId, $partnerId, $assignedAmount)
    {
        $partner = (new \App\Models\PartnerModel())->find($partnerId);
        if (!$partner || empty($partner['firebase_uid'])) {
            log_message('error', "❌ No firebase_uid found for partner ID: $partnerId");

            // return a flag indicating failure
            return [
                'success' => false,
                'partner_id' => $partnerId,
                'name' => $partner['name'] ?? '',
                'reason' => 'Missing firebase_uid'
            ];
        }

        $firebaseUid = $partner['firebase_uid'];
        $firestore = new FirestoreService();
        $firestore->pushAssignmentRequest($bookingServiceId, $firebaseUid, $partnerId, $assignedAmount);

        return ['success' => true, 'partner_id' => $partnerId];
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
                'success' => false,
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
}
