<?php

namespace App\Models;

use CodeIgniter\Model;

class PartnerAddressModel extends Model
{
    protected $table            = 'partner_addresses';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $allowedFields = [
        'partner_id',
        'address_line_1',
        'address_line_2',
        'landmark',
        'pincode',
        'city',
        'state',
        'country',
        'is_primary',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'partner_id'      => 'required|integer',
        'address_line_1'  => 'required|min_length[5]',
        'pincode'         => 'required|numeric|min_length[6]|max_length[6]',
        'city'            => 'required',
        'state'           => 'required',
        'country'         => 'permit_empty',
        'is_primary'      => 'in_list[0,1]',
    ];

    protected $validationMessages = [
        'partner_id' => [
            'required' => 'Partner ID is required.',
        ],
        'address_line_1' => [
            'required' => 'Address Line 1 is required.',
            'min_length' => 'Address must be at least 5 characters.',
        ],
        'pincode' => [
            'required' => 'Pincode is required.',
            'numeric'  => 'Pincode must be numeric.',
            'min_length' => 'Pincode must be 6 digits.',
            'max_length' => 'Pincode must be 6 digits.',
        ],
        'city' => ['required' => 'City is required.'],
        'state' => ['required' => 'State is required.'],
        'is_primary' => ['in_list' => 'Primary flag must be 0 or 1.'],
    ];
}
