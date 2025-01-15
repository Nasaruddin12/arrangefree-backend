<?php

namespace App\Models;

use CodeIgniter\Model;

class DesignerAssignProductModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'af_designer_assign_products';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ["designer_id", "product_id", "status"];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [
        'designer_id'   => 'required',
        'product_id'    => 'required',
    ];
    protected $validationMessages   = [
        'designer_id' => [
            'required' => 'The designer_id field is required.',
        ],
        'product_id' => [
            'required' => 'The product_id field is required.',
        ],
    ];
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
