<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\SubscriptionModel;
use CodeIgniter\API\ResponseTrait;
use Exception;

class SubsriptionController extends BaseController
{
    use ResponseTrait;
    public function createSubscription()
    {
        try {
            $subscriptionModel = new SubscriptionModel();
            $validation = \Config\Services::validation();
            $statusCode = 200;

            $subscriptionData = [
                'user_id' => $this->request->getVar('user_id'),
                'vendor_id' => $this->request->getVar('vendor_id'),
                'subscription_amount' => $this->request->getVar('subscription_amount'),
                'amount_payed' => $this->request->getVar('amount_payed'),
                'subscription_date' => $this->request->getVar('subscription_date'),
                'subscription_pdf' => $this->request->getVar('subscription_pdf'),
            ];

            $subscriptionModel->insert($subscriptionData);

            if (!empty($subscriptionModel->errors())) {
                throw new Exception('Validation', );
            }

            if ($subscriptionModel->db->error()['code']) {
                throw new Exception($subscriptionModel->db->error()['message']);
            }

            $response = [
                'message' => 'Subscription created successfully.',
                'subscription_id' => $subscriptionModel->db->insertID(),
            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode()  === 400 ? 400 : 500 ;
            $response = [
                'error' => $e->getMessage()
            ];
        }
        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    // Get all subscriptions
    public function getSubscriptions($id)
    {
        $subscriptionModel = new SubscriptionModel();
        try {
            $data = $subscriptionModel->where('id',$id)->findAll();

            if (!empty($subscriptionModel->errors())) {
                throw new Exception('Validation', );
            }

            if ($subscriptionModel->db->error()['code']) {
                throw new Exception($subscriptionModel->db->error()['message']);
            }

            $statusCode = 200;
            $response = [
                'data' => $data
            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode()  === 400 ? 400 : 500  ;
            $response = [
                'error' => $e->getMessage()
            ];
        }
        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    // Update a subscription by ID
    public function updateSubscription($id)
    {
        try {
            $subscriptionModel = new SubscriptionModel();
            $validation = &$subscriptionModel;

            $subscriptionData = [
                'user_id' => $this->request->getVar('user_id'),
                'vendor_id' => $this->request->getVar('vendor_id'),
                'subscription_amount' => $this->request->getVar('subscription_amount'),
                'amount_payed' => $this->request->getVar('amount_payed'),
                'subscription_date' => $this->request->getVar('subscription_date'),
            ];

            $subscriptionModel->update($id, $subscriptionData);

            if (!empty($subscriptionModel->errors())) {
                throw new Exception('Validation', );
            }

            if ($subscriptionModel->db->error()['code']) {
                throw new Exception($subscriptionModel->db->error()['message']);
            }

            if ($subscriptionModel->db->affectedRows() == 1) {
                $statusCode = 200;
                $response = [
                    'message' => 'Subscription updated successfully.',
                ];
            } else {
                throw new Exception('Nothing to update', 200);
            }
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500 ;
            $response = [
                'error' => $e->getMessage()
            ];
        }
        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    // Delete a subscription by ID
    public function deleteSubscription($id)
    {
        try {
            $subscriptionModel = new SubscriptionModel();
            $subscriptionModel->delete($id);

            if ($subscriptionModel->db->affectedRows() == 1) {
                $statusCode = 200;
                $response = [
                    'message' => 'Subscription deleted successfully.'
                ];
            } else {
                throw new Exception('Nothing to delete', 200);
            }
        } catch (Exception $e) {
            $statusCode = $e->getCode()  === 400 ? 400 : 500;
            $response = [
                'error' => $e->getMessage()
            ];
        }
        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }
    public function getSubscriptionsByUserId($id)
    {
        $subscriptionModel = new SubscriptionModel();
        try {
            $data = $subscriptionModel->where('user_id',$id)->findAll();

            if (!empty($subscriptionModel->errors())) {
                throw new Exception('Validation', );
            }

            if ($subscriptionModel->db->error()['code']) {
                throw new Exception($subscriptionModel->db->error()['message']);
            }

            $statusCode = 200;
            $response = [
                'data' => $data
            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode()  === 400 ? 400 : 500  ;
            $response = [
                'error' => $e->getMessage()
            ];
        }
        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    
}
