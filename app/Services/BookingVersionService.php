<?php

namespace App\Services;

use App\Models\BookingsModel;
use App\Models\BookingServicesModel;
use App\Models\BookingVersionsModel;
use App\Models\BookingServiceVersionsModel;

class BookingVersionService
{
    protected $bookingModel;
    protected $bookingServiceModel;
    protected $versionModel;
    protected $serviceVersionModel;

    public function __construct()
    {
        $this->bookingModel        = new BookingsModel();
        $this->bookingServiceModel = new BookingServicesModel();
        $this->versionModel        = new BookingVersionsModel();
        $this->serviceVersionModel = new BookingServiceVersionsModel();
    }

    /**
     * Create a booking version snapshot
     */
    public function create(
        int $bookingId,
        string $reason,
        string $changedBy = 'user',
        ?int $adminId = null,
        ?int $partnerId = null
    ): int {
        // Validation
        if ($changedBy === 'admin' && !$adminId) {
            throw new \RuntimeException('admin_id required');
        }

        if ($changedBy === 'partner' && !$partnerId) {
            throw new \RuntimeException('partner_id required');
        }

        $booking = $this->bookingModel->find($bookingId);
        if (!$booking) {
            throw new \RuntimeException('Booking not found');
        }

        // Next version number
        $versionNo = $this->versionModel->getLatestVersionNo($bookingId) + 1;

        // Insert booking snapshot
        $this->versionModel->insert([
            'booking_id'    => $bookingId,
            'version_no'    => $versionNo,
            'total_amount'  => $booking['total_amount'],
            'discount'      => $booking['discount'],
            'cgst'          => $booking['cgst'],
            'sgst'          => $booking['sgst'],
            'final_amount'  => $booking['final_amount'],
            'paid_amount'   => $booking['paid_amount'],
            'amount_due'    => $booking['amount_due'],
            'payment_type'  => $booking['payment_type'],
            'status'        => $booking['status'],
            'change_reason' => $reason,
            'changed_by'    => $changedBy,
            'admin_id'      => $adminId,
            'partner_id'    => $partnerId,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        $versionId = $this->versionModel->insertID();

        // Snapshot services
        $services = $this->bookingServiceModel
            ->where('booking_id', $bookingId)
            ->findAll();

        foreach ($services as $service) {
            $this->serviceVersionModel->insert([
                'booking_version_id' => $versionId,
                'service_id'         => $service['service_id'],
                'service_type_id'    => $service['service_type_id'],
                'room_id'            => $service['room_id'],
                'rate_type'          => $service['rate_type'],
                'value'              => $service['value'],
                'rate'               => $service['rate'],
                'amount'             => $service['amount'],
                'addons'             => $service['addons'],
                'created_at'         => date('Y-m-d H:i:s'),
            ]);
        }

        return $versionId;
    }
}
