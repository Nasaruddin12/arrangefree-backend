<?php

namespace App\Models;

use CodeIgniter\Model;

class RoomElementModel extends Model
{
    protected $table      = 'room_elements';
    protected $primaryKey = 'id';

    protected $allowedFields = ['title', 'type', 'created_at', 'updated_at'];
    protected $useTimestamps = true;
}
