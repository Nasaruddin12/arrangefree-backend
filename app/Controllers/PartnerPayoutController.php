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

    /**
     * Admin: Get all partner payout withdrawal requests (paginated + filters)
     *
     * GET /admin/payouts/requests
     * Query params: page, limit, status, partner_id, search
     * Header: Authorization: Bearer <token>
     */
    public function adminListRequests()
    {
        $page = (int) ($this->request->getVar('page') ?? 1);
        $limit = (int) ($this->request->getVar('limit') ?? 20);
        $offset = ($page - 1) * $limit;

        $status = $this->request->getVar('status');
        $partnerId = $this->request->getVar('partner_id');
        $search = $this->request->getVar('search');

        $builder = $this->model->orderBy('created_at', 'DESC');

        if (!empty($status)) {
            $builder = $builder->where('status', $status);
        }

        if (!empty($partnerId)) {
            $builder = $builder->where('partner_id', (int)$partnerId);
        }

        if (!empty($search)) {
            $builder = $builder->like('notes', $search)->orLike('id', $search);
        }

        $total = $builder->countAllResults(false);

        $rows = $builder->findAll($limit, $offset);

        return $this->response->setJSON([
            'status' => true,
            'message' => 'Payout requests retrieved',
            'data' => $rows,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total_records' => (int)$total,
                'total_pages' => $limit > 0 ? (int) ceil($total / $limit) : 0,
            ],
        ]);
    }
}
