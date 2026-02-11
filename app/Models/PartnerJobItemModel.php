<?php

namespace App\Models;

use CodeIgniter\Model;

class PartnerJobItemModel extends Model
{
    protected $table            = 'partner_job_items';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $allowedFields = [
        'partner_job_id',
        'parent_item_id',
        'service_source',
        'source_id',
        'room_id',
        'with_material',
        'title',
        'quantity',
        'unit',
        'rate',
        'amount',
        'status',
        'checklist_status',
        'cancelled_by',
        'cancel_reason'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = null;
    protected $dateFormat    = 'datetime';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
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
