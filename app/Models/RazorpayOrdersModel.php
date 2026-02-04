<?php

namespace App\Models;

use CodeIgniter\Model;

class RazorpayOrdersModel extends Model
{
    protected $table      = 'razorpay_orders';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';

    protected $allowedFields = [
        'booking_id',
        'user_id',
        'razorpay_order_id',
        'amount',
        'currency',
        'status',
        'receipt',
        'attempts',
        'payment_id'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = null;
    protected $dateFormat    = 'datetime';

    /**
     * Validation Rules
     */
    protected $validationRules = [
        'booking_id'       => 'required|integer',
        'user_id'          => 'required|integer',
        'razorpay_order_id' => 'required|string|max_length[100]',
        'amount'           => 'required|decimal',
        'currency'         => 'required|string|max_length[10]',
        'status'           => 'required|in_list[created,paid,failed]',
        'receipt'          => 'permit_empty|string|max_length[100]',
        'attempts'         => 'permit_empty|integer',
        'payment_id'       => 'permit_empty|string|max_length[100]',
    ];

    /**
     * Custom Validation Messages
     */
    protected $validationMessages = [
        'booking_id'       => ['required' => 'Booking ID is required.', 'integer' => 'Booking ID must be a valid number.'],
        'user_id'          => ['required' => 'User ID is required.', 'integer' => 'User ID must be a valid number.'],
        'razorpay_order_id' => ['required' => 'Razorpay Order ID is required.', 'max_length' => 'Razorpay Order ID must not exceed 100 characters.'],
        'amount'           => ['required' => 'Amount is required.', 'decimal' => 'Amount must be a decimal value.'],
        'currency'         => ['required' => 'Currency is required.', 'max_length' => 'Currency must not exceed 10 characters.'],
        'status'           => ['required' => 'Order status is required.', 'in_list' => 'Invalid order status provided.'],
        'receipt'          => ['max_length' => 'Receipt must not exceed 100 characters.'],
        'payment_id'       => ['max_length' => 'Payment ID must not exceed 100 characters.'],
    ];

    /**
     * Create a new Razorpay order record
     */
    public function createOrder($data)
    {
        if (!$this->validate($data)) {
            return $this->errors(); // Return validation errors
        }
        return $this->insert($data);
    }

    /**
     * Get order details by ID
     */
    public function getOrderById($id)
    {
        return $this->where('id', $id)->first();
    }

    /**
     * Get order details by Razorpay Order ID
     */
    public function getOrderByRazorpayOrderId($razorpay_order_id)
    {
        return $this->where('razorpay_order_id', $razorpay_order_id)->first();
    }

    /**
     * Update order status
     */
    public function updateOrderStatus($razorpay_order_id, $status, $payment_id = null)
    {
        $updateData = ['status' => $status];
        if ($payment_id) {
            $updateData['payment_id'] = $payment_id;
        }
        return $this->where('razorpay_order_id', $razorpay_order_id)->set($updateData)->update();
    }

    /**
     * Get all orders for a user
     */
    public function getUserOrders($user_id)
    {
        return $this->where('user_id', $user_id)->findAll();
    }
}
