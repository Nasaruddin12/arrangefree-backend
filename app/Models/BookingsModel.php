<?php

namespace App\Models;

use CodeIgniter\Model;

class BookingsModel extends Model
{
    protected $table      = 'bookings';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'user_id',
        'address_id',
        'slot_date',
        'total_amount',
        'discount',
        'final_amount',
        'paid_amount',
        'amount_due',
        'payment_type',
        'payment_status',
        'status',
        'applied_coupon',
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
        'user_id'        => 'required|integer',
        'slot_date'      => 'required|valid_date[Y-m-d]',  // Ensures slot_date is a valid date
        'total_amount'   => 'required|decimal',
        'discount'       => 'required|decimal',
        'final_amount'   => 'required|decimal',
        'status'         => 'required|in_list[pending,confirmed,cancelled,completed]',
        'applied_coupon' => 'permit_empty|string|max_length[50]',
    ];

    /**
     * Custom Validation Messages
     */
    protected $validationMessages = [
        'user_id'        => ['required' => 'User ID is required.', 'integer' => 'User ID must be a number.'],
        'slot_date'      => ['required' => 'Slot Date is required.', 'valid_date' => 'Slot Date must be in YYYY-MM-DD format.'],
        'total_amount'   => ['required' => 'Total Amount is required.', 'decimal' => 'Total Amount must be a decimal value.'],
        'discount'       => ['required' => 'Discount is required.', 'decimal' => 'Discount must be a decimal value.'],
        'final_amount'   => ['required' => 'Final Amount is required.', 'decimal' => 'Final Amount must be a decimal value.'],
        'status'         => ['required' => 'Status is required.', 'in_list' => 'Invalid status value.'],
        'applied_coupon' => ['max_length' => 'Coupon code cannot exceed 50 characters.'],
    ];

    /**
     * Create a new booking
     */
    public function createBooking($data)
    {
        return $this->insert($data);
    }

    /**
     * Get booking by ID
     */
    public function getBookingById($id)
    {
        return $this->find($id);
    }

    /**
     * Get all bookings for a user
     */
    public function getBookingsByUserId($user_id)
    {
        return $this->where('user_id', $user_id)->findAll();
    }

    /**
     * Update a booking
     */
    public function updateBooking($id, $data)
    {
        return $this->update($id, $data);
    }

    /**
     * Delete a booking
     */
    public function deleteBooking($id)
    {
        return $this->delete($id);
    }
}
