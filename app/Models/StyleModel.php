<?php

namespace App\Models;

use CodeIgniter\Model;

class StyleModel extends Model
{
    protected $table      = 'styles';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'name',
        'styles_category', // foreign key or reference to styles_category
        'image',
        'status',
        'created_at',
        'updated_at'
    ];

    protected $useTimestamps = true;
    protected $useSoftDeletes = false; // Enable if needed
}
