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
        'gender',
        'profession',
        'team_size',
        'emergency_contact',
        'service_areas',
        'aadhaar_no',
        'pan_no',
        'documents_verified',
        'bank_verified',
        'verified_by',
        'verified_at',
        'status'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'name'             => 'required|min_length[3]',
        'mobile'           => 'required|regex_match[/^[0-9]{10}$/]|is_unique[partners.mobile]',
        'dob'              => 'required|valid_date|check_age',
        'gender'           => 'required|in_list[male,female,other]',
        'profession'       => 'required',
        'team_size'        => 'required',
        'emergency_contact' => 'permit_empty|regex_match[/^[0-9]{10}$/]',
        'service_areas'    => 'required',
        'aadhaar_no'       => 'required|regex_match[/^[0-9]{12}$/]|is_unique[partners.aadhaar_no]',
        'pan_no'           => 'required|regex_match[/^[A-Z]{5}[0-9]{4}[A-Z]$/]|is_unique[partners.pan_no]',
        'status'           => 'permit_empty|in_list[pending,active,blocked,terminated,resigned,rejected]',
    ];

    protected $validationMessages = [
        'name' => ['required' => 'Name is required.'],

        'mobile' => [
            'required'    => 'Mobile number is required.',
            'regex_match' => 'Enter a valid 10-digit mobile number.',
            'is_unique'   => 'This mobile number is already registered.',
        ],

        'dob' => [
            'required'  => 'Date of birth is required.',
            'check_age' => 'You must be at least 18 years old.',
        ],

        'gender' => [
            'required' => 'Gender is required.',
            'in_list'  => 'Gender must be male, female, or other.',
        ],

        'emergency_contact' => [
            'regex_match' => 'Enter a valid 10-digit contact number.',
        ],

        'aadhaar_no' => [
            'regex_match' => 'Aadhaar number must be a 12-digit numeric value.',
            'is_unique'   => 'This Aadhaar number is already registered.'
        ],

        'pan_no' => [
            'regex_match' => 'PAN number must follow the format: 5 uppercase letters, 4 digits, 1 uppercase letter.',
            'is_unique'   => 'This PAN number is already registered.'
        ],
    ];

    /**
     * Adjust uniqueness rules when updating
     */
    protected function beforeValidate(array $data): array
    {
        if (!empty($data['data']['id'])) {
            $id = $data['data']['id'];

            $this->validationRules['mobile']     = 'required|regex_match[/^[0-9]{10}$/]|is_unique[partners.mobile,id,' . $id . ']';
            $this->validationRules['aadhaar_no'] = 'required|regex_match[/^[0-9]{12}$/]|is_unique[partners.aadhaar_no,id,' . $id . ']';
            $this->validationRules['pan_no']     = 'required|regex_match[/^[A-Z]{5}[0-9]{4}[A-Z]$/]|is_unique[partners.pan_no,id,' . $id . ']';
        }

        return $data;
    }
}
