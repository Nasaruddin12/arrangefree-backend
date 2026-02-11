<?php

namespace App\Models;

use CodeIgniter\Model;

class PartnerJobAdditionalItemsModel extends Model
{
    protected $table            = 'partner_job_additional_items';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $allowedFields = [
        'partner_job_id',
        'booking_additional_service_id',
        'service_source',
        'source_id',
        'title',
        'quantity',
        'unit',
        'rate',
        'amount',
        'status',
        'requested_by',
        'requested_note',
        'approved_by',
        'approved_at'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
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
