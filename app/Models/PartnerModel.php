<?php

namespace App\Models;

use CodeIgniter\Model;

class PartnerModel extends Model
{
    protected $table            = 'partners';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'name',
        'mobile',
        'mobile_verified',
        'dob',
        'work',
        'labour_count',
        'area',
        'service_areas',
        'aadhaar_no',
        'pan_no',
        'documents_verified',
        'bank_verified',
        'verified_by',
        'verified_at'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'name'          => 'required|min_length[3]',
        'mobile'        => 'required|regex_match[/^[0-9]{10}$/]|is_unique[partners.mobile]',
        'dob'           => 'required|valid_date|check_age',
        'work'          => 'required',
        'labour_count'  => 'required|is_natural_no_zero',
        'area'          => 'required',
        'service_areas' => 'required',
        'aadhaar_no'    => 'required|min_length[12]|is_unique[partners.aadhaar_no]',
        'pan_no'        => 'required|alpha_numeric|max_length[10]|is_unique[partners.pan_no]',
    ];
    
    protected $validationMessages = [
        'name' => ['required' => 'Name is required.'],
    
        'mobile' => [
            'required'     => 'Mobile number is required.',
            'regex_match'  => 'Enter a valid 10-digit mobile number.',
            'is_unique'    => 'This mobile number is already registered.',
        ],
    
        'dob' => [
            'required'   => 'Date of birth is required.',
            'check_age'  => 'You must be at least 18 years old.',
        ],
    
        'aadhaar_no' => [
            'required'   => 'Aadhaar number is required.',
            'is_unique'  => 'This Aadhaar number is already registered.',
        ],
    
        'pan_no' => [
            'required'   => 'PAN number is required.',
            'is_unique'  => 'This PAN number is already registered.',
        ],
    
        'labour_count' => [
            'is_natural_no_zero' => 'Labour count must be greater than 0.'
        ],
    ];
    
}
