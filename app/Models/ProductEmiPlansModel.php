<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductEmiPlansModel extends Model
{
    protected $table = 'product_emi_plans';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'months','advance_payment_percent', 'created_at', 'updated_at'
    ];
}
