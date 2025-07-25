<?php

namespace App\Models;

use CodeIgniter\Model;

class PartnerPayoutModel extends Model
{
    protected $table            = 'partner_payouts';
    protected $primaryKey       = 'id';
    protected $allowedFields    = [
        'partner_id',
        'booking_service_id',
        'amount',
        'status',
        'released_at',
        'notes',
        'created_at',
        'updated_at'
    ];
    protected $useTimestamps = true;
}
