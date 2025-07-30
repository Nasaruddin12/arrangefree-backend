<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\PartnerReferralInviteModel;
use CodeIgniter\API\ResponseTrait;

class ReferralController extends BaseController
{
    use ResponseTrait;

    public function invite()
    {
        $rules = [
            'referrer_id'   => 'required|integer',
            'friend_name'   => 'required|string|max_length[100]',
            'friend_mobile' => 'required|regex_match[/^[0-9]{10,15}$/]',
            'referral_code' => 'permit_empty|string|max_length[20]'
        ];

        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $data = $this->request->getJSON(true);

        $model = new PartnerReferralInviteModel();

        // Generate a simple referral code (e.g. SEEB1234)
        $code = 'SEEB' . rand(1000, 9999);

        $inviteData = [
            'referrer_id'    => $data['referrer_id'],
            'friend_name'    => $data['friend_name'],
            'friend_mobile'  => $data['friend_mobile'],
            'referral_code'  => $data['referral_code'] ?? $code,
            'is_registered'  => 0,
        ];

        $model->insert($inviteData);

        return $this->respond([
            'message' => 'Referral invite created successfully.',
            'data'    => $inviteData,
            'share_link' => "https://seeb.in/r/partner?ref={$code}"
        ]);
    }
    public function getReferrerByMobile($mobile)
    {
        $inviteModel = new \App\Models\PartnerReferralInviteModel();
        $partnerModel = new \App\Models\PartnerModel();

        $invite = $inviteModel
            ->where('friend_mobile', $mobile)
            ->orderBy('created_at', 'DESC')
            ->first();

        if (!$invite) {
            return $this->failNotFound('No referrer found for this mobile number.');
        }

        $referrer = $partnerModel->find($invite['referrer_id']);

        return $this->respond([
            'status' => 200,
            'referrer_id'   => $invite['referrer_id'],
            'referrer_name' => $referrer['name'] ?? null,
            'referral_code' => $invite['referral_code'],
            'created_at'    => $invite['created_at'],
            'is_registered' => $invite['is_registered'],
            'referee_id'    => $invite['referee_id'] ?? null
        ]);
    }
}
