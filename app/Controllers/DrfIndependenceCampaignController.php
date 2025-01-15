<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\DrfIndependenceCampaignModel;
use CodeIgniter\API\ResponseTrait;
use Exception;

class DrfIndependenceCampaignController extends BaseController
{
    use ResponseTrait;
    public function createIndependenceCampaign()
    {
        try {
            $drfIndependenceCampaignModel = new DrfIndependenceCampaignModel();
            $validation = &$drfIndependenceCampaignModel;

            $independenceCampaignData = [
                'name' => $this->request->getVar('name'),
                'email' => $this->request->getVar('email'),
                'mobile_no' => $this->request->getVar('mobile_no'),
                'address' => $this->request->getVar('address'),
                'image' => $this->request->getVar('image'),
            ];

            $drfIndependenceCampaignModel->insert($independenceCampaignData);

            if (!empty($drfIndependenceCampaignModel->errors())) {
                throw new Exception('Validation', 400);
            }

            if ($drfIndependenceCampaignModel->db->error()['code']) {
                throw new Exception($drfIndependenceCampaignModel->db->error()['message'], 500);
            }

            if ($drfIndependenceCampaignModel->db->affectedRows() == 1) {
                $statusCode = 200;
                $response = [
                    'message' => 'Independence campaign created successfully.',
                ];
            } else {
                throw new Exception('Nothing to insert', 200);
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

    public function getIndependenceCampaign()
    {
        try {
            $DrfIndependenceCampaignModel = new DrfIndependenceCampaignModel();
            $Campaign = $DrfIndependenceCampaignModel->findAll();

            if (!$Campaign) {
                throw new Exception('Campaign not found', 404);
            }

            $response = [
                'status' => 200,
                'data' => $Campaign
            ];
        } catch (Exception $e) {
            $response = [
                'status' => $e->getCode() === 404 ? 404 : 500,
                'error' => $e->getCode() === 404 ? 'Brand not found' : $e->getMessage()
            ];
        }

        return $this->respond($response, $response['status']);
    }

}
