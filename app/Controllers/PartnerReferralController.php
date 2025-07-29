<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\PartnerReferralModel;
use App\Models\PartnerModel;
use App\Models\PartnerReferralInviteModel;

class PartnerReferralController extends BaseController
{
    public function summary($partner_id)
    {
        $referralModel = new PartnerReferralModel();
        $inviteModel   = new PartnerReferralInviteModel();
        $partnerModel  = new PartnerModel();

        $referrals        = $referralModel
            ->where('referrer_id', $partner_id)
            ->orderBy('created_at', 'DESC')
            ->findAll();

        $invites = $inviteModel
            ->where('referrer_id', $partner_id)
            ->orderBy('created_at', 'DESC')
            ->findAll();

        $responseReferrals = [];
        $responsePayouts   = [];
        $refereeIds        = [];

        $total_bonus_earned  = 0;
        $total_bonus_paid    = 0;
        $total_bonus_pending = 0;

        // ✅ Registered Referrals (from partner_referrals)
        foreach ($referrals as $ref) {
            $referee = $partnerModel->find($ref['referee_id']);
            if (!$referee) continue;

            $bonusAmount = (float) $ref['bonus_amount'];
            $status      = $ref['bonus_status'];
            $refereeIds[] = $referee['id'];

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
                'id'             => $referee['id'],
                'name'           => $referee['name'],
                'mobile'         => $referee['mobile'] ?? '',
                'joined_at'      => date('Y-m-d', strtotime($referee['created_at'] ?? '')),
                'status'         => $referee['status'] ?? 'registered',
                'bonus_amount'   => $bonusAmount,
                'bonus_status'   => $status,
                'is_registered'  => true,
            ];
        }

        // ✅ Unregistered Invites
        foreach ($invites as $invite) {
            // Skip if already registered via referrals table
            if (in_array($invite['referee_id'], $refereeIds) || $invite['is_registered']) {
                continue;
            }

            $responseReferrals[] = [
                'id'             => null,
                'name'           => $invite['friend_name'],
                'mobile'         => $invite['friend_mobile'],
                'joined_at'      => null,
                'status'         => 'invited',
                'bonus_amount'   => 0,
                'bonus_status'   => 'pending',
                'is_registered'  => false,
            ];
        }

        return $this->response->setJSON([
            'status'               => 200,
            'message'              => 'Referral summary fetched successfully.',
            'total_referred'       => count($responseReferrals),
            'total_bonus_earned'   => $total_bonus_earned,
            'total_bonus_paid'     => $total_bonus_paid,
            'total_bonus_pending'  => $total_bonus_pending,
            'referrals'            => $responseReferrals,
            'payouts'              => $responsePayouts,
        ]);
    }
    public function adminReferralSummary()
    {
        $referralModel = new \App\Models\PartnerReferralModel();
        $partnerModel  = new \App\Models\PartnerModel();

        // Fetch all referrers who made at least one referral
        $referrers = $referralModel
            ->select('referrer_id')
            ->distinct()
            ->findAll();

        $result = [];

        foreach ($referrers as $refData) {
            $referrer_id = $refData['referrer_id'];
            $partner = $partnerModel->find($referrer_id);
            if (!$partner) continue;

            $referrals = $referralModel->where('referrer_id', $referrer_id)->findAll();

            $registered = 0;
            $pending    = 0;
            $eligible   = 0;
            $bonusEarned = 0;
            $bonusPaid   = 0;
            $bonusPending = 0;

            foreach ($referrals as $ref) {
                if ($ref['referee_id']) {
                    $registered++;
                } else {
                    $pending++;
                }

                if ((int) $ref['is_eligible'] === 1) {
                    $eligible++;
                }

                $bonusEarned += (float) $ref['bonus_amount'];

                if ($ref['bonus_status'] === 'paid') {
                    $bonusPaid += (float) $ref['bonus_amount'];
                } elseif ($ref['bonus_status'] === 'pending') {
                    $bonusPending += (float) $ref['bonus_amount'];
                }
            }

            $result[] = [
                'partner_id'          => $referrer_id,
                'partner_name'        => $partner['name'],
                'partner_mobile'      => $partner['mobile'],
                'total_referrals'     => count($referrals),
                'registered_referrals' => $registered,
                'pending_invites'     => $pending,
                'eligible_referrals'  => $eligible,
                'bonus_earned'        => $bonusEarned,
                'bonus_paid'          => $bonusPaid,
                'bonus_pending'       => $bonusPending,
            ];
        }

        return $this->response->setJSON([
            'status' => 200,
            'message' => 'Referral summary fetched successfully.',
            'data'   => $result
        ]);
    }
    public function partnerReferrals($partner_id)
    {
        $referralModel = new \App\Models\PartnerReferralModel();
        $partnerModel  = new \App\Models\PartnerModel();

        // Fetch the partner making the referrals
        $referrer = $partnerModel->find($partner_id);
        if (!$referrer) {
            return $this->response->setStatusCode(404)->setJSON([
                'status' => 404,
                'error'  => 'Referrer not found.',
            ]);
        }

        // Get all referrals made by this partner
        $referrals = $referralModel
            ->where('referrer_id', $partner_id)
            ->orderBy('created_at', 'DESC')
            ->findAll();

        $response = [];

        foreach ($referrals as $ref) {
            $referee = $ref['referee_id'] ? $partnerModel->find($ref['referee_id']) : null;

            $response[] = [
                'referral_id'     => $ref['id'],
                'referee_id'      => $referee['id'] ?? null,
                'name'            => $referee['name'] ?? $ref['invited_name'] ?? 'Pending Registration',
                'mobile'          => $referee['mobile'] ?? $ref['invited_mobile'] ?? '',
                'joined_at'       => $referee['created_at'] ?? null,
                'bonus_amount'    => (float) $ref['bonus_amount'],
                'bonus_status'    => $ref['bonus_status'],
                'is_eligible'     => (bool) $ref['is_eligible'],
                'tasks_completed' => (int) $ref['tasks_completed'],
                'required_tasks'  => (int) $ref['required_tasks'],
                'paid_at'         => $ref['paid_at'],
                'paid_txn_id'     => $ref['paid_txn_id'],
                'updated_at'      => $ref['updated_at'],
            ];
        }

        return $this->response->setJSON([
            'referrer_id'   => $partner_id,
            'referrer_name' => $referrer['name'],
            'referrals'     => $response,
        ]);
    }
}
