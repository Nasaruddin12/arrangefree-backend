<?php

namespace App\Models;

use CodeIgniter\Model;

class BookingServicesModel extends Model
{
    protected $table      = 'booking_services';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'booking_id',
        'service_id',
        'service_type_id',
        'room_id',
        'rate_type',
        'value',
        'rate',
        'amount',
        'addons',
        'description',
        'reference_image',
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
        'service_id'      => 'required|integer',
        'service_type_id' => 'required|integer',
        'room_id'         => 'required|integer',
        'rate_type'       => 'required|string|max_length[50]',
        'value'           => 'required|string',
        'rate'            => 'required|decimal',
        'amount'          => 'required|decimal',
        'description'     => 'permit_empty|string',
        'reference_image' => 'permit_empty|string',
    ];

    /**
     * Custom Validation Messages
     */
    protected $validationMessages = [
        'booking_id'      => ['required' => 'Booking ID is required.', 'integer' => 'Booking ID must be a number.'],
        'service_id'      => ['required' => 'Service ID is required.', 'integer' => 'Service ID must be a number.'],
        'service_type_id' => ['required' => 'Service Type ID is required.', 'integer' => 'Service Type ID must be a number.'],
        'room_id'         => ['required' => 'Room ID is required.', 'integer' => 'Room ID must be a number.'],
        'rate_type'       => ['required' => 'Rate Type is required.', 'max_length' => 'Rate Type cannot exceed 50 characters.'],
        'value'           => ['required' => 'Value is required.'],
        'rate'            => ['required' => 'Rate is required.', 'decimal' => 'Rate must be a decimal number.'],
        'amount'          => ['required' => 'Amount is required.', 'decimal' => 'Amount must be a decimal number.'],
    ];

    /**
     * Create a new booking service record
     */
    public function createBookingService($data)
    {
        return $this->insert($data);
    }

    /**
     * Get services by Booking ID
     */
    public function getServicesByBookingId($booking_id)
    {
        return $this->where('booking_id', $booking_id)->findAll();
    }

    /**
     * Update a booking service record
     */
    public function updateBookingService($id, $data)
    {
        return $this->update($id, $data);
    }

    /**
     * Delete a booking service record
     */
    public function deleteBookingService($id)
    {
        return $this->delete($id);
    }
}
