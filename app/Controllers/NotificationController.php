<?php namespace App\Controllers;

use App\Libraries\FirebaseService;

class NotificationController extends BaseController
{
    public function send()
    {
        $firebase = new FirebaseService();

        $deviceToken = 'YOUR_DEVICE_FCM_TOKEN'; // 🔁 Replace with real token
        $title = 'Welcome to Seeb!';
        $body = 'Your booking has been confirmed.';

        try {
            $response = $firebase->sendNotification($deviceToken, $title, $body);
            echo '✅ Notification sent: ';
            print_r($response);
        } catch (\Exception $e) {
            echo '❌ Failed: ' . $e->getMessage();
        }
    }
}
