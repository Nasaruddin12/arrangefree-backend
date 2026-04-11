<?php

namespace App\Models;

use CodeIgniter\Model;

class ChannelPartnerModel extends Model
{
    protected $table            = 'channel_partners';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $useTimestamps    = true;
    protected $dateFormat       = 'datetime';
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
    protected $deletedField     = 'deleted_at';

    protected $allowedFields = [
        'name',
        'company_name',
        'email',
        'mobile',
        'password',
        'otp',
        'otp_expires_at',
        'email_verified',
        'mobile_verified',
        'is_logged_in',
        'status',
        'fcm_token',
        'last_login_at',
    ];

    protected $validationRules = [
        'id'       => 'if_exist|numeric',
        'name'     => 'required|min_length[3]|max_length[100]',
        'company_name' => 'permit_empty|max_length[150]',
        'email'    => 'permit_empty|valid_email|is_unique[channel_partners.email,id,{id}]',
        'mobile'   => 'required|regex_match[/^[0-9]{10}$/]|is_unique[channel_partners.mobile,id,{id}]',
        'password' => 'permit_empty|min_length[6]|max_length[255]',
        'otp'      => 'permit_empty|max_length[255]',
        'status'   => 'permit_empty|in_list[pending,active,inactive,blocked]',
    ];

    protected $validationMessages = [
        'name' => [
            'required'   => 'Name is required.',
            'min_length' => 'Name must be at least 3 characters long.',
        ],
        'email' => [
            'valid_email' => 'Please enter a valid email address.',
            'is_unique'   => 'This email is already registered.',
        ],
        'mobile' => [
            'required'    => 'Mobile number is required.',
            'regex_match' => 'Enter a valid 10-digit mobile number.',
            'is_unique'   => 'This mobile number is already registered.',
        ],
        'password' => [
            'min_length' => 'Password must be at least 6 characters long.',
        ],
        'status' => [
            'in_list' => 'Invalid channel partner status provided.',
        ],
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;
}
