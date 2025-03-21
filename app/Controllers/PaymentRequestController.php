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
            'booking_id'     => $this->request->getVar('booking_id'),
            'user_id'        => $this->request->getVar('user_id'),
            'amount'         => $this->request->getVar('amount'),
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
        $status = $this->request->getVar('request_status');

        if (!$status || !in_array($status, ['pending', 'completed', 'cancelled'])) {
            return $this->failValidationErrors(['request_status' => 'Invalid status.']);
        }

        $this->model->update($id, ['request_status' => $status]);

        return $this->respond([
            'status'  => 200,
            'message' => 'Payment request updated successfully.'
        ]);
    }
    
    // Delete Payment Request
    public function delete($id = null)
    {
        try {
            // Check if the payment request exists
            $paymentRequest = $this->model->find($id);
            if (!$paymentRequest) {
                return $this->respond([
                    'status'  => 404,
                    'message' => 'Payment request not found.'
                ], 404);
            }

            // Delete the payment request
            $this->model->delete($id);

            return $this->respond([
                'status'  => 200,
                'message' => 'Payment request deleted successfully.'
            ], 200);
        } catch (\Exception $e) {
            log_message('error', 'Error deleting payment request: ' . $e->getMessage());
            return $this->respond([
                'status'  => 500,
                'message' => 'Something went wrong while deleting the payment request.'
            ], 500);
        }
    }


    // Get Payment Requests by Booking ID
    public function getByBookingId($booking_id = null)
    {
        if (!$booking_id) {
            return $this->failNotFound('Booking ID is required.');
        }

        $data = $this->model->where('booking_id', $booking_id)->findAll();

        if (empty($data)) {
            return $this->failNotFound('No payment requests found for this booking.');
        }

        return $this->respond([
            'status'  => 200,
            'message' => 'Payment requests retrieved successfully.',
            'data'    => $data
        ]);
    }
}
