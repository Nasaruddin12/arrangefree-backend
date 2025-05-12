<?php

namespace App\Models;

use CodeIgniter\Model;

class ServiceAddonModel extends Model
{
    protected $table            = 'service_addons';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'service_id',
        'group_name',
        'is_required',
        'name',
        'price_type',
        'qty',
        'price',
        'description',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'service_id'   => 'required|integer',
        'name'         => 'required|string',
        'price_type'   => 'in_list[unit,square_feet]',
        'qty'          => 'required|decimal',
        'price'        => 'required|decimal',
    ];

    protected $validationMessages = [
        'name' => [
            'required' => 'Addon name is required.'
        ],
        'price' => [
            'required' => 'Price is required.',
            'decimal'  => 'Price must be a valid number.'
        ]
    ];
}
