<?php

namespace App\Models;

use CodeIgniter\Model;

class ServiceProcessStepModel extends Model
{
    protected $table = 'service_process_steps';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'service_id',
        'step_title',
        'step_description',
        'step_order',
        'estimated_time',
        'icon',
        'status',
    ];

    protected $useTimestamps = true;

    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $returnType = 'array';
}