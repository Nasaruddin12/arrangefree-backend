<?php

namespace App\Models;

use CodeIgniter\Model;

class QuotationModal extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'drf_quotation';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'customer_name',
        'phone',
        'address',
        'items',
        'mark_list',
        'total_amount',
        'sgst',
        'cgst',
        'installment',
        'time_line',
        'created_by'
    ];
}
