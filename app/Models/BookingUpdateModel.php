<?php

namespace App\Models;

use CodeIgniter\Model;

class BookingUpdateModel extends Model
{
    protected $table            = 'booking_updates';
    protected $primaryKey       = 'id';
    protected $allowedFields    = [
        'booking_service_id',
        'partner_id',
        'message',
        'status',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps    = true;
}
