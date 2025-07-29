<?php

namespace App\Models;

use CodeIgniter\Model;

class PartnerReferralInviteModel extends Model
{
    protected $table      = 'partner_referral_invites';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'referrer_id',
        'friend_name',
        'friend_mobile',
        'referral_code',
        'is_registered',
    ];

    protected $useTimestamps = true;
}
