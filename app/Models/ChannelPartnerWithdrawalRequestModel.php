<?php

namespace App\Models;

use CodeIgniter\Model;

class ChannelPartnerWithdrawalRequestModel extends Model
{
    protected $table            = 'channel_partner_withdrawal_requests';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useTimestamps    = false;
    protected $dateFormat       = 'datetime';

    protected $allowedFields = [
        'channel_partner_id',
        'requested_amount',
        'status',
        'requested_at',
        'approved_by_admin_id',
        'approved_at',
        'rejected_reason',
        'note',
    ];
}
