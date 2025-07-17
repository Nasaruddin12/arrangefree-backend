<?php

namespace App\Controllers;

use App\Libraries\FirebaseService;
use App\Models\CustomerModel;
use App\Models\NotificationModel;
use App\Services\NotificationService;
use CodeIgniter\API\ResponseTrait;

class NotificationController extends BaseController
{

    use ResponseTrait;
    public function send()
    {
        $firebase = new FirebaseService();

        // $deviceToken = 'dt2gBsM1R1Cp9QgIUlx_0B:APA91bEh7Tj6J_56lfsoFSTiWgrDfYZabWdXgTsesUGhQDrjceuGxRA6qTQxni_wlVBebmgYC7TDYotVJuTOEnlqpZpB_FzhSgMJOyic78Ofdxc1nj8De9A'; // 🔁 Replace with real token
        // $deviceToken = 'eKSGfQNwukYzhnvp9SeEl7:APA91bEqlJXxTIrE-KfvJ_o2ET_Dt-yhLdLGwTG5Twv2YPWyZ4twEXmqRe5AIYtSA6adFA6h6HcE85YOeZGlXzcwkMN441xnVwanaXiwJAh9d-jBqdbHcFY'; // 🔁 Replace with real token
        $deviceToken = 'dxu9ZzK4akjniCwypNrcgS:APA91bEGhQkJjbIzGGVkmleZxBAk7vkYcA6pvyIJH868h7aTGl20gXw-cb3jf9ffngL8yS1uqH2v8K-ib1V9JrJjGK1LZbnBGVkJBMjl3Jx33kWEZGo-1aE'; // 🔁 Replace with real token
        $title = 'Welcome to Seeb!';
        $body = 'Your booking has been confirmed.';

        try {
            $responses = $firebase->sendNotification($deviceToken, $title, $body, 'TicketChat', 3, $userId = 3, 'partner');

            $success = 0;
            $failed  = 0;

            foreach ($responses as $res) {
                if ($res['status'] === 200) {
                    $success++;
                } else {
                    $failed++;
                }
            }

            return $this->respond([
                'status' => 200,
                'message' => "Sent: $success, Failed: $failed",
                'results' => $responses
            ]);
        } catch (\Exception $e) {
            return $this->respond([
                'status'  => 500,
                'message' => 'Failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    protected $notificationModel;

    public function __construct()
    {
        $this->notificationModel = new NotificationModel();
    }

    // 🔹 GET /notifications?user_id=12&user_type=partner
    public function index()
    {
        // 🔁 Auto-delete notifications older than 1 month
        // $this->notificationModel
        //     ->where('created_at <', date('Y-m-d H:i:s', strtotime('-1 month')))
        //     ->delete();

        $userId = $this->request->getVar('user_id');
        $userType = $this->request->getVar('user_type');

        if (!$userId || !$userType) {
            return $this->failValidationErrors("user_id and user_type are required");
        }

        $notifications = $this->notificationModel
            ->where('user_id', $userId)
            ->where('user_type', $userType)
            ->orderBy('created_at', 'DESC')
            ->findAll();

        $unreadCount = $this->notificationModel
            ->where('user_id', $userId)
            ->where('user_type', $userType)
            ->where('is_read', false)
            ->countAllResults();

        return $this->respond([
            'status' => true,
            'unread_count' => $unreadCount,
            'notifications' => $notifications
        ]);
    }

    // 🔹 POST /notifications/create
    public function create()
    {
        try {
            $data = $this->request->getJSON(true);

            // ✅ Validation rules
            $rules = [
                'user_id'   => 'required|integer',
                'user_type' => 'required|in_list[customer,partner,admin]',
                'title'     => 'required|string',
                'message'   => 'required|string',
                'type'      => 'required|string',
            ];

            $validation = \Config\Services::validation();
            if (!$this->validateData($data, $rules)) {
                return $this->failValidationErrors($validation->getErrors());
            }

            // ✅ Send and save notification
            $notificationService = new NotificationService();
            $result = $notificationService->notifyUser($data);

            if ($result['status']) {
                return $this->respond([
                    'status' => 200,
                    'message' => 'Notification sent and saved',
                    'fcm_result' => $result['fcm'] ?? null
                ], 200);
            } else {
                return $this->respond([
                    'status' => false,
                    'message' => $result['message'],
                    'error'   => $result['missing'] ?? null
                ], 400);
            }
        } catch (\Throwable $e) {
            log_message('error', 'NotificationController::create error - ' . $e->getMessage());

            return $this->respond([
                'status' => false,
                'message' => 'Something went wrong while sending notification.',
                'error' =>  $e->getMessage()
            ], 500);
        }
    }

    // 🔹 POST /notifications/mark-as-read
    public function markAsRead()
    {
        $id = $this->request->getVar('id');

        if (!$id) return $this->failValidationErrors("Notification ID is required");

        $this->notificationModel->update($id, ['is_read' => true]);

        return $this->respond(['status' => true, 'message' => 'Marked as read']);
    }

    // 🔹 POST /notifications/mark-all-read
    public function markAllAsRead()
    {
        $userId = $this->request->getVar('user_id');
        $userType = $this->request->getVar('user_type');

        if (!$userId || !$userType) {
            return $this->failValidationErrors("user_id and user_type are required");
        }

        $this->notificationModel
            ->where('user_id', $userId)
            ->where('user_type', $userType)
            ->where('is_read', false)
            ->set(['is_read' => true])
            ->update();

        return $this->respond(['status' => true, 'message' => 'All notifications marked as read']);
    }

    // 🔹 DELETE /notifications/delete?id=123
    public function delete($id = null)
    {
        if (!$id) return $this->failValidationErrors("Notification ID is required");

        $this->notificationModel->delete($id);

        return $this->respond(['status' => true, 'message' => 'Notification deleted']);
    }
    public function clearAll()
    {
        $userId = $this->request->getPost('user_id');
        $userType = $this->request->getPost('user_type');

        if (!$userId || !$userType) {
            return $this->failValidationErrors("user_id and user_type are required");
        }

        $this->notificationModel
            ->where('user_id', $userId)
            ->where('user_type', $userType)
            ->delete();

        return $this->respond([
            'status' => 200,
            'message' => 'All notifications cleared for this user'
        ]);
    }
}
