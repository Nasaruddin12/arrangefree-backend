<?php

namespace App\Models;

use CodeIgniter\Model;

class ChannelPartnerBankDetailModel extends Model
{
    protected $table            = 'channel_partner_bank_details';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $allowedFields = [
        'channel_partner_id',
        'account_holder_name',
        'bank_name',
        'bank_branch',
        'account_number',
        'ifsc_code',
        'upi_id',
        'bank_document',
        'status',
        'rejection_reason',
        'verified_by',
        'verified_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $dateFormat    = 'datetime';

    protected $validationRules = [
        'channel_partner_id'  => 'required|is_natural_no_zero',
        'account_holder_name' => 'permit_empty|min_length[3]',
        'bank_name'           => 'permit_empty',
        'bank_branch'         => 'permit_empty',
        'account_number'      => 'permit_empty|numeric|min_length[6]',
        'ifsc_code'           => 'permit_empty|alpha_numeric|exact_length[11]',
        'upi_id'              => 'permit_empty|max_length[100]',
        'bank_document'       => 'permit_empty',
        'status'              => 'required|in_list[pending,verified,rejected]',
    ];

    protected $validationMessages = [
        'channel_partner_id' => [
            'required'            => 'Channel partner ID is required.',
            'is_natural_no_zero'  => 'Channel partner ID must be a valid number.',
        ],
        'account_holder_name' => [
            'min_length' => 'Account holder name must be at least 3 characters long.',
        ],
        'account_number' => [
            'numeric'  => 'Account number must be numeric.',
        ],
        'ifsc_code' => [
            'exact_length' => 'IFSC Code must be 11 characters.',
        ],
        'status' => [
            'required' => 'Status is required.',
            'in_list'  => 'Status must be one of the following: pending, verified, rejected.',
        ],
    ];
}
