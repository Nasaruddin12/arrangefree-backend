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
        'bank_document'        => 'required',
        'status'               => 'in_list[pending,verified,rejected]',
    ];

    protected $validationMessages = [
        'ifsc_code' => ['exact_length' => 'IFSC Code must be 11 characters.'],
        'account_number' => ['numeric' => 'Account number must be numeric.'],
    ];
}
