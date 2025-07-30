<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\PartnerPayoutModel;

class PartnerPayoutController extends BaseController
{
    protected $model;
    use \CodeIgniter\API\ResponseTrait;

    public function __construct()
    {
        $this->model = new PartnerPayoutModel();
    }

    // GET: /payouts/partner/(:partnerId)
    public function listByPartner($partnerId)
    {
        $payouts = $this->model
            ->where('partner_id', $partnerId)
            ->orderBy('created_at', 'desc')
            ->findAll();

        return $this->response->setJSON(['status' => true, 'data' => $payouts]);
    }

    // POST: /payouts/create
    public function create()
    {
        $rules = [
            'partner_id'         => 'required|integer',
            'booking_service_id' => 'required|integer',
            'amount'             => 'required|decimal',
            'notes'              => 'permit_empty|string',
        ];

        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $data = $this->request->getJSON(true);

        $data['status'] = 'pending';

        $this->model->insert($data);

        return $this->response->setJSON(['status' => true, 'message' => 'Payout created']);
    }

    // POST: /payouts/release
    public function release()
    {
        $id = $this->request->getVar('id');

        $payout = $this->model->find($id);
        if (!$payout) {
            return $this->failNotFound('Payout record not found');
        }

        $this->model->update($id, [
            'status'      => 'released',
            'released_at' => date('Y-m-d H:i:s')
        ]);

        return $this->response->setJSON(['status' => true, 'message' => 'Payout released']);
    }
}
