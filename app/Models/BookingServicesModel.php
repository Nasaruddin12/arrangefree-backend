<?php

namespace App\Models;

use CodeIgniter\Model;

class BookingServicesModel extends Model
{
    protected $table      = 'booking_services';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';

    protected $allowedFields = [
        'booking_id',
        'parent_booking_service_id',
        'service_id',
        'addon_id',
        'service_type_id',
        'room_id',
        'quantity',
        'unit',
        'rate',
        'amount',
        'room_length',
        'room_width',
        'description',
        'reference_image',
        'is_job_created',
        'status',
        'cancelled_by',
        'cancelled_at',
        'cancel_reason'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Validation Rules
     */
    protected $validationRules = [
        'booking_id'      => 'required|integer',
        'service_id'      => 'permit_empty|integer',
        'service_type_id' => 'permit_empty|integer',
        'room_id'         => 'permit_empty|integer',
        'addon_id'        => 'permit_empty|integer',
        'quantity'        => 'permit_empty|decimal',
        'unit'            => 'permit_empty|string|max_length[50]',
        'rate'            => 'permit_empty|decimal',
        'amount'          => 'permit_empty|decimal',
        'room_length'     => 'permit_empty|decimal',
        'room_width'      => 'permit_empty|decimal',
        'description'     => 'permit_empty|string',
        'reference_image' => 'permit_empty|string',
        'parent_booking_service_id' => 'permit_empty|integer',
    ];

    /**
     * Custom Validation Messages
     */
    protected $validationMessages = [
        'booking_id'      => ['required' => 'Booking ID is required.', 'integer' => 'Booking ID must be a number.'],
        'service_id'      => ['integer' => 'Service ID must be a number.'],
        'service_type_id' => ['integer' => 'Service Type ID must be a number.'],
        'room_id'         => ['integer' => 'Room ID must be a number.'],
        'addon_id'        => ['integer' => 'Addon ID must be a number.'],
        'quantity'        => ['decimal' => 'Quantity must be a decimal number.'],
        'unit'            => ['max_length' => 'Unit cannot exceed 50 characters.'],
        'rate'            => ['decimal' => 'Rate must be a decimal number.'],
        'amount'          => ['decimal' => 'Amount must be a decimal number.'],
        'room_length'     => ['decimal' => 'Room length must be a decimal number.'],
        'room_width'      => ['decimal' => 'Room width must be a decimal number.'],
        'parent_booking_service_id' => ['integer' => 'Parent booking service ID must be a number.'],
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
