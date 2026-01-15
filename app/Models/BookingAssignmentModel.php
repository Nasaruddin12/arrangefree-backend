<?php

namespace App\Models;

use CodeIgniter\Model;

class BookingAssignmentModel extends Model
{
    protected $table            = 'booking_assignments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;

    protected $allowedFields    = [
        'booking_service_id',
        'partner_id',
        'amount',
        'rate',
        'rate_type',
        'quantity',
        'with_material',
        'helper_count',
        'status',
        'assigned_at',
        'accepted_at',
        'estimated_start_date',
        'estimated_completion_date',
        'actual_completion_date',
        'admin_notes',
        'created_at',
        'updated_at'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    public function getWithPartner($bookingServiceId)
    {
        return $this->select('booking_assignments.*, partners.name AS partner_name')
            ->join('partners', 'partners.id = booking_assignments.partner_id')
            ->where('booking_assignments.booking_service_id', $bookingServiceId)
            ->first();
    }

    // 🔹 Check if assignment already exists
    public function isAlreadyAssigned($bookingServiceId)
    {
        return $this->where('booking_service_id', $bookingServiceId)->first() !== null;
    }
}
