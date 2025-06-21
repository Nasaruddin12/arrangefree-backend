<?php

namespace App\Models;

use CodeIgniter\Model;

class RazorpayOrdersModel extends Model
{
    protected $table      = 'razorpay_orders';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'user_id',
        'booking_id',
        'order_id',
        'amount',
        'currency',
        'status',
        'payment_id',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Validation Rules
     */
    protected $validationRules = [
        'user_id'    => 'required|integer',
        'booking_id' => 'required|integer', // Nullable if not always associated with a booking
        'order_id'   => 'required|string|max_length[100]',
        'amount'     => 'required|decimal',
        'currency'   => 'required|string|max_length[3]',
        'status'     => 'required|in_list[created,pending,paid,failed,refunded]',
        'payment_id' => 'permit_empty|string|max_length[100]',
    ];

    /**
     * Custom Validation Messages
     */
    protected $validationMessages = [
        'user_id'    => ['required' => 'User ID is required.', 'integer' => 'User ID must be a valid number.'],
        'booking_id' => ['required' => 'Booking ID is required.', 'integer' => 'Booking ID must be a valid number.'],
        'order_id'   => ['required' => 'Order ID is required.', 'max_length' => 'Order ID must not exceed 100 characters.'],
        'amount'     => ['required' => 'Amount is required.', 'decimal' => 'Amount must be a decimal value.'],
        'currency'   => ['required' => 'Currency is required.', 'max_length' => 'Currency must be 3 characters (e.g., INR, USD).'],
        'status'     => ['required' => 'Order status is required.', 'in_list' => 'Invalid order status provided.'],
        'payment_id' => ['max_length' => 'Payment ID must not exceed 100 characters.'],
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
    public function getOrderByOrderId($order_id)
    {
        return $this->where('order_id', $order_id)->first();
    }

    /**
     * Update order status
     */
    public function updateOrderStatus($order_id, $status, $payment_id = null)
    {
        $data = ['status' => $status];

        if ($payment_id) {
            $data['payment_id'] = $payment_id;
        }

        return $this->where('order_id', $order_id)->set($data)->update();
    }

    /**
     * Get all orders for a user
     */
    public function getUserOrders($user_id)
    {
        return $this->where('user_id', $user_id)->findAll();
    }
}
