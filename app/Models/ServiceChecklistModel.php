<?php

namespace App\Models;

use CodeIgniter\Model;

class ServiceChecklistModel extends Model
{
    protected $table      = 'service_checklists';
    protected $primaryKey = 'id';
    protected $allowedFields = ['service_id', 'title', 'is_required', 'sort_order', 'created_at', 'updated_at'];
    protected $useTimestamps = true;
}
