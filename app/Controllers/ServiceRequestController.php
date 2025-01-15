<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ServiceRequestModel;
use CodeIgniter\API\ResponseTrait;
use Exception;

class ServiceRequestController extends BaseController
{
    use ResponseTrait;
    public function createservice()
    {
        try {
            $ServiceRequestModel = new ServiceRequestModel();
            $validation = \Config\Services::validation();
            $statusCode = 200;

            $serviceData = [
                'user_id' => $this->request->getVar('user_id'),
                'service' => $this->request->getVar('service'),
                'message' => $this->request->getVar('message'),
                'status' => $this->request->getVar('status'),
            ];

            $ServiceRequestModel->insert($serviceData);

            if (!empty($ServiceRequestModel->errors())) {
                throw new Exception('Validation', 400);
            }

            if ($ServiceRequestModel->db->error()['code']) {
                throw new Exception($ServiceRequestModel->db->error()['message'], 500);
            }

            if ($ServiceRequestModel->db->affectedRows() == 1) {
                $response = [
                    'message' => 'service created successfully.',
                    'service_id' => $ServiceRequestModel->db->insertID(),
                ];
            }
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? ['validation' => $validation->getErrors()] : $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    // Get all services
    public function getservices()
    {
        $ServiceRequestModel = new ServiceRequestModel();
        try {
            $data = $ServiceRequestModel->findAll();

            if (!empty($ServiceRequestModel->errors())) {
                throw new Exception('Validation', 400);
            }

            if ($ServiceRequestModel->db->error()['code']) {
                throw new Exception($ServiceRequestModel->db->error()['message'], 500);
            }

            $statusCode = 200;
            $response = [
                'data' => $data
            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? $ServiceRequestModel->errors() : $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    public function updateServices($id)
    {
        $ServiceRequestModel = new ServiceRequestModel();
        try {
            $data  = [
                'user_id' => $this->request->getVar('user_id'),
                'service' => $this->request->getVar('service'),
                'message' => $this->request->getVar('message'),
                'status' => $this->request->getVar('status'),
            ];

            $ServiceRequestModel->update($id, $data);

            if (!empty($ServiceRequestModel->errors())) {
                throw new Exception('Validation', 400);
            }

            if ($ServiceRequestModel->db->error()['code']) {
                throw new Exception($ServiceRequestModel->db->error()['message'], 500);
            }

            if ($ServiceRequestModel->db->affectedRows() == 1) {
                $statusCode = 200;
                $response = [
                    'message' => 'service updated successfully.',
                    'service_id' => $ServiceRequestModel->db->insertID(),
                ];
            } else {
                throw new Exception('Nothing to update', 200);
            }
            
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? $ServiceRequestModel->errors() : $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    public function getServiceByUserId($id)
    {
        $ServiceRequestModel = new ServiceRequestModel();
        try {
            $data = $ServiceRequestModel->where('user_id',$id)->findAll();

            if (!empty($ServiceRequestModel->errors())) {
                throw new Exception('Validation', 400);
            }

            if ($ServiceRequestModel->db->error()['code']) {
                throw new Exception($ServiceRequestModel->db->error()['message'], 500);
            }

            $statusCode = 200;
            $response = [
                'data' => $data
            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? $ServiceRequestModel->errors() : $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }
}
