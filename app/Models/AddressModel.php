<?php

namespace App\Models;

use CodeIgniter\Model;

class AddressModel extends Model
{
    protected $table            = 'customer_addresses';
    protected $primaryKey       = 'id';
    protected $allowedFields    = ['user_id', 'house', 'address', 'landmark', 'address_label', 'is_default', 'created_at', 'updated_at'];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
}
