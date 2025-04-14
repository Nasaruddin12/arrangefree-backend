<?php

namespace App\Models;

use CodeIgniter\Model;

class ServiceTypeModel extends Model
{
    protected $table      = 'service_types'; // Table name
    protected $primaryKey = 'id'; // Primary key

    protected $allowedFields = ['name', 'image', 'status']; // Columns that can be modified

    protected $useTimestamps = true; // Enables created_at and updated_at auto-fill
}
