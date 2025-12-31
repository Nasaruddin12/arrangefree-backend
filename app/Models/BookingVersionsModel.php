<?php

namespace App\Models;

use CodeIgniter\Model;

class BookingVersionsModel extends Model
{
    protected $table            = 'booking_versions';
    protected $primaryKey       = 'id';

    protected $allowedFields    = [
        'booking_id',
        'version_no',
        'total_amount',
        'discount',
        'cgst',
        'sgst',
        'final_amount',
        'paid_amount',
        'amount_due',
        'payment_type',
        'status',
        'change_reason',
        'changed_by',
        'admin_id',
        'partner_id',
        'created_at'
    ];

    protected $useTimestamps = false;

    /**
     * Get latest version number for a booking
     */
    public function getLatestVersionNo(int $bookingId): int
    {
        $row = $this->where('booking_id', $bookingId)
            ->orderBy('version_no', 'DESC')
            ->first();

        return $row ? (int) $row['version_no'] : 0;
    }

    /**
     * Fetch full version history with services
     */
    public function getVersionsWithServices(int $bookingId)
    {
        $versions = $this->where('booking_id', $bookingId)
            ->orderBy('version_no', 'ASC')
            ->findAll();

        $serviceVersionModel = new BookingServiceVersionsModel();

        foreach ($versions as &$version) {
            $version['services'] = $serviceVersionModel
                ->where('booking_version_id', $version['id'])
                ->findAll();
        }

        return $versions;
    }
}
