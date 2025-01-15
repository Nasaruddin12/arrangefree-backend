<?php

namespace App\Models;

use CodeIgniter\Model;

class AppTechHeadersValueModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'af_app_text_header_value';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['header_id', 'value'];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';


    protected $validationRules      = [
        'header_id'          => 'required',
        'value'    => 'required',
    ];
    protected $validationMessages   = [
        'header_id' => [
            'required' => 'The header_id field is required.',
        ],
        'value' => [
            'required' => 'The value field is required.',
        ],
    ];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;
}
