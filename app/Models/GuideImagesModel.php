<?php

namespace App\Models;

use CodeIgniter\Model;

class GuideImagesModel extends Model
{
    protected $table            = 'guide_images';
    protected $primaryKey       = 'id';
    protected $allowedFields    = ['service_type_id', 'room_id', 'title', 'description', 'image_url', 'created_at', 'updated_at'];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
}
