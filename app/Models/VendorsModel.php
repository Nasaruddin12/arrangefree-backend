<?php

namespace App\Models;

use CodeIgniter\Model;

class VendorsModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'drf_vendors';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'name',
        'email',
        'mobile_no',
        'company_name',
        'vendor_gst_no',
        'vendor_code',
        'vendor_address',
        'otp',
        'is_logged_in',
        'password',
        'status',
        'user_id'
    ];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [
        'name' => "required",
        'email' => "required|valid_email|is_unique[drf_vendors.email]",
        // 'password'=> "required|min_length[8]|max_length[21]|alpha_numeric",
        'mobile_no' => "required|exact_length[10]|numeric|is_unique[drf_vendors.mobile_no]",
        'company_name' => "required",
        // 'vendor_gst_no' => "required",
        // 'dealer_code' => "required",
        // 'vendor_address' => "required",
        // 'otp' => "required",
        // 'is_logged_in' => "required",
        // 'password' => "required",
        // 'status' => "required",
        'user_id' => "required",
    ];
    protected $validationMessages   = [
        'name' => [
            'required' => "NAME IS COMPULSORY",
        ],
        'user_id' => [
            'required' => "User Id IS COMPULSORY",
        ],
        'company_name' => [
            'required' => "Company Name  IS COMPULSORY",
        ],
        // 'vendor_gst_no' => [
        //     'required' => "Gst Number IS COMPULSORY",
        // ],
        // 'dealer_code' => [
        //     'required' => "Dealer Code IS COMPULSORY",
        // ],
        // 'vendor_address' => [
        //     'required' => "Vendor Address IS COMPULSORY",
        // ],
        'email' => [
            'required' => "Email IS COMPULSORY",
            'valid_email' => "PLEASE ENTER A VALID EMAIL",
            'is_unique' => "Email IS ALREADY EXISTED",
        ],

        // 'password' => [
        //     'required'=> "Password IS COMPULSORY",
        //     'min_length'=> "PASSWORD MUST BE GREATER THAN 5 WORDS",
        //     'max_length'=> "PASSWORD MUST BE LESSER THAN 13 WORDS",
        //     "alpha_numeric"=>"ONLY NUMBER AND ALPHABET CAN BE USED"
        // ],
        'mobile_no' => [
            'required' => "NUMBER IS COMPULSARY",
            'is_unique' => "NUMBER IS ALREADY TAKEN",
            'exact_length' => "NUMBER SHOULD BE IN 10 CHARECTURS",
            "numeric" => "THIS IS NOT THE VALID NUMBER"
        ]
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
