<?php

namespace App\Services;

use App\Libraries\FirebaseService;
use App\Models\NotificationModel;

class NotificationService
{
    protected $firebase;
    protected $notificationModel;
    protected $db;

    public function __construct()
    {
        $this->firebase = new FirebaseService();
        $this->notificationModel = new NotificationModel();
        $this->db = db_connect();
    }

    /**
     * Send FCM push notification and save to database
     *
     * @param array{
     *     user_id: int,
     *     user_type: "customer"|"partner"|"admin",
     *     title: string,
     *     message: string,
     *     type: string,
     *     navigation_screen?: string,
     *     navigation_id?: int,
     *     image?: string
     * } $data
     * @return array{status: bool, message: string, fcm?: mixed, missing?: array}
     */
    public function notifyUser(array $data): array
    {
        $required = ['user_id', 'user_type', 'title', 'message', 'type'];
        $missing = array_filter($required, fn($key) => empty($data[$key]));

        if (!empty($missing)) {
            return [
                'status' => false,
                'message' => 'Missing fields: ' . implode(', ', $missing),
                'missing' => $missing
            ];
        }

        $userId = $data['user_id'];
        $userType = $data['user_type'];

        $token = $this->getDeviceToken($userId, $userType);
        if (!$token) {
            return [
                'status' => false,
                'message' => 'Device token not found for user',
            ];
        }

        $unreadCount = $this->getUnreadCount($userId, $userType) ?? 1;

        try {
            $fcm = $this->firebase->sendNotification(
                $token,
                $data['title'],
                $data['message'],
                $data['navigation_screen'] ?? 'Home',
                $data['navigation_id'] ?? null,
                $unreadCount,
                $data['image'] ?? null
            );

            $this->notificationModel->insert($data);

            return [
                'status' => true,
                'message' => 'Notification sent and saved',
                'fcm' => $fcm
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Push failed: ' . $e->getMessage()
            ];
        }
    }

    private function getDeviceToken($userId, $userType): ?string
    {
        $tableMap = [
            'customer' => 'customers',
            'partner'  => 'partners',
            'admin'    => 'admins',
        ];

        if (!isset($tableMap[$userType])) return null;

        return $this->db->table($tableMap[$userType])
            ->select('fcm_token')
            ->where('id', $userId)
            ->get()
            ->getRow('fcm_token');
    }

    private function getUnreadCount($userId, $userType): int
    {
        return $this->notificationModel
            ->where('user_id', $userId)
            ->where('user_type', $userType)
            ->where('is_read', false)
            ->countAllResults();
    }
}
