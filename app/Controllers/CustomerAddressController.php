<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\CustomerAddressModel;
use CodeIgniter\API\ResponseTrait;
use Exception;

class CustomerAddressController extends BaseController
{
    use ResponseTrait;
    public function createCustomerAddress()
    {
        try {
            $customerAddressModel = new CustomerAddressModel();
            $validation = &$customerAddressModel;
            $statusCode = 200;

            $userBackendToFrontendAttrs = [
                'customer_id' => 'customer_id',
                'first_name' => 'first_name',
                'last_name' => 'last_name',
                'email' => 'email',
                'phone' => 'phone',
                'street_address' => 'street_address',
                'city' => 'city',
                'state' => 'state',
                'country' => 'country',
                'pincode' => 'pincode',
                'address_label' => 'address_label',
            ];

            $customerAddressData = [];
            foreach ($userBackendToFrontendAttrs as $backendAttr => $frontendAttr) {
                $customerAddressData[$backendAttr] = $this->request->getVar($frontendAttr);
            }

            $customerAddressModel->insert($customerAddressData);

            if ($customerAddressModel->db->error()['code']) {
                throw new Exception($customerAddressModel->db->error()['message'], 500);
            }

            if (!empty($customerAddressModel->errors())) {
                throw new Exception('Validation', 400);
            }

            if ($customerAddressModel->db->affectedRows() == 1) {
                $statusCode = 200;
                $response = [
                    'message' => 'Customer Address created successfully.',
                    // 'rating_review_id' => $customerAddressModel->db->insertID(),
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

    public function customerAddressById($customerId)
    {
        try {
            $customerAddressModel = new CustomerAddressModel();
            $customerAddress = $customerAddressModel->where('customer_id', $customerId)->findAll();

            if (!empty($customerAddress)) {
                $statusCode = 200;
                $response = [
                    'message' => 'Customer Address found.',
                    'customer_address' => $customerAddress,
                ];
            } else {
                throw new Exception('Customer Address not found', 404);
            }
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 404 ? 404 : 500;
            $response = [
                'error' => $e->getCode() === 404 ? $e->getMessage() : 'Internal Server Error',
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

public function updateCustomerAddress($id)
{
    try {
        $customerAddressModel = new CustomerAddressModel();
        $validation = &$customerAddressModel;
        $statusCode = 200;

        $userBackendToFrontendAttrs = [
            'customer_id' => 'customer_id',
            'first_name' => 'first_name',
            'last_name' => 'last_name',
            'email' => 'email',
            'phone' => 'phone',
            'street_address' => 'street_address',
            'city' => 'city',
            'state' => 'state',
            'country' => 'country',
            'pincode' => 'pincode',
            'address_label' => 'address_label',
        ];

        $customerAddressData = [];
        foreach ($userBackendToFrontendAttrs as $backendAttr => $frontendAttr) {
            $customerAddressData[$backendAttr] = $this->request->getVar($frontendAttr);
        }

        $customerAddressModel->update($id, $customerAddressData);

        if ($customerAddressModel->db->error()['code']) {
            throw new Exception($customerAddressModel->db->error()['message'], 500);
        }

        if (!empty($customerAddressModel->errors())) {
            throw new Exception('Validation', 400);
        }

        if ($customerAddressModel->db->affectedRows() == 1) {
            $statusCode = 200;
            $response = [
                'message' => 'Customer Address updated successfully.',
                'customer_address_id' => $id,
            ];
        } else {
            throw new Exception('Nothing to update', 200);
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

public function deleteCustomerAddress($id)
{
    try {
        $customerAddressModel = new CustomerAddressModel();
        $validation = &$customerAddressModel;
        $statusCode = 200;

        // Check if the customer address exists
        $customerAddress = $customerAddressModel->find($id);
        if (!$customerAddress) {
            throw new Exception('Customer Address not found', 404);
        }

        // Delete the customer address
        $customerAddressModel->delete($id);

        if ($customerAddressModel->db->error()['code']) {
            throw new Exception($customerAddressModel->db->error()['message'], 500);
        }

        if ($customerAddressModel->db->affectedRows() == 1) {
            $statusCode = 200;
            $response = [
                'message' => 'Customer Address deleted successfully.',
            ];
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
