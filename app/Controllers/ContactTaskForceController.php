<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ContactTaskForceModel;
use CodeIgniter\API\ResponseTrait;
use Exception;

class ContactTaskForceController extends BaseController
{
    use ResponseTrait;
    public function contactUs()
    {
        try {
            // $session = session();
            $ContactTaskForceModel = new ContactTaskForceModel();
            $validation = &$ContactTaskForceModel;

            // $statusCode = 200;

            $userBackendToFrontendAttrs = [
                'name' => 'name',
                'contact_number' => 'contact_number',
                'email_id' => 'email_id',
                'message' => 'message',
                'status' => 'status',
            ];

            $contactUsData = array();
            foreach ($userBackendToFrontendAttrs as $backendAttr => $frontendAttr) {
                $contactUsData[$backendAttr] = $this->request->getVar($frontendAttr);
            }
            // var_dump($contactUsData);
            // die;
            $ContactTaskForceModel->insert($contactUsData);

            $insertId = $ContactTaskForceModel->getInsertID();
            if (!empty($ContactTaskForceModel->errors())) {
                throw new Exception('Validation', 400);
            }
            if ($ContactTaskForceModel->db->error()['code']) {
                throw new Exception($ContactTaskForceModel->db->error()['message'], 500);
            }
            if ($ContactTaskForceModel->db->affectedRows() == 1) {


                $leadData = $ContactTaskForceModel->where("id", $insertId)->first();

                // $name = $leadData["name"];
                // $email_id = $leadData["email_id"];
                // $contact_number = $leadData["contact_number"];
                // $msg = $leadData["message"];


                // $emailController = new EmailController();
                // $subject = 'New Leads Added';
                // $message = "Hey! Admin,\n There is new Lead in Dorfee.\n Name:$name\n Email:$email_id\n Contact:$contact_number\n Message:$msg";
                // $emailController->sendMail('leads@dorfee.com', $subject, $message);




                $statusCode = 200;
                $response = [
                    'message' => 'Contact Us created successfully.',
                    // 'customer_id' => $ContactTaskForceModel->db->insertID(),
                    // 'customer_name' => $contactUsData['name'],
                ];
            }
        } catch (Exception $e) {
            // $db->transRollback();
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? ['validation' => $validation->errors()] : $e->getMessage(),
                'status' => $statusCode
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
        // $response['status'] = $statusCode;
    }

    public function getAllContactUs()
    {
        try {
            $ContactTaskForceModel = new ContactTaskForceModel();
            $contactUsList = $ContactTaskForceModel->findAll(); // Retrieve all contact us entries

            $statusCode = 200;
            $response = [
                'message' => 'Success',
                'data' => $contactUsList
            ];
        } catch (Exception $e) {
            $statusCode = 500;
            $response = [
                'error' => $e->getMessage(),
                'status' => $statusCode
            ];
        }

        return $this->respond($response, $statusCode);
    }
}
