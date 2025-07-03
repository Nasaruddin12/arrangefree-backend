<?php

namespace App\Models;

use CodeIgniter\Model;

class PartnerBankDetailModel extends Model
{
    protected $table            = 'partner_bank_details';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'partner_id',
        'account_holder_name',
        'bank_name',
        'bank_branch',
        'account_number',
        'ifsc_code',
        'bank_document',
        'status',
        'rejection_reason',
        'verified_by',
        'verified_at'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'partner_id'           => 'required|is_natural_no_zero',
        'account_holder_name'  => 'required|min_length[3]',
        'bank_name'            => 'required',
        'bank_branch'          => 'required',
        'account_number'       => 'required|numeric|min_length[6]',
        'ifsc_code'            => 'required|alpha_numeric|exact_length[11]',
        'bank_document'        => 'permit_empty',
        'status'               => 'required|in_list[pending,verified,rejected]',
    ];

    protected $validationMessages = [
        'ifsc_code' => ['exact_length' => 'IFSC Code must be 11 characters.'],
        'account_number' => ['numeric' => 'Account number must be numeric.'],
        'account_holder_name' => ['min_length' => 'Account holder name must be at least 3 characters long.'],
        'bank_name' => ['required' => 'Bank name is required.'],
        'bank_branch' => ['required' => 'Bank branch is required.'],
        'account_number' => ['required' => 'Account number is required.'],
        'ifsc_code' => ['required' => 'IFSC code is required.'],
        'bank_document' => ['required' => 'Bank document is required.'],
        'status' => ['required' => 'Status is required.'],
        'partner_id' => ['required' => 'Partner ID is required.'],
        'partner_id' => ['is_natural_no_zero' => 'Partner ID must be a valid number.'], 
        'status' => ['in_list' => 'Status must be one of the following: pending, verified, rejected.'],
    ];
}
