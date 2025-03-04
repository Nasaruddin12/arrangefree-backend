<?php

namespace App\Controllers;

use App\Models\RazorpayOrdersModel;
use CodeIgniter\RESTful\ResourceController;
use Exception;

class RazorpayOrdersController extends ResourceController
{
    protected $modelName = 'App\Models\RazorpayOrdersModel';
    protected $format    = 'json';

    /**
     * Create a new Razorpay Order
     */
    public function createOrder()
    {
        try {
            $rules = [
                'user_id'  => 'required|integer',
                'amount'   => 'required|decimal',
            ];

            if (!$this->validate($rules)) {
                return $this->respond([
                    'status'  => 400,
                    'message' => 'Validation failed',
                    'errors'  => $this->validator->getErrors()
                ], 400);
            }

            // Get validated input
            $userId = $this->request->getVar('user_id');
            $amount = $this->request->getVar('amount');

            // Initialize Razorpay API
            $config = new \Config\Razorpay(); // Ensure this config file exists
            $razorpay = new \Razorpay\Api\Api($config->keyId, $config->keySecret);
            $currency = $config->displayCurrency;
            $receipt = 'order_' . time();

            // Create order on Razorpay
            $orderData = [
                'amount'          => $amount * 100, // Convert to paisa
                'currency'        => $currency,
                'receipt'         => $receipt,
                'payment_capture' => 1,
            ];
            $razorpayOrder = $razorpay->order->create($orderData);

            // Store order details in database
            $razorpayModel = new RazorpayOrdersModel();
            $orderRecord = [
                'user_id'   => $userId,
                'order_id'  => $razorpayOrder->id,
                'amount'    => $razorpayOrder->amount / 100, // Convert back to original amount
                'currency'  => $currency,
                'status'    => $razorpayOrder->status,
                'receipt'   => $razorpayOrder->receipt,
                'offer_id'  => $razorpayOrder->offer_id ?? null,
            ];
            $razorpayModel->insert($orderRecord);

            return $this->respond([
                'status'  => 201,
                'message' => 'Order created successfully',
                'data'    => $orderRecord
            ], 201);
        } catch (\Razorpay\Api\Errors\BadRequestError $e) {
            return $this->respond([
                'status'  => 400,
                'message' => 'Razorpay request failed',
                'error'   => $e->getMessage()
            ], 400);
        } catch (Exception $e) {
            return $this->respond([
                'status'  => 500,
                'message' => 'Failed to create order',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a single order by ID
     */
    public function getOrder($id)
    {
        try {
            $razorpayModel = new RazorpayOrdersModel();
            $order = $razorpayModel->find($id);

            if (!$order) {
                return $this->respond([
                    'status'  => 404,
                    'message' => 'Order not found'
                ], 404);
            }

            return $this->respond([
                'status'  => 200,
                'message' => 'Order retrieved successfully',
                'data'    => $order
            ], 200);
        } catch (Exception $e) {
            return $this->respond([
                'status'  => 500,
                'message' => 'Failed to retrieve order',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all orders for a user
     */
    public function getUserOrders($user_id)
    {
        try {
            $razorpayModel = new RazorpayOrdersModel();
            $orders = $razorpayModel->where('user_id', $user_id)->findAll();

            return $this->respond([
                'status'  => 200,
                'message' => 'User orders retrieved successfully',
                'data'    => $orders
            ], 200);
        } catch (Exception $e) {
            return $this->respond([
                'status'  => 500,
                'message' => 'Failed to retrieve user orders',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update Order Status
     */
    public function updateOrderStatus()
    {
        try {
            $rules = [
                'order_id'   => 'required|string|max_length[100]',
                'status'     => 'required|in_list[created,pending,paid,failed,refunded]',
                'payment_id' => 'permit_empty|string|max_length[100]',
            ];

            if (!$this->validate($rules)) {
                return $this->respond([
                    'status'  => 400,
                    'message' => 'Validation failed',
                    'errors'  => $this->validator->getErrors()
                ], 400);
            }

            $order_id   = $this->request->getVar('order_id');
            $status     = $this->request->getVar('status');
            $payment_id = $this->request->getVar('payment_id') ?? null;
            $razorpayModel = new RazorpayOrdersModel();

            $order = $razorpayModel->getOrderByOrderId($order_id);
            if (!$order) {
                return $this->respond([
                    'status'  => 404,
                    'message' => 'Order not found'
                ], 404);
            }

            $razorpayModel->updateOrderStatus($order_id, $status, $payment_id);

            return $this->respond([
                'status'  => 200,
                'message' => 'Order status updated successfully',
                'data'    => compact('order_id', 'status', 'payment_id')
            ], 200);
        } catch (Exception $e) {
            return $this->respond([
                'status'  => 500,
                'message' => 'Failed to update order status',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an order by ID
     */
    public function deleteOrder($id)
    {
        try {
            $razorpayModel = new RazorpayOrdersModel();
            $order = $razorpayModel->find($id);

            if (!$order) {
                return $this->respond([
                    'status'  => 404,
                    'message' => 'Order not found'
                ], 404);
            }

            $razorpayModel->delete($id);

            return $this->respond([
                'status'  => 200,
                'message' => 'Order deleted successfully',
                'id'      => $id
            ], 200);
        } catch (Exception $e) {
            return $this->respond([
                'status'  => 500,
                'message' => 'Failed to delete order',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
