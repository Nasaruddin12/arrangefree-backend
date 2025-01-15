<?php

namespace App\Models;

use CodeIgniter\Model;

class SellerModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'af_sellers';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['name','email','mobile_no','password','is_logged_in','otp','status'];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [
        'name'=> "required",
        'email'=> "required|valid_email|is_unique[af_sellers.email,af_sellers.id,{id}]",
        'password'=> "required|min_length[8]|max_length[21]|alpha_numeric",
        'mobile_no'=> "required|exact_length[10]|numeric",
    ];
    protected $validationMessages   = [
        'name' => [
            'required'=> "NAME IS COMPULSORY",
        ],
        'email' => [
            'required'=> "EMail IS COMPULSORY",
            'valid_email'=> "PLEASE ENTER A VALID EMAIL",
            'is_unique'=> "EMIAL IS ALREADY EXISTED",
        ],
        
        'password' => [
            'required'=> "EMail IS COMPULSORY",
            'min_length'=> "PASSWORD MUST BE GREATER THAN 5 WORDS",
            'max_length'=> "PASSWORD MUST BE LESSER THAN 13 WORDS",
            "alpha_numeric"=>"ONLY NUMBER AND ALPHABET CAN BE USED"
        ],
        'mobile_no' => [
            'required'=> "NUMBER IS COMPULSARY",
            'is_unique'=> "NUMBER IS ALREADY TAKEN",
            'exact_length'=> "NUMBER SHOULD BE IN 10 CHARECTURS",
            "numeric"=> "THIS IS NOT THE VALID NUMBER"
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
