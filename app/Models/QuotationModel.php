<?php

namespace App\Models;

use CodeIgniter\Model;

class QuotationModel extends Model
{
    protected $table = 'quotations';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at'; 
    protected $allowedFields = [
        'customer_name',
        'phone',
        'address',
        'total',
        'discount',
        'discount_amount',
        'discount_desc',
        'sgst',
        'cgst',
        'grand_total',
        'mark_list',
        'status',
        'created_by',
    ];
}
