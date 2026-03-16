<?php

namespace App\Models;

use CodeIgniter\Model;

class ReviewMediaModel extends Model
{
    protected $table      = 'review_media';
    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $allowedFields = [
        'review_id',
        'media_type',
        'media_url',
        'created_at'
    ];

    protected $useTimestamps = false;
}