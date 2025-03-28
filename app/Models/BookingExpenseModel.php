<?php

namespace App\Models;

use CodeIgniter\Model;

class BookingExpenseModel extends Model
{
    protected $table            = 'booking_expenses';
    protected $primaryKey       = 'id';
    protected $allowedFields    = [
        'booking_id',
        'amount',
        'category',
        'payment_method',
        'transaction_id',
        'vendor_or_client',
        'description',
        'created_at',
        'updated_at'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
