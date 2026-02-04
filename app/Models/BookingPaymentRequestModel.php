<?php

namespace App\Models;

use CodeIgniter\Model;

class BookingPaymentRequestModel extends Model
{
    protected $table            = 'booking_payment_requests';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $allowedFields = [
        'booking_id',
        'user_id',
        'requested_amount',
        'currency',
        'payment_gateway',
        'razorpay_order_id',
        'status',
        'requested_by',
        'requested_at',
        'expires_at'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = null;
    protected $dateFormat    = 'datetime';

    // Validation
    protected $validationRules = [];
    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];
}
