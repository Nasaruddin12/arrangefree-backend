<?php

namespace App\Models;

use CodeIgniter\Model;

class TicketModel extends Model
{
    protected $table = 'tickets';
    protected $primaryKey = 'id';
    protected $allowedFields = ['user_id', 'subject', 'status', 'created_at', 'updated_at'];
    protected $useTimestamps = true;
}
