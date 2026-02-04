<?php

namespace App\Models;

use CodeIgniter\Model;

class PartnerWithdrawalRequestModel extends Model
{
    protected $table = 'partner_withdrawal_requests';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $dateFormat = 'datetime';
    protected $allowedFields = [
        'partner_id',
        'requested_amount',
        'status',
        'requested_at',
        'approved_by_admin_id',
        'approved_at',
        'rejected_reason',
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
