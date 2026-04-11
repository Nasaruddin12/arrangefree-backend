<?php

namespace App\Models;

use CodeIgniter\Model;

class ChannelPartnerLeadModel extends Model
{
    protected $table            = 'channel_partner_leads';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $useTimestamps    = true;
    protected $dateFormat       = 'datetime';
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $allowedFields = [
        'channel_partner_id',
        'customer_name',
        'mobile',
        'address',
        'requirement_title',
        'space_type',
        'budget',
        'notes',
        'status',
    ];

    protected $validationRules = [
        'channel_partner_id' => 'required|is_natural_no_zero',
        'customer_name'      => 'required|min_length[2]|max_length[150]',
        'mobile'             => 'required|regex_match[/^[0-9]{10}$/]',
        'address'            => 'permit_empty',
        'requirement_title'  => 'required|min_length[2]|max_length[255]',
        'space_type'         => 'permit_empty|max_length[100]',
        'budget'             => 'permit_empty|max_length[100]',
        'notes'              => 'permit_empty',
        'status'             => 'permit_empty|in_list[new,in_progress,contacted,converted,rejected]',
    ];

    protected $validationMessages = [
        'channel_partner_id' => [
            'required'           => 'Channel partner ID is required.',
            'is_natural_no_zero' => 'Channel partner ID must be valid.',
        ],
        'customer_name' => [
            'required'   => 'Customer name is required.',
            'min_length' => 'Customer name must be at least 2 characters long.',
        ],
        'mobile' => [
            'required'    => 'Mobile number is required.',
            'regex_match' => 'Enter a valid 10-digit mobile number.',
        ],
        'requirement_title' => [
            'required'   => 'Requirement title is required.',
            'min_length' => 'Requirement title must be at least 2 characters long.',
        ],
        'status' => [
            'in_list' => 'Invalid lead status.',
        ],
    ];
}
