<?php

namespace App\Models;

use CodeIgniter\Model;

class StylesCategoryModel extends Model
{
    protected $table            = 'styles_category';
    protected $primaryKey       = 'id';

    protected $allowedFields    = [
        'name',
        'image',
        'status',
    ];

    protected $useTimestamps    = true;
    protected $useSoftDeletes   = true;

    protected $returnType       = 'array'; // or 'object' if you prefer
}
