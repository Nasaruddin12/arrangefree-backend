<?php

namespace App\Models;

use CodeIgniter\Model;

class ImageCollectionModel extends Model
{
    protected $table = 'image_collections';
    protected $primaryKey = 'id';
    protected $allowedFields = ['title', 'images', 'created_at', 'updated_at'];

    // Automatically convert JSON field to array when fetching
    protected $casts = [
        'images' => 'json'
    ];

    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
