<?php

namespace App\Models;

use CodeIgniter\Model;

class AdminUserImpersonationSessionModel extends Model
{
    protected $table = 'admin_user_impersonation_sessions';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'admin_id',
        'user_id',
        'access_request_id',
        'token',
        'expires_at',
        'is_active'
    ];

    protected $useTimestamps = false;

    protected $returnType = 'array';

    public function getValidSession($token)
    {
        return $this->where('token', $token)
                    ->where('is_active', 1)
                    ->where('expires_at >=', date('Y-m-d H:i:s'))
                    ->first();
    }
}