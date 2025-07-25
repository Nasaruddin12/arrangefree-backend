<?php

namespace App\Models;

use CodeIgniter\Model;

class BookingUpdateMediaModel extends Model
{
    protected $table            = 'booking_update_media';
    protected $primaryKey       = 'id';
    protected $allowedFields    = [
        'booking_update_id',
        'media_type',
        'file_url',
        'label',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps    = true;
}
