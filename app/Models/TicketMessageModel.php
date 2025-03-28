<?php

namespace App\Models;

use CodeIgniter\Model;

class TicketMessageModel extends Model
{
    protected $table = 'ticket_messages';
    protected $primaryKey = 'id';
    protected $allowedFields = ['ticket_id', 'sender_id', 'message', 'created_at'];
    protected $useTimestamps = true;
}
