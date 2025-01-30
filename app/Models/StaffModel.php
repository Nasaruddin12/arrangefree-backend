<?php

namespace App\Models;

use CodeIgniter\Model;

class StaffModel extends Model
{
    protected $table            = 'staff';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'name',
        'email',
        'mobile_no',
        'salary',
        'aadhar_no',
        'pan_no',
        'joining_date',
        'relieving_date',
        'designation',
        'pan_card',
        'aadhar_card',
        'photo',
        'joining_letter',
        'status'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'name'         => 'required|max_length[255]',
        'email'        => 'required|valid_email|is_unique[staff.email,id,{id}]',
        'mobile_no'    => 'required|max_length[15]',
        'salary'       => 'required|decimal',
        'aadhar_no'    => 'required|exact_length[12]|is_unique[staff.aadhar_no,id,{id}]',
        'pan_no'       => 'required|exact_length[10]|is_unique[staff.pan_no,id,{id}]',
        'joining_date' => 'required|valid_date',
        'designation'  => 'required|max_length[100]',
        'status'       => 'in_list[active,inactive]'
    ];

    protected $validationMessages = [
        'name' => [
            'required'   => 'Name is required.',
            'max_length' => 'Name cannot exceed 255 characters.'
        ],
        'email' => [
            'required'    => 'Email is required.',
            'valid_email' => 'Please provide a valid email address.',
            'is_unique'   => 'This email is already in use.'
        ],
        'mobile_no' => [
            'required'   => 'Mobile number is required.',
            'max_length' => 'Mobile number cannot exceed 15 characters.'
        ],
        'salary' => [
            'required' => 'Salary is required.',
            'decimal'  => 'Salary must be a valid decimal number.'
        ],
        'aadhar_no' => [
            'required'    => 'Aadhar number is required.',
            'exact_length' => 'Aadhar number must be exactly 12 digits.',
            'is_unique'   => 'This Aadhar number is already registered.'
        ],
        'pan_no' => [
            'required'    => 'PAN number is required.',
            'exact_length' => 'PAN number must be exactly 10 characters.',
            'is_unique'   => 'This PAN number is already registered.'
        ],
        'joining_date' => [
            'required'   => 'Joining date is required.',
            'valid_date' => 'Please provide a valid date.'
        ],
        'designation' => [
            'required'   => 'Designation is required.',
            'max_length' => 'Designation cannot exceed 100 characters.'
        ],
        'status' => [
            'in_list' => 'Status must be either active or inactive.'
        ]
    ];

    protected $skipValidation = true;
}
