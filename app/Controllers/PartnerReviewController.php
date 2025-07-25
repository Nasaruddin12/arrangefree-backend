<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\PartnerReviewModel;

class PartnerReviewController extends BaseController
{
    protected $model;
    use \CodeIgniter\API\ResponseTrait;

    public function __construct()
    {
        $this->model = new PartnerReviewModel();
    }

    public function submit()
    {
        $rules = [
            'booking_service_id' => 'required|integer',
            'partner_id'         => 'required|integer',
            'rating'             => 'required|integer|greater_than_equal_to[1]|less_than_equal_to[5]',
            'review'             => 'permit_empty|string',
        ];

        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $data = $this->request->getPost([
            'booking_service_id',
            'partner_id',
            'rating',
            'review'
        ]);

        // Only one review per booking_service
        $existing = $this->model
            ->where('booking_service_id', $data['booking_service_id'])
            ->first();

        if ($existing) {
            return $this->fail('Review already submitted for this booking.');
        }

        $this->model->insert($data);

        return $this->respondCreated(['status' => true, 'message' => 'Review submitted successfully']);
    }

    public function getByPartner($partnerId)
    {
        $reviews = $this->model
            ->where('partner_id', $partnerId)
            ->orderBy('created_at', 'desc')
            ->findAll();

        return $this->respond(['status' => true, 'data' => $reviews]);
    }
}
