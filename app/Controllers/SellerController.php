<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\SellerModel;
use CodeIgniter\API\ResponseTrait;
use Exception;

class SellerController extends BaseController
{
    use ResponseTrait;
    public function createSeller()
    {
        try {
            
            $SellerModel = new SellerModel();
            // $validation = \Config\Services::validation();
            $validation  = &$SellerModel;
            // $statusCode = 200;

            $userBackendToFrontendAttrs = [
                'name' => 'name',
                'email' => 'email',
                'mobile_no' => 'mobile_no',
                'password' => 'password',
                'is_logged_in' => 'is_logged_in',
                'otp' => 'otp',
                'status' => 'status',
            ];

            $SellerData = array();
            foreach ($userBackendToFrontendAttrs as $backendAttr => $frontendAttr) {
                $SellerData[$backendAttr] = $this->request->getVar($frontendAttr);
            }
            

            $SellerModel->insert($SellerData);
            if (!empty($SellerModel->errors())) {
                throw new Exception('Validation', 400);
            }
            if ($SellerModel->db->error()['code']) {
                throw new Exception($SellerModel->db->error()['message'], 500);
            }
            if ($SellerModel->db->affectedRows() == 1) {
                $statusCode = 200;
                $response = [
                    'message' => 'Seller created successfully.',
                    'seller_id' => $SellerModel->db->insertID(),
                ];
            }
        } catch (\Exception $e) {
            // $db->transRollback();
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? ['validation' => $validation->errors()] : $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
        // $response['status'] = $statusCode;
    }

    public function getSeller()
    {

        $SellerModel = new SellerModel();
        try {
            $data = $SellerModel->findAll();

            if (!empty($SellerModel->errors())) {
                // $validation = &$accountfaqModel;
                throw new Exception('Validation', 400);
            }
            if ($SellerModel->db->error()['code'])
                throw new Exception($SellerModel->db->error()['message'], 500);

            $statusCode =200;
            $response = [
                "data" => $data
            ];
        } catch (\Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? $SellerModel->errors() : $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    public function updateSeller($id)
    {
        try {
            $SellerModel = new SellerModel();
            $validation = \Config\Services::validation();
            // $statusCode = 200;
    
            $userBackendToFrontendAttrs = [
                'name' => 'name',
                'email' => 'email',
                'mobile_no' => 'mobile_no',
                'password' => 'password',
                'is_logged_in' => 'is_logged_in',
                'otp' => 'otp',
                'status' => 'status',
            ];
    
            $SellerData = array();
            foreach ($userBackendToFrontendAttrs as $backendAttr => $frontendAttr) {
                $SellerData[$backendAttr] = $this->request->getVar($frontendAttr);
            }
            // var_dump($SellerData);
            $customerData['id'] = $id;

            $SellerModel->update($id, $SellerData); // update the seller with the given ID
            if (!empty($SellerModel->errors())) {
                throw new Exception('Validation', 400);
            }
            if ($SellerModel->db->error()['code']) {
                throw new Exception($SellerModel->db->error()['message'], 500);
            }
            if ($SellerModel->db->affectedRows() == 1) {
                $statusCode = 200;
                $response = [
                    'message' => 'Seller updated successfully.',
                    'seller_id' => $id,
                ];
            } else {
                throw new Exception('Nothing to update', 200);
            }
        } catch (\Exception $e) {
            // $db->transRollback();
            $statusCode = $e->getCode() === 400 ? 400 : ($e->getCode());
            $response = [
                'error' => $e->getCode() === 400 ? ['validation' => $SellerModel->errors()] : $e->getMessage()
            ];
        }
    
        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }
    
}
