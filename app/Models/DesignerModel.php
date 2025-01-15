<?php

namespace App\Models;

use CodeIgniter\Model;

class DesignerModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'af_designer';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = ["id", "employee_id", "name", "pan_number", "adhaar_number", "pan_card", "adhaar_card", "agreement", "status", "deleted_at"];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules      = [
        'employee_id'          => 'required',
        'name'          => 'required',
        'pan_number'    => 'required|is_unique[af_designer.pan_number]',
        'adhaar_number'   => 'required|is_unique[af_designer.adhaar_number]',
        'agreement'     => 'required',
        'pan_card'     => 'required',
        'adhaar_card'     => 'required',
    ];
    protected $validationMessages   = [
        'employee_id' => [
            'required' => 'The employee_id field is required.',
        ],
        'name' => [
            'required' => 'The Name field is required.',
        ],
        'pan_number' => [
            'required' => 'The PAN Number field is required.',
            'is_unique' => 'The PAN Number must be unique.',
        ],
        'adhaar_number' => [
            'required' => 'The Aadhaar Card field is required.',
            'is_unique' => 'The Aadhaar Card must be unique.',
        ],
        'agreement' => [
            'required' => 'The Agreement field is required.',
        ],
        'adhaar_card' => [
            'required' => 'The adhaar_card field is required.',
        ],
        'pan_card' => [
            'required' => 'The pan_card field is required.',
        ],
    ];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;
}
