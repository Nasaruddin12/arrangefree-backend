<?php

namespace App\Models;

use CodeIgniter\Model;

class BookingPaymentsModel extends Model
{
    protected $table      = 'booking_payments';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';

    protected $allowedFields = [
        'booking_id',
        'user_id',
        'payment_gateway',
        'payment_method',
        'gateway_payment_id',
        'amount',
        'currency',
        'status',
        'paid_at',
        'created_at'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = null;
    protected $dateFormat    = 'datetime';

    /**
     * Validation Rules
     */
    protected $validationRules = [
        'booking_id'      => 'required|integer',
        'user_id'         => 'required|integer',
        'amount'          => 'required|decimal',
        'payment_gateway'    => 'required|in_list[razorpay,manual]',
        'payment_method'     => 'permit_empty|string|max_length[50]',
        'gateway_payment_id' => 'permit_empty|string|max_length[100]',
        'status'             => 'required|in_list[pending,success,failed,refunded,partial_refund]',
        'paid_at'            => 'permit_empty|valid_date[Y-m-d H:i:s]',
    ];

    /**
     * Custom Validation Messages
     */
    protected $validationMessages = [
        'booking_id'      => ['required' => 'Booking ID is required.', 'integer' => 'Booking ID must be a valid number.'],
        'user_id'         => ['required' => 'User ID is required.', 'integer' => 'User ID must be a valid number.'],
        'amount'          => ['required' => 'Payment amount is required.', 'decimal' => 'Amount must be a decimal value.'],
        'payment_gateway'    => ['required' => 'Payment gateway is required.', 'in_list' => 'Invalid payment gateway provided.'],
        'payment_method'     => ['max_length' => 'Payment method must not exceed 50 characters.'],
        'gateway_payment_id' => ['max_length' => 'Gateway payment ID must not exceed 100 characters.'],
        'status'             => ['required' => 'Payment status is required.', 'in_list' => 'Invalid payment status provided.'],
        'paid_at'            => ['valid_date' => 'Paid at must be in Y-m-d H:i:s format.'],
    ];

    /**
     * Create a new payment record
     */
    public function createPayment($data)
    {
        if (!$this->validate($data)) {
            return $this->errors(); // Return validation errors
        }
        return $this->insert($data);
    }

    /**
     * Get payment details by booking ID
     */
    public function getPaymentByBookingId($booking_id)
    {
        return $this->where('booking_id', $booking_id)->first();
    }

    /**
     * Get payment details by gateway payment ID
     */
    public function getPaymentByTransactionId($transaction_id)
    {
        return $this->where('gateway_payment_id', $transaction_id)->first();
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus($id, $status, $transaction_id = null)
    {
        $data = ['status' => $status];

        if ($transaction_id) {
            $data['gateway_payment_id'] = $transaction_id;
        }

        return $this->update($id, $data);
    }

    /**
     * Get all payments for a user
     */
    public function getUserPayments($user_id)
    {
        return $this->where('user_id', $user_id)->findAll();
    }
}
