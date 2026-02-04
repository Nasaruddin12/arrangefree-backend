<?php

namespace App\Models;

use CodeIgniter\Model;

class PartnerJobRequestModel extends Model
{
    protected $table = 'partner_job_requests';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = null;
    protected $dateFormat = 'datetime';
    protected $allowedFields = [
        'partner_job_id',
        'partner_id',
        'status',
        'requested_by',
        'requested_by_id',
        'responded_at',
        'response_note'
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
