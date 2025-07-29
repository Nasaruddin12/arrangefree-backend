<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\PartnerReferralModel;
use App\Models\PartnerModel;

class PartnerReferralController extends BaseController
{
    public function summary($partner_id)
    {
        $referralModel = new PartnerReferralModel();
        $partnerModel  = new PartnerModel();

        // Get all referrals made by this partner
        $referrals = $referralModel
            ->where('referrer_id', $partner_id)
            ->orderBy('created_at', 'DESC')
            ->findAll();

        $responseReferrals = [];
        $responsePayouts   = [];

        $total_bonus_earned  = 0;
        $total_bonus_paid    = 0;
        $total_bonus_pending = 0;

        foreach ($referrals as $ref) {
            $referee = $partnerModel->find($ref['referee_id']);
            if (!$referee) continue;

            $bonusAmount = (float) $ref['bonus_amount'];
            $status      = $ref['bonus_status'];

            $total_bonus_earned += $bonusAmount;

            if ($status === 'paid') {
                $total_bonus_paid += $bonusAmount;

                $responsePayouts[] = [
                    'id'     => $ref['id'],
                    'amount' => $bonusAmount,
                    'date'   => date('Y-m-d', strtotime($ref['paid_at'] ?? $ref['updated_at'] ?? $ref['created_at'])),
                    'status' => 'paid',
                ];
            } elseif ($status === 'pending') {
                $total_bonus_pending += $bonusAmount;

                $responsePayouts[] = [
                    'id'     => $ref['id'],
                    'amount' => $bonusAmount,
                    'date'   => date('Y-m-d', strtotime($ref['updated_at'] ?? $ref['created_at'])),
                    'status' => 'pending',
                ];
            }

            $responseReferrals[] = [
                'id'           => $referee['id'],
                'name'         => $referee['name'],
                'mobile'       => $referee['mobile'] ?? '',
                'joined_at'    => date('Y-m-d', strtotime($referee['created_at'] ?? '')),
                'status'       => $referee['status'] ?? 'registered',
                'bonus_amount' => $bonusAmount,
                'bonus_status' => $status,
            ];
        }

        return $this->response->setJSON([
            'total_referred'       => count($responseReferrals),
            'total_bonus_earned'   => $total_bonus_earned,
            'total_bonus_paid'     => $total_bonus_paid,
            'total_bonus_pending'  => $total_bonus_pending,
            'referrals'            => $responseReferrals,
            'payouts'              => $responsePayouts,
        ]);
    }
}
