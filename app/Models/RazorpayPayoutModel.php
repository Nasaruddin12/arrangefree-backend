<?php

namespace App\Models;

use CodeIgniter\Model;

class RazorpayPayoutModel extends Model
{
    protected $table = 'razorpay_payouts';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $dateFormat = 'datetime';
    protected $allowedFields = [
        'partner_payout_id',
        'razorpay_order_id',
        'razorpay_fund_account_id',
        'amount',
        'currency',
        'status',
        'failure_reason',
        'gateway_response'
    ];

    protected $validationRules = [];
    protected $validationMessages = [];
    protected $skipValidation = false;

    protected $callbacks = [
        'beforeInsert' => [],
        'afterInsert' => [],
        'beforeUpdate' => [],
        'afterUpdate' => [],
        'beforeDelete' => [],
        'afterDelete' => [],
    ];
}
