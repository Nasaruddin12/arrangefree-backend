<?php

namespace App\Models;

use CodeIgniter\Model;

class PartnerReferralModel extends Model
{
    protected $table            = 'partner_referrals';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;

    protected $allowedFields    = [
        'referrer_id',
        'referee_id',
        'bonus_month',
        'tasks_completed',
        'required_tasks',
        'is_eligible',
        'bonus_amount',
        'bonus_status',
        'paid_at',
        'paid_txn_id'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
    protected $dateFormat    = 'datetime';

    // Optional: Custom methods

    public function findByReferrer($partnerId)
    {
        return $this->where('referrer_id', $partnerId)->findAll();
    }

    public function findPendingForMonth($month)
    {
        return $this->where('bonus_month', $month)
                    ->where('bonus_status', 'pending')
                    ->findAll();
    }

    public function markAsPaid($id, $txnId = null)
    {
        return $this->update($id, [
            'bonus_status' => 'paid',
            'paid_at'      => date('Y-m-d H:i:s'),
            'paid_txn_id'  => $txnId,
        ]);
    }
}
