<?php

namespace App\Models;

use CodeIgniter\Model;

class PartnerOtpModel extends Model
{
    protected $table            = 'partner_otps';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'mobile',
        'otp',
        'expires_at',
        'otp_blocked_until'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
