<?php

namespace App\Models;

use CodeIgniter\Model;

class PartnerWalletTransactionModel extends Model
{
    protected $table = 'partner_wallet_transactions';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = null;
    protected $dateFormat = 'datetime';
    protected $allowedFields = [
        'partner_id',
        'source_type',
        'source_id',
        'amount',
        'is_credit',
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
