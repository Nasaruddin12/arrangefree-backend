<?php

namespace App\Models;

use CodeIgniter\Model;

class ChannelPartnerWalletTransactionModel extends Model
{
    protected $table            = 'channel_partner_wallet_transactions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = null;
    protected $dateFormat       = 'datetime';

    protected $allowedFields = [
        'channel_partner_id',
        'source_type',
        'source_id',
        'amount',
        'is_credit',
        'note',
    ];
}
