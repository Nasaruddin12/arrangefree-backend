<?php

namespace App\Models;

use CodeIgniter\Model;

class WorkTypeModel extends Model
{
    protected $table = 'work_types';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'service_id',
        'name',
        'image',
        'rate',
        'rate_type',
        'description',
        'materials',
        'features',
        'care_instructions',
        'warranty_details',
        'quality_promise',
        'status',
        'created_at',
        'updated_at'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
