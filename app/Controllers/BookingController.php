<?php

namespace App\Controllers;

use App\Models\BookingsModel;
use App\Models\BookingServicesModel;
use App\Models\BookingPaymentsModel;
use App\Models\SeebCartModel;
use CodeIgniter\RESTful\ResourceController;

class BookingController extends ResourceController
{
    protected $bookingsModel;
    protected $bookingServicesModel;
    protected $bookingPaymentsModel;
    protected $seebCartModel;
    protected $db;

    public function __construct()
    {
        $this->bookingsModel = new BookingsModel();
        $this->bookingServicesModel = new BookingServicesModel();
        $this->bookingPaymentsModel = new BookingPaymentsModel();
        $this->seebCartModel = new SeebCartModel();
        $this->db = \Config\Database::connect();
    }

    /**
     * Create Booking (After Payment)
     */
    public function createBooking()
    {
        try {
            $data = $this->request->getJSON(true) ?? $this->request->getVar();

            // Normalize Payment Type
            $paymentType = strtolower(str_replace(' ', '_', $data['payment_type'] ?? 'pay_later'));

            // Determine Payment Status & Amounts
            $paidAmount = ($paymentType === 'pay_later') ? 0.00 : ($data['paid_amount'] ?? 0.00);
            $amountDue = max(0, $data['final_amount'] - $paidAmount);
            $paymentStatus = ($paymentType === 'pay_later') ? null : ($data['payment_status'] ?? 'pending');

            // Validate Payment Status Enum
            $validPaymentStatuses = ['pending', 'completed', 'failed', 'refunded'];
            if ($paymentStatus && !in_array($paymentStatus, $validPaymentStatuses)) {
                return $this->failValidationErrors([
                    'status'  => 400,
                    'message' => 'Invalid payment status.',
                    'errors'  => ['payment_status' => 'Allowed values: pending, completed, failed, refunded.']
                ]);
            }

            // Determine Booking Status
            if ($paymentType === 'pay_later') {
                $bookingStatus = 'pending';
            } else {
                $bookingStatus = ($paymentStatus === 'completed' && $amountDue == 0) ? 'confirmed' : 'pending';
            }

            // Booking Data
            $bookingData = [
                'user_id'        => $data['user_id'],
                'total_amount'   => $data['total_amount'],
                'discount'       => $data['discount'] ?? 0.00,
                'final_amount'   => $data['final_amount'],
                'paid_amount'    => $paidAmount,
                'amount_due'     => $amountDue,
                'payment_type'   => $paymentType,
                'status'         => $bookingStatus,
                'applied_coupon' => $data['applied_coupon'] ?? null,
                'created_at'     => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s'),
            ];

            // Validate Booking Data
            if (!$this->bookingsModel->validate($bookingData)) {
                return $this->failValidationErrors([
                    'status'  => 400,
                    'message' => 'Validation failed.',
                    'errors'  => $this->bookingsModel->errors()
                ]);
            }

            // Start Transaction
            $this->db->transStart();

            // Insert Booking
            $bookingId = $this->bookingsModel->insert($bookingData);
            if (!$bookingId) {
                throw new \Exception('Failed to store booking.');
            }

            // Insert Booking Services (if any)
            if (!empty($data['services'])) {
                foreach ($data['services'] as $service) {
                    $this->bookingServicesModel->insert([
                        'booking_id'      => $bookingId,
                        'service_id'      => $service['service_id'],
                        'service_type_id' => $service['service_type_id'],
                        'room_id'         => $service['room_id'],
                        'rate_type'       => $service['rate_type'],
                        'value'           => $service['value'],
                        'rate'            => $service['rate'],
                        'amount'          => $service['amount'],
                        'description'     => $service['description'] ?? null,
                        'reference_image' => $service['reference_image'] ?? null,
                    ]);
                }
            }

            // If payment type is online, handle payment
            if ($paymentType === 'online') {
                if (in_array($paymentStatus, ['completed', 'failed'])) {
                    $paymentData = [
                        'booking_id'     => $bookingId,
                        'user_id'        => $data['user_id'],
                        'amount'         => $paidAmount,
                        'payment_type'   => $paymentType,
                        'transaction_id' => $data['transaction_id'] ?? null,
                        'payment_status' => $paymentStatus,
                        'payment_date'   => date('Y-m-d H:i:s'),
                    ];

                    // Check for Duplicate Transactions
                    if (!empty($data['transaction_id']) && $this->bookingPaymentsModel->where('transaction_id', $data['transaction_id'])->first()) {
                        return $this->failValidationErrors([
                            'status'  => 400,
                            'message' => 'Duplicate transaction detected.'
                        ]);
                    }

                    // Validate Payment Data
                    if (!$this->bookingPaymentsModel->validate($paymentData)) {
                        return $this->failValidationErrors([
                            'status'  => 400,
                            'message' => 'Payment validation failed.',
                            'errors'  => $this->bookingPaymentsModel->errors()
                        ]);
                    }

                    // Insert Payment Data
                    $this->bookingPaymentsModel->insert($paymentData);

                    // If payment is completed, update booking status
                    if ($paymentStatus === 'completed') {
                        $this->bookingsModel->update($bookingId, ['status' => 'confirmed']);
                    }
                }
            }

            // Remove Booked Items from Cart
            $this->seebCartModel->where('user_id', $data['user_id'])->delete();

            // Commit Transaction
            $this->db->transComplete();
            if ($this->db->transStatus() === false) {
                $dbError = $this->db->error(); // Get database error details
            
                // If there's no specific DB error, log additional debug info
                if (empty($dbError['code'])) {
                    log_message('error', 'Unknown transaction failure. Debugging required.');
                    return $this->respond([
                        'status'  => 500,
                        'message' => 'Transaction failed. Debugging required. Check logs.'
                    ], 500);
                }
            
                log_message('error', 'Transaction failed: ' . json_encode($dbError));
            
                return $this->respond([
                    'status'  => 500,
                    'message' => 'Transaction failed. Please try again.',
                    'error'   => $dbError
                ], 500);
            }
            

            return $this->respond([
                'status'         => 200,
                'message'        => 'Booking successfully completed!',
                'booking_id'     => $bookingId,
                'paid_amount'    => $paidAmount,
                'amount_due'     => $amountDue,
                'payment_status' => $paymentStatus
            ], 200);
        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Booking Error: ' . $e->getMessage());
            return $this->respond([
                'status'  => 500,
                'message' => 'Something went wrong. ' . $e->getMessage()
            ], 500);
        }
    }
}
