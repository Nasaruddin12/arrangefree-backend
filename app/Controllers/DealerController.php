<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\DealerModel;
use CodeIgniter\API\ResponseTrait;
use Exception;

class DealerController extends BaseController
{
    use ResponseTrait;

    // Create a new dealer
    public function createDealer()
    {
        try {
            $dealerModel = new DealerModel();
            $validation = \Config\Services::validation();
            $statusCode = 200;

            $dealerData = [
                'name' => $this->request->getVar('name'),
                'company_name' => $this->request->getVar('company_name'),
                'mobile_no' => $this->request->getVar('mobile_no'),
                'email' => $this->request->getVar('email'),
            ];

            $dealerModel->insert($dealerData);

            if (!empty($dealerModel->errors())) {
                throw new Exception('Validation', 400);
            }

            if ($dealerModel->db->error()['code']) {
                throw new Exception($dealerModel->db->error()['message'], 500);
            }

            if ($dealerModel->db->affectedRows() == 1) {
                $response = [
                    'message' => 'Dealer created successfully.',
                    'dealer_id' => $dealerModel->db->insertID(),
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

    // Get all dealers
    public function getDealers()
    {
        $dealerModel = new DealerModel();
        try {
            $data = $dealerModel->findAll();

            if (!empty($dealerModel->errors())) {
                throw new Exception('Validation', 400);
            }

            if ($dealerModel->db->error()['code']) {
                throw new Exception($dealerModel->db->error()['message'], 500);
            }

            $statusCode = 200;
            $response = [
                'data' => $data
            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? $dealerModel->errors() : $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    // Update a dealer by ID
public function updateDealer($id)
{
    try {
        $dealerModel = new DealerModel();
        $validation = &$dealerModel;

        $dealerData = [
            'name' => $this->request->getVar('name'),
            'company_name' => $this->request->getVar('company_name'),
            'contact_no' => $this->request->getVar('contact_no'),
            'email' => $this->request->getVar('email'),
        ];

        $dealerModel->update($id, $dealerData);

        if (!empty($dealerModel->errors())) {
            throw new Exception('Validation', 400);
        }

        if ($dealerModel->db->error()['code']) {
            throw new Exception($dealerModel->db->error()['message'], 500);
        }

        if ($dealerModel->db->affectedRows() == 1) {
            $statusCode = 200;
            $response = [
                'message' => 'Dealer updated successfully.',
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

// Delete a dealer by ID
public function deleteDealer($id)
{
    try {
        $dealerModel = new DealerModel();
        $dealerModel->delete($id);

        if ($dealerModel->db->affectedRows() == 1) {
            $statusCode = 200;
            $response = [
                'message' => 'Dealer deleted successfully.'
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

function countAllDealers()
    {
        try {
            $dealerModel = new DealerModel();
            $rest = $dealerModel->countAllResults();
           
            if ($rest) {
                $response = [
                    "Status" => 200,
                    "Data" => $rest
                ];
            } else {
                $response = [
                    "Status" => 404,
                    "Msg" => "No Data Found"
                ];
            }
        } catch (Exception $e) {
            $response = [
                "Status" => 500,
                "Error" => $e->getMessage()
            ];
        }
        return $this->respond($response, 200);
    }
}
