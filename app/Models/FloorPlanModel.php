<?php

namespace App\Models;

use CodeIgniter\Model;

class FloorPlanModel extends Model
{
    protected $table      = 'floor_plans';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;
    protected $useSoftDeletes   = true;

    protected $allowedFields = [
        'user_id',
        'room_name',
        'room_size',
        'name',
        'primary_color',
        'accent_color',
        'style_name',
        'floorplan_image',
        'floor3d_image',
        'elements_json',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}
