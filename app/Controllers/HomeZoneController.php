<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\HomeZoneModel;
use CodeIgniter\API\ResponseTrait;
use Exception;

class HomeZoneController extends BaseController
{
    use ResponseTrait;
    public function createHomeZone()
    {
        try {
            $session = session();
            $homeZoneModel = new HomeZoneModel();
            $validation = \Config\Services::validation();
            // $statusCode = 200;

            $userBackendToFrontendAttrs = [
                'title' => 'title',
        
            ];

            $HomeZoneData = array();
            foreach ($userBackendToFrontendAttrs as $backendAttr => $frontendAttr) {
                $HomeZoneData[$backendAttr] = $this->request->getVar($frontendAttr);
            }

            $homeZoneModel->insert($HomeZoneData);
            if (!empty($homeZoneModel->errors())) {
                throw new Exception('Validation', 400);
            }
            if ($homeZoneModel->db->error()['code']) {
                throw new Exception($homeZoneModel->db->error()['message'], 500);
            }
            if ($homeZoneModel->db->affectedRows() == 1) {
                $statusCode = 200;
                $response = [
                    'message' => 'Home Zone Created Successfully.',
                    'homeZone_id' => $homeZoneModel->db->insertID(),
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

    public function getHomeZone()
    {

        $homeZoneModel = new HomeZoneModel();
        try {
            $data = $homeZoneModel->findAll();

            if (!empty($homeZoneModel->errors())) {
                // $validation = &$accountfaqModel;
                throw new Exception('Validation', 400);
            }
            if ($homeZoneModel->db->error()['code'])
                throw new Exception($homeZoneModel->db->error()['message'], 500);

            $statusCode =200;
            $response = [
                "data" => $data
            ];
        } catch (\Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? $homeZoneModel->errors() : $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    public function updateHomeZone($id)
    {
        try {
            $session = session();
            $homeZoneModel = new HomeZoneModel();
            $validation = &$homeZoneModel;
            // $statusCode = 200;
    
            $userBackendToFrontendAttrs = [
                'title' => 'title',
        
            ];
    
            $homeZoneData = array();
            foreach ($userBackendToFrontendAttrs as $backendAttr => $frontendAttr) {
                $homeZoneData[$backendAttr] = $this->request->getVar($frontendAttr);
            }
            $homeZoneData['id'] = $id;
            // var_dump($homeZoneData);
            $homeZoneModel->update($id, $homeZoneData); // update the Customer with the given ID
            if (!empty($homeZoneModel->errors())) {
                throw new Exception('Validation', 400);
            }
            if ($homeZoneModel->db->error()['code']) {
                throw new Exception($homeZoneModel->db->error()['message'], 500);
            }
            if ($homeZoneModel->db->affectedRows() == 1) {
                $statusCode = 200;
                $response = [
                    'message' => 'Home Zone updated successfully.',
                ];
            } else {
                throw new Exception('Nothing to update', 200);
            }
        } catch (\Exception $e) {
            // $db->transRollback();
            $statusCode = $e->getCode() === 400 ? 400 : ($e->getCode());
            $response = [
                'error' => $e->getCode() === 400 ? ['validation' => $validation->errors()] : $e->getMessage()
            ];
        }
    
        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    public function deleteHomeZone($id)
{
    try {
        $homeZoneModel = new HomeZoneModel();
        $homeZoneModel->delete($id);

        if ($homeZoneModel->db->affectedRows() == 1) {
            $statusCode = 200;
            $response = [
                'message' => 'Home Zone Category deleted successfully.'
            ];
        } else {
            throw new Exception('Nothing to delete', 200);
        }
    } catch (\Exception $e) {
        $statusCode = $e->getCode() === 400 ? 400 : ($e->getCode());
        $response = [
            'error' => $e->getMessage()
        ];
    }

    $response['status'] = $statusCode;
    return $this->respond($response, $statusCode);
}

}
