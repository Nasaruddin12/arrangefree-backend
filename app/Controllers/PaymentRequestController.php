<?php

namespace App\Controllers;

use App\Models\BookingPaymentRequest;
use CodeIgniter\RESTful\ResourceController;

class PaymentRequestController extends ResourceController
{
    protected $modelName = 'App\Models\BookingPaymentRequest';
    protected $format    = 'json';

    // Create Payment Request
    public function create()
    {
        $rules = [
            'booking_id' => 'required|integer',
            'user_id'    => 'required|integer',
            'amount'     => 'required|decimal'
        ];

        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $data = [
            'booking_id'     => $this->request->getPost('booking_id'),
            'user_id'        => $this->request->getPost('user_id'),
            'amount'         => $this->request->getPost('amount'),
            'request_status' => 'pending',
        ];

        if ($this->model->insert($data)) {
            return $this->respondCreated([
                'status'  => 201,
                'message' => 'Payment request raised successfully.'
            ]);
        } else {
            return $this->failServerError('Failed to raise payment request.');
        }
    }

    // Fetch Payment Requests
    public function index()
    {
        return $this->respond([
            'status'  => 200,
            'message' => 'Payment requests retrieved successfully.',
            'data'    => $this->model->findAll()
        ]);
    }

    // Update Payment Request Status
    public function update($id = null)
    {
        $status = $this->request->getPost('request_status');

        if (!$status || !in_array($status, ['pending', 'completed', 'cancelled'])) {
            return $this->failValidationErrors(['request_status' => 'Invalid status.']);
        }

        $this->model->update($id, ['request_status' => $status]);

        return $this->respond([
            'status'  => 200,
            'message' => 'Payment request updated successfully.'
        ]);
    }
}
