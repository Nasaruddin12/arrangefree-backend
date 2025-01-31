<?php

namespace App\Models;

use CodeIgniter\Model;

class InteriorTransactionModel extends Model
{
    protected $table = 'interior_transactions'; // Table name
    protected $primaryKey = 'id';              // Primary key

    // Fields that are allowed to be inserted or updated
    protected $allowedFields = [
        'quotation_id',
        'transaction_type',
        'category',
        'amount',
        'payment_method',
        'transaction_no',
        'vendor_or_client',
        'description',
        'date',
        'remarks',
        'type',
        'created_at',
        'updated_at',
    ];

    // Automatically manage created_at and updated_at
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Validation rules for input data
    protected $validationRules = [
        'quotation_id'    => 'permit_empty|integer',
        'transaction_type' => 'required|in_list[Income,Expense]',
        'category'         => 'permit_empty|string|max_length[255]',
        'amount'           => 'required|decimal',
        'payment_method'   => 'required|in_list[Cash,Online,Cheque,Other]',
        'transaction_no'   => 'permit_empty|string|max_length[255]',
        'vendor_or_client' => 'permit_empty|string|max_length[255]',
        'description'      => 'permit_empty|string',
        'date'             => 'required|valid_date',
        'remarks'          => 'permit_empty|string',
        'type'             => 'required|in_list[Interior,Product]',
    ];

    protected $validationMessages = [
        'amount' => [
            'required' => 'The transaction amount is required.',
            'decimal'  => 'The amount must be a valid decimal number.',
        ],
        'transaction_type' => [
            'required' => 'Transaction type is required.',
            'in_list'  => 'Transaction type must be either Income or Expense.',
        ],
        'payment_method' => [
            'required' => 'Payment method is required.',
            'in_list'  => 'Payment method must be one of: Cash, Online, Cheque, Other.',
        ],
        'date' => [
            'required'    => 'The transaction date is required.',
            'valid_date'  => 'The transaction date must be a valid date.',
        ],
        'transaction_type' => [
            'required' => 'Type is required.',
            'in_list'  => 'Type must be either Interior or Product.',
        ],
    ];
}
