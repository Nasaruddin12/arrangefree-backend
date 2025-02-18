<?php

namespace App\Models;

use CodeIgniter\Model;

class ServiceModel extends Model
{
    protected $table      = 'services'; // Table name
    protected $primaryKey = 'id'; // Primary key

    protected $allowedFields = ['name', 'image']; // Columns that can be modified

    protected $useTimestamps = true; // Enables created_at and updated_at auto-fill
}
