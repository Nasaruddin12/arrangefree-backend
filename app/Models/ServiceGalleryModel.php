<?php

namespace App\Models;

use CodeIgniter\Model;

class ServiceGalleryModel extends Model
{
    protected $table = 'service_gallery';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $allowedFields = [
        'service_id',
        'media_type',
        'title',
        'description',
        'media_url',
        'thumbnail_url',
        'sort_order',
        'is_active',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'service_id' => 'required|integer',
        'media_type' => 'required|in_list[image,video,tutorial]',
        'media_url' => 'required|string',
        'sort_order' => 'permit_empty|integer',
        'is_active' => 'permit_empty|in_list[0,1]',
    ];

    protected $validationMessages = [
        'service_id' => [
            'required' => 'Service ID is required',
            'integer' => 'Service ID must be an integer',
        ],
        'media_type' => [
            'required' => 'Media type is required',
            'in_list' => 'Media type must be one of: image, video, tutorial',
        ],
        'media_url' => [
            'required' => 'Media URL is required',
        ],
    ];
}