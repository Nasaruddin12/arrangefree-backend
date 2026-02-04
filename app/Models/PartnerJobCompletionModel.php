<?php

namespace App\Models;

use CodeIgniter\Model;

class PartnerJobCompletionModel extends Model
{
    protected $table            = 'partner_job_completion';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $allowedFields = [
        'partner_job_id',
        'customer_name',
        'customer_mobile',
        'signature_image',
        'signed_at',
        'signed_lat',
        'signed_lng',
        'submitted_by_partner_id',
        'verification_status',
        'verified_by_admin_id',
        'verification_note'
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
