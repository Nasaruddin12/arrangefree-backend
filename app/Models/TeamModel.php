<?php

namespace App\Models;

use CodeIgniter\Model;

class TeamModel extends Model
{
    protected $table      = 'teams';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'name',
        'mobile',
        'age',
        'work',
        'labour_count',
        'area',
        'service_areas',
        'aadhaar_no',
        'aadhaar_front',
        'aadhaar_back',
        'pan_no',
        'pan_file',
        'address_proof',
        'photo',
    ];

    protected $useTimestamps = true;

    // Validation Rules
    protected $validationRules = [
        'name'             => 'required|min_length[3]|max_length[255]',
        'mobile'           => 'required|regex_match[/^[0-9]{10}$/]|is_unique[teams.mobile]',
        'age'              => 'required|integer|greater_than_equal_to[18]',
        'work'             => 'required|min_length[3]|max_length[255]',
        'labour_count'     => 'required|integer|greater_than_equal_to[0]',
        'area'             => 'required|min_length[3]|max_length[255]',
        'service_areas'    => 'required',
        'aadhaar_no'       => 'required|regex_match[/^[0-9]{12}$/]|is_unique[teams.aadhaar_no]',
        'aadhaar_front'    => 'required',
        'aadhaar_back'     => 'required',
        'pan_no'           => 'required|regex_match[/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/]|is_unique[teams.pan_no]',
        'pan_file'         => 'required',
        'address_proof'    => 'required',
        'photo'            => 'required',
    ];

    protected $validationMessages = [
        'mobile' => [
            'regex_match' => 'Mobile number must be exactly 10 digits.',
            'is_unique'   => 'This mobile number is already registered.'
        ],
        'aadhaar_no' => [
            'regex_match' => 'Aadhaar number must be exactly 12 digits.',
            'is_unique'   => 'This Aadhaar number is already registered.'
        ],
        'pan_no' => [
            'regex_match' => 'PAN number must be in the valid format (e.g., ABCDE1234F).',
            'is_unique'   => 'This PAN number is already registered.'
        ],
        'age' => [
            'greater_than_equal_to' => 'Age must be 18 or above.'
        ],
        'labour_count' => [
            'greater_than_equal_to' => 'Labour count cannot be negative.'
        ]
    ];
}
