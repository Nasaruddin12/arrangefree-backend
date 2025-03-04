<?php

namespace App\Models;

use CodeIgniter\Model;

class BookingPaymentsModel extends Model
{
    protected $table      = 'booking_payments';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'booking_id',
        'user_id',
        'amount',
        'payment_method',
        'transaction_id',
        'payment_status',
        'payment_date',
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
        'booking_id'      => 'required|integer',
        'user_id'         => 'required|integer',
        'amount'          => 'required|decimal',
        'payment_method'  => 'required|string|max_length[50]',
        'transaction_id'  => 'permit_empty|string|max_length[100]',
        'payment_status'  => 'required|in_list[pending,completed,failed,refunded]',
        'payment_date'    => 'permit_empty|valid_date[Y-m-d H:i:s]',
    ];

    /**
     * Custom Validation Messages
     */
    protected $validationMessages = [
        'booking_id'      => ['required' => 'Booking ID is required.', 'integer' => 'Booking ID must be a valid number.'],
        'user_id'         => ['required' => 'User ID is required.', 'integer' => 'User ID must be a valid number.'],
        'amount'          => ['required' => 'Payment amount is required.', 'decimal' => 'Amount must be a decimal value.'],
        'payment_method'  => ['required' => 'Payment method is required.', 'max_length' => 'Payment method must not exceed 50 characters.'],
        'transaction_id'  => ['max_length' => 'Transaction ID must not exceed 100 characters.'],
        'payment_status'  => ['required' => 'Payment status is required.', 'in_list' => 'Invalid payment status provided.'],
        'payment_date'    => ['valid_date' => 'Payment date must be in Y-m-d H:i:s format.'],
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
     * Get payment details by transaction ID
     */
    public function getPaymentByTransactionId($transaction_id)
    {
        return $this->where('transaction_id', $transaction_id)->first();
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus($id, $status, $transaction_id = null)
    {
        $data = ['payment_status' => $status];

        if ($transaction_id) {
            $data['transaction_id'] = $transaction_id;
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
