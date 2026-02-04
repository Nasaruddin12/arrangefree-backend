<?php

namespace App\Models;

use CodeIgniter\Model;

class PartnerPayoutModel extends Model
{
    protected $table = 'partner_payouts';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $dateFormat = 'datetime';
    protected $allowedFields = [
        'partner_id',
        'amount',
        'payout_mode',
        'transaction_reference',
        'status',
        'initiated_by',
        'initiated_by_id',
        'initiated_at',
        'completed_at',
        'note'
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
