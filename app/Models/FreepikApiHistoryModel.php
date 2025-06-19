<?php

namespace App\Models;

use CodeIgniter\Model;

class FreepikApiHistoryModel extends Model
{
    protected $table      = 'freepik_api_history';
    protected $primaryKey = 'id';
    protected $allowedFields = ['user_id', 'prompt', 'images', 'type', 'created_at'];

    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';

    protected $casts = [
        'images' => 'json'
    ];
}
