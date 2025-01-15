<?php

namespace App\Models;

use CodeIgniter\Model;

class StaffsModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'drf_staff';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = FALSE;
    protected $protectFields    = true;
    protected $allowedFields    = ["username", "password", "name", "phone", "email", "aadhaar_no", "pan_no", "adhaar_file", "pan_file", "status"];



    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules = [
        'username'     => 'required',
        'password'     => 'required',
        'name'     => 'required',
        'phone'     => 'required',
        'email'        => 'required',
        'aadhaar_no'        => 'required',
        'pan_no'        => 'required',
        'adhaar_file'        => 'required',
        'pan_file'        => 'required',
    ];
    // 'is_unique' => 'Sorry. That Email has already been taken. Please choose another.',
    // 'is_unique' => 'Sorry. That phone has already been taken. Please choose another.',
    protected $validationMessages = [
        'email' => [
            'required' => 'Sorry. The Email Field Is Required',
        ],
        'phone' => [
            'required' => 'Sorry. The phone Field Is Required',
        ],
        'username' => [
            'required' => 'Sorry. The username Field Is Required',
        ],
        'password' => [
            'required' => 'Sorry. The password Field Is Required',
        ],
        'name' => [
            'required' => 'Sorry. The name Field Is Required',
        ],
        'aadhaar_no' => [
            'required' => 'Sorry. The aadhaar_no Field Is Required',
        ],
        'pan_no' => [
            'required' => 'Sorry. The pan_no Field Is Required',
        ],
        'adhaar_file' => [
            'required' => 'Sorry. The adhaar_file Field Is Required',
        ],
        'pan_file' => [
            'required' => 'Sorry. The adhaar_file Field Is Required',
        ],
    ];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;
}
