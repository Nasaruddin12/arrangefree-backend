<?php

namespace App\Models;

use CodeIgniter\Model;

class PhonePeTransactionModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'af_phonepe_transaction';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ["merchantTransactionId", "transactionId", "payment_status", "transation_status", "amount", "form_json"];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}
