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
        'room_width',
        'room_height',
        'room_length',
        'canvas_json',
        'file',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}
