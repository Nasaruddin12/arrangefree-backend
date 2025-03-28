<?php

namespace App\Models;

use CodeIgniter\Model;

class GuideVideosModel extends Model
{
    protected $table            = 'guide_videos';
    protected $primaryKey       = 'id';
    protected $allowedFields    = ['service_type_id', 'room_id', 'title', 'description', 'video_link', 'created_at', 'updated_at'];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
}
