<?php

namespace App\Models;

use CodeIgniter\Model;

class NotificationModel extends Model
{
    protected $table      = 'notifications';
    protected $primaryKey = 'id';

    protected $useTimestamps = true;

    protected $allowedFields = [
        'user_id', 'user_type', 'title', 'message', 'type',
        'image', 'navigation_screen', 'navigation_id', 'is_read'
    ];
}
