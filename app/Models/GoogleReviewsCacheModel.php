<?php

namespace App\Models;

use CodeIgniter\Model;

class GoogleReviewsCacheModel extends Model
{
    protected $table      = 'google_reviews_cache';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'place_id',
        'response_json',
        'created_at',
    ];

    protected $useTimestamps = false;
}
