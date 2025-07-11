<?php

namespace App\Models;

use CodeIgniter\Model;

class TicketMessageModel extends Model
{
    protected $table = 'ticket_messages';
    protected $primaryKey = 'id';
    protected $allowedFields = ['ticket_id', 'sender_type', 'sender_id', 'message', 'file', 'is_read_by_admin', 'is_read_by_user', 'created_at'];
    // protected $useTimestamps = true;
}
