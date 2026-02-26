<?php

namespace App\Models;

use CodeIgniter\Model;

class AdminUserActivityLogModel extends Model
{
    protected $table = 'admin_user_activity_logs';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'admin_id',
        'user_id',
        'action',
        'description',
        'ip_address'
    ];

    protected $useTimestamps = false;

    protected $returnType = 'array';
}