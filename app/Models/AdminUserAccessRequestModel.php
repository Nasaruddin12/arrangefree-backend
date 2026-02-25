<?php

namespace App\Models;

use CodeIgniter\Model;

class AdminUserAccessRequestModel extends Model
{
    protected $table = 'admin_user_access_requests';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'admin_id',
        'user_id',
        'access_type',
        'reason',
        'status',
        'expires_at',
        'approved_at'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $returnType = 'array';
}