<?php

namespace App\Models;

use CodeIgniter\Model;

class AIAPIHistoryModel extends Model
{
    protected $table            = 'ai_api_history';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $allowedFields    = [
        'user_id',
        'api_endpoint',
        'request_data',
        'response_data',
        'status_code',
        'created_at'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = null; // No updates needed for history logs
}
