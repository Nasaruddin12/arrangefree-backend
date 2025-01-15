<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\CampaignsDataModel;
use CodeIgniter\API\ResponseTrait;
use Exception;

class CampaignsDataController extends BaseController
{
    use ResponseTrait;
    public function createCampaignData()
    {
        try {
            $CampaignsDataModel = new CampaignsDataModel();
            $validation = \Config\Services::validation();
            $statusCode = 200;

            $campaignData = [
                'name' => $this->request->getVar('name'),
                'customer_name' => $this->request->getVar('customer_name'),
                'customer_mobile_no' => $this->request->getVar('customer_mobile_no'),
                'insta_handle' => $this->request->getVar('insta_handle'),
                'score' => $this->request->getVar('score'),
            ];

            $CampaignsDataModel->insert($campaignData);

            if (!empty($CampaignsDataModel->errors())) {
                throw new Exception('Validation', 400);
            }

            if ($CampaignsDataModel->db->error()['code']) {
                throw new Exception($CampaignsDataModel->db->error()['message'], 500);
            }

            if ($CampaignsDataModel->db->affectedRows() == 1) {
                $response = [
                    'message' => 'Campaign created successfully.',
                    'Campaign_id' => $CampaignsDataModel->db->insertID(),
                ];
            }
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? ['validation' => $validation->errors()] : $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    public function getCampaignData()
    {
        $CampaignsDataModel = new CampaignsDataModel();
        try {
            $data = $CampaignsDataModel->findAll();

            if (!empty($CampaignsDataModel->errors())) {
                throw new Exception('Validation', 400);
            }

            if ($CampaignsDataModel->db->error()['code']) {
                throw new Exception($CampaignsDataModel->db->error()['message'], 500);
            }

            $statusCode = 200;
            $response = [
                'data' => $data
            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? $CampaignsDataModel->errors() : $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    // Update a Campaign by ID
public function updateCampaignData($id)
{
    try {
        $CampaignsDataModel = new CampaignsDataModel();
        $validation = &$CampaignsDataModel;

        $campaignData = [
            'name' => $this->request->getVar('name'),
            'customer_name' => $this->request->getVar('customer_name'),
            'customer_mobile_no' => $this->request->getVar('customer_mobile_no'),
            'insta_handle' => $this->request->getVar('insta_handle'),
            'score' => $this->request->getVar('score'),
        ];

        $CampaignsDataModel->update($id, $campaignData);

        if (!empty($CampaignsDataModel->errors())) {
            throw new Exception('Validation', 400);
        }

        if ($CampaignsDataModel->db->error()['code']) {
            throw new Exception($CampaignsDataModel->db->error()['message'], 500);
        }

        if ($CampaignsDataModel->db->affectedRows() == 1) {
            $statusCode = 200;
            $response = [
                'message' => 'Campaign updated successfully.',
            ];
        } else {
            throw new Exception('Nothing to update', 200);
        }
    } catch (Exception $e) {
        $statusCode = $e->getCode() === 400 ? 400 : ($e->getCode());
        $response = [
            'error' => $e->getCode() === 400 ? ['validation' => $validation->errors()] : $e->getMessage()
        ];
    }

    $response['status'] = $statusCode;
    return $this->respond($response, $statusCode);
}

// Delete a Campaign by ID
public function deleteCampaignData($id)
{
    try {
        $CampaignsDataModel = new CampaignsDataModel();
        $CampaignsDataModel->delete($id);

        if ($CampaignsDataModel->db->affectedRows() == 1) {
            $statusCode = 200;
            $response = [
                'message' => 'Campaign deleted successfully.'
            ];
        } else {
            throw new Exception('Nothing to delete', 200);
        }
    } catch (Exception $e) {
        $statusCode = $e->getCode() === 400 ? 400 : ($e->getCode());
        $response = [
            'error' => $e->getMessage()
        ];
    }

    $response['status'] = $statusCode;
    return $this->respond($response, $statusCode);
}
}
