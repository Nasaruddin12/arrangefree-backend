<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\AfSubcribedUserModel;
use App\Models\PhonePeTransactionModel;
use CodeIgniter\API\ResponseTrait;
use Config\Razorpay;
use DateTime;
use Exception;
use Razorpay\Api\Api;

class AfSubcribedUserController extends BaseController
{
    use ResponseTrait;
    public function createMembership()
    {

        try {
            $AfSubcribedUserModel = new AfSubcribedUserModel();
            $validation = \Config\Services::validation();
            $statusCode = 200;
            $startDate = new DateTime('now');
            $renewal_date = new DateTime('now');
            $renewal_date->modify('+1 year');
            $cardNumber = '061523205000';
            $amount = ($this->request->getVar('subscription_amount'))*100;
            $subscribtionData = [
                'card_id' => $this->request->getVar('card_id'),
                'user_id' => $this->request->getVar('user_id'),
                // 'transaction_id' => $this->request->getVar('transaction_id'),
                'start_date' => $startDate->format('Y-m-d'),
                'renewal_date' => $renewal_date->format('Y-m-d'),
                'subscription_amount' => $amount,
                'subscription_type' => $this->request->getVar('subscription_type'),
                'card_number' => $cardNumber,
            ];

            $config = new Razorpay();
            $razorpay = new Api($config->keyId, $config->keySecret);
            // print_r($razorpay);
            $currency = $config->displayCurrency;
            $receipt = 'ord' . date("Ymdhis");
            $data = [
                'amount' => (int)($amount),
                'currency' => $currency,
                'receipt' => $receipt,
                'payment_capture' => 1,
            ];
            $razorpayOrder = $razorpay->order->create($data);
            $razorpayOrderID = $razorpayOrder->id;
            $razorpayAmount = $razorpayOrder->amount;
            $subscribtionData['transaction_id'] = $razorpayOrderID;
            $AfSubcribedUserModel->insert($subscribtionData);

            if (!empty($AfSubcribedUserModel->errors())) {
                throw new Exception('Validation', 400);
            }

            if ($AfSubcribedUserModel->db->error()['code']) {
                throw new Exception($AfSubcribedUserModel->db->error()['message'], 500);
            }

            $subsID = $AfSubcribedUserModel->db->insertID();
            $uniqueCardNumber = $AfSubcribedUserModel->set(['card_number' => $cardNumber . $subsID])->update($subsID);

            $response = [
                'message' => 'SUbscribtion created successfully.',
                'order_id' => $razorpayOrderID,
                'amount' => $razorpayAmount,
                // 'dealer_id' => $AfSubcribedUserModel->db->insertID(),
            ];
            /* if ($AfSubcribedUserModel->db->affectedRows() == 1) {
            } */
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? ['validation' => $validation->getErrors()] : $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    // Get all dealers
    public function getMembership()
    {
        $AfSubcribedUserModel = new AfSubcribedUserModel();
        try {
            $data = $AfSubcribedUserModel->findAll();

            if (!empty($AfSubcribedUserModel->errors())) {
                throw new Exception('Validation', 400);
            }

            if ($AfSubcribedUserModel->db->error()['code']) {
                throw new Exception($AfSubcribedUserModel->db->error()['message'], 500);
            }

            $statusCode = 200;
            $response = [
                'data' => $data
            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? $AfSubcribedUserModel->errors() : $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    public function verifyPayment()
    {
        $razorpayPaymentId = $this->request->getVar('razorpay_payment_id');
        $razorpayOrderId = $this->request->getVar('razorpay_order_id');
        $razorpaySignature = $this->request->getVar('razorpay_signature');
        try {
            $config = new Razorpay();
            $api = new Api($config->keyId, $config->keySecret);
            $paymentData = $api->payment->fetch($razorpayPaymentId);

            // die(var_dump($paymentData));
            $paymentStatus = $paymentData->status;
            $orderID = $paymentData->order_id;
            $attributes = array('razorpay_signature' => $razorpaySignature, 'razorpay_payment_id' => $razorpayPaymentId, 'razorpay_order_id' => $orderID);

            $res = $api->utility->verifyPaymentSignature($attributes);

            if ($res == null) {
                $phonePeTransactionModel = new PhonePeTransactionModel();
                $validation = &$phonePeTransactionModel;

                $txnID = "TXN" . date("Ymdhis");
                $datatoinsert = [
                    "merchantTransactionId" => $txnID,
                    "transactionId" => $paymentData["id"],
                    "payment_status" => $paymentData["status"],
                    "transation_status" => $paymentData["status"],
                    "amount" => $paymentData["amount"],
                    "form_json" => serialize($paymentData),
                ];


                $phonePeTransactionModel->insert($datatoinsert);

                $transactionID = $phonePeTransactionModel->db->insertID();
                $AfSubcribedUserModel = new AfSubcribedUserModel();
                $AfSubcribedUserModel->set(['transaction_id' => $transactionID])->where('transaction_id', $orderID)->update();
                $statusCode = 200;
                $response = [
                    'message' => 'Payment successfull',
                ];
            }
        } catch (Exception $e) {
            // echo "<pre>";
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getMessage(),
            ];
            $response['status'] = $statusCode;
            return $response;
            // return redirect()->to('http://localhost:3000/failure', 200, 'refresh');
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    public function getMumbershipByCardId($id)
    {
        $AfSubcribedUserModel = new AfSubcribedUserModel();
        try {
            $data = $AfSubcribedUserModel->where('card_id',$id)->findAll();

            if (!empty($AfSubcribedUserModel->errors())) {
                throw new Exception('Validation', 400);
            }

            if ($AfSubcribedUserModel->db->error()['code']) {
                throw new Exception($AfSubcribedUserModel->db->error()['message'], 500);
            }

            $statusCode = 200;
            $response = [
                'data' => $data
            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? $AfSubcribedUserModel->errors() : $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }
}
