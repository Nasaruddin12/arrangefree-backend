<?php

namespace App\Models;

use CodeIgniter\Database\Exceptions\DataException;
use CodeIgniter\Model;

class BookingAssignmentRequestModel extends Model
{
    protected $table            = 'booking_assignment_requests';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;

    protected $allowedFields    = [
        'booking_service_id',
        'partner_id',
        'status',
        'sent_at',
        'accepted_at',
        'created_at',
        'updated_at'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // 🔹 Get all active requests for a service
    public function getActiveRequests($bookingServiceId)
    {
        return $this->where('booking_service_id', $bookingServiceId)
            ->where('status', 'pending')
            ->findAll();
    }

    // 🔹 Partner claims the task (first accept logic)
    public function claimFirst($bookingServiceId, $partnerId)
    {
        $db = \Config\Database::connect();
        $db->transStart();

        // Lock the row
        $existing = $this->where('booking_service_id', $bookingServiceId)
            ->where('status', 'pending')
            ->where('partner_id', $partnerId)
            ->set(['status' => 'accepted', 'accepted_at' => date('Y-m-d H:i:s')])
            ->update();

        if ($this->db->affectedRows() === 0) {
            $db->transComplete();
            throw new DataException('Request already claimed or expired.');
        }

        // Expire others
        $this->where('booking_service_id', $bookingServiceId)
            ->where('status', 'pending')
            ->where('partner_id !=', $partnerId)
            ->set(['status' => 'expired'])
            ->update();

        $db->transComplete();
        return true;
    }
}
