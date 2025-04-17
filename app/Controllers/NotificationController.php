<?php namespace App\Controllers;

use App\Libraries\FirebaseService;
use App\Models\CustomerModel;

class NotificationController extends BaseController
{
    public function send()
    {
        $firebase = new FirebaseService();

        $deviceToken = 'dNbASJF7okXjkWZlSxx4wD:APA91bFMNrQUjP6xghY9ncMA92xvLT-b24aj1R7MViQXitEyqDDnwMxzjRUusDmEDIimWnj-BynO3RIZhSEl3GT7YWIoIpeGzg1H5Ea-8pp1u7M9AXnyv1I'; // 🔁 Replace with real token
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

    public function sendFirstStepEmail()
    {
        $userModel = new CustomerModel();

        // Get tomorrow's date
        $tomorrow = date('Y-m-d', strtotime('+1 day'));

        // Fetch users created tomorrow (assuming `created_at` is DATE or DATETIME)
        $users = $userModel
            ->where("DATE(created_at)", $tomorrow)
            ->findAll();

        if (empty($users)) {
            return 'ℹ️ No users found with created_at = tomorrow (' . $tomorrow . ')';
        }

        // Prepare recipients array
        $recipients = [];

        foreach ($users as $user) {
            $recipients[] = [
                'email' => $user['email'],
                'name'  => $user['name'] ?? 'Customer'
            ];
        }

        // Call EmailController's function
        $emailController = new EmailController();
        $result = $emailController->sendRoomStepEmailToMultiple($recipients);

        // Log results
        foreach ($result as $log) {
            echo $log . "<br>";
        }
    }
}
