<?php

namespace App\Controllers;

use App\Libraries\FirebaseService;
use App\Models\CustomerModel;
use CodeIgniter\API\ResponseTrait;

class NotificationController extends BaseController
{

    use ResponseTrait;
    public function send()
    {
        $firebase = new FirebaseService();

        // $deviceToken = 'dNbASJF7okXjkWZlSxx4wD:APA91bFMNrQUjP6xghY9ncMA92xvLT-b24aj1R7MViQXitEyqDDnwMxzjRUusDmEDIimWnj-BynO3RIZhSEl3GT7YWIoIpeGzg1H5Ea-8pp1u7M9AXnyv1I'; // 🔁 Replace with real token
        $deviceToken = 'eKSGfQNwukYzhnvp9SeEl7:APA91bEqlJXxTIrE-KfvJ_o2ET_Dt-yhLdLGwTG5Twv2YPWyZ4twEXmqRe5AIYtSA6adFA6h6HcE85YOeZGlXzcwkMN441xnVwanaXiwJAh9d-jBqdbHcFY'; // 🔁 Replace with real token
        $title = 'Welcome to Seeb!';
        $body = 'Your booking has been confirmed.';

        try {
            $response = $firebase->sendNotification($deviceToken, $title, $body);
            if ($response->getStatusCode() == 200) {

                return $this->respond([
                    'status'  => 200,
                    'message' => 'Notification sent successfully.',

                ], 200);
            } else {
                return $this->respond([
                    'status'  => $response->getStatusCode(),
                    'message' => 'Failed to send notification: ' . $response->getBody(),
                ], $response->getStatusCode());
            }
        } catch (\Exception $e) {
            return $this->respond([
                'status'  => 500,
                'message' => 'Failed: ' . $e->getMessage(),
            ], 500);
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
