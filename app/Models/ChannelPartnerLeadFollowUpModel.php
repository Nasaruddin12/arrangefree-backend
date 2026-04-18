<?php

namespace App\Models;

use CodeIgniter\Model;

class ChannelPartnerLeadFollowUpModel extends Model
{
    protected $table            = 'channel_partner_lead_follow_ups';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $useTimestamps    = false;
    protected $dateFormat       = 'datetime';

    protected $allowedFields = [
        'lead_id',
        'assigned_admin_id',
        'admin_id',
        'previous_status',
        'status',
        'next_follow_up_at',
        'note',
        'created_at',
    ];
}
