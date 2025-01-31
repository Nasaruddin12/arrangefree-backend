<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\InteriorContactUsModel;
use CodeIgniter\API\ResponseTrait;
use Exception;

class InteriorContactUsController extends BaseController
{
    use ResponseTrait;
    public function contactUs()
    {
        try {
            // $session = session();
            $InteriorContactUsModel = new InteriorContactUsModel();
            $validation = &$InteriorContactUsModel;

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
            $InteriorContactUsModel->insert($contactUsData);

            $insertId = $InteriorContactUsModel->getInsertID();
            if (!empty($InteriorContactUsModel->errors())) {
                throw new Exception('Validation', 400);
            }
            if ($InteriorContactUsModel->db->error()['code']) {
                throw new Exception($InteriorContactUsModel->db->error()['message'], 500);
            }
            if ($InteriorContactUsModel->db->affectedRows() == 1) {


                $leadData = $InteriorContactUsModel->where("id", $insertId)->first();

                $name = $leadData["name"];
                $email_id = $leadData["email_id"];
                $contact_number = $leadData["contact_number"];
                $msg = $leadData["message"];


                $emailController = new EmailController();
                $subject = 'New Leads Added';
                $message = "Hey! Admin,\n There is new Lead in Dorfee.\n Name:$name\n Email:$email_id\n Contact:$contact_number\n Message:$msg";
                $emailController->sendMail('leads@dorfee.com', $subject, $message);




                $statusCode = 200;
                $response = [
                    'message' => 'Contact Us created successfully.',
                    'id' => $insertId,
                    // 'customer_id' => $InteriorContactUsModel->db->insertID(),
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
            $InteriorContactUsModel = new InteriorContactUsModel();

            // Get request parameters
            $startDate = $this->request->getVar('start_date');
            $endDate = $this->request->getVar('end_date');
            $page = $this->request->getVar('page') ?? 1; // Default page 1
            $limit = $this->request->getVar('limit') ?? 10; // Default limit 10

            // Set default start and end date if not provided (current month)
            if (!$startDate || !$endDate) {
                $startDate = date('Y-m-01'); // First day of current month
                $endDate = date('Y-m-t');   // Last day of current month
            }

            // Apply date filtering and pagination
            $contactUsList = $InteriorContactUsModel
                ->where('created_at >=', $startDate)
                ->where('created_at <=', $endDate)
                ->paginate($limit, 'default', $page);

            $totalRecords = $InteriorContactUsModel
                ->where('created_at >=', $startDate)
                ->where('created_at <=', $endDate)
                ->countAllResults();

            return $this->respond([
                'message' => 'Success',
                'data' => $contactUsList,
                'pagination' => [
                    'current_page' => (int) $page,
                    'limit' => (int) $limit,
                    'total_records' => $totalRecords,
                    'total_pages' => ceil($totalRecords / $limit)
                ]
            ], 200);
        } catch (Exception $e) {
            return $this->respond([
                'error' => $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }
}
