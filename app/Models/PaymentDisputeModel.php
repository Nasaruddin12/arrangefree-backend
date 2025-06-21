<?php

namespace App\Models;

use CodeIgniter\Model;

class PaymentDisputeModel extends Model
{
    protected $table      = 'payment_disputes';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $allowedFields = [
        'payment_id',
        'booking_id',
        'status',
        'reason',
        'payload',
    ];
}
