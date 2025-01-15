<?php

namespace App\Models;

use CodeIgniter\Model;

class ComplaintsModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'af_complaints';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        "user_id",
        "description",
        "product_id",
        "order_id",
        "status"
    ];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';


    protected $validationRules      = [
        'user_id'          => 'required',
        'description'    => 'required',
        'product_id'          => 'required',
        'order_id'    => 'required',
    ];
    protected $validationMessages   = [
        'user_id' => [
            'required' => 'The user_id field is required.',
        ],
        'description' => [
            'required' => 'The description field is required.',
        ],
        'product_id' => [
            'required' => 'The product_id field is required.',
        ],
        'order_id' => [
            'required' => 'The order_id field is required.',
        ],
    ];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;
}
