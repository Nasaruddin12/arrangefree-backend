<?php

namespace App\Models;

use CodeIgniter\Model;

class ServiceOfferModel extends Model
{
    protected $table = 'service_offers';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'service_id',
        'category_id',
        'title',
        'discount_type',
        'discount_value',
        'start_date',
        'end_date',
        'priority',
        'is_active'
    ];
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';
}
