<?php

namespace App\Models;

use CodeIgniter\Model;

class BookingServiceVersionsModel extends Model
{
    protected $table            = 'booking_service_versions';
    protected $primaryKey       = 'id';

    protected $allowedFields    = [
        'booking_version_id',
        'service_id',
        'service_type_id',
        'room_id',
        'rate_type',
        'value',
        'rate',
        'amount',
        'addons',
        'created_at'
    ];

    protected $useTimestamps = false;
}
