<?php

namespace App\Models;

use CodeIgniter\Model;

class PartnerJobPaymentModel extends Model
{
    protected $table = 'partner_job_payments';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $dateFormat = 'datetime';
    protected $allowedFields = [
        'partner_id',
        'earning_type',
        'source_id',
        'amount',
        'status',
        'approved_by',
        'approved_by_id',
        'approved_at',
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
