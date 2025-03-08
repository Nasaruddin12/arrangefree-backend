<?php

namespace App\Controllers;

use App\Models\BookingsModel;
use App\Models\BookingServicesModel;
use App\Models\BookingPaymentsModel;
use App\Models\RazorpayOrdersModel;
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
            // $paymentType = strtolower(str_replace(' ', '_', $data['payment_type'] ?? 'pay_later'));
            $paymentType = $data['payment_type'];

            // Determine Payment Status & Amounts
            $paidAmount = ($paymentType === 'pay_later') ? 0.00 : 0.00;
            $amountDue = max(0, $data['final_amount'] - $paidAmount);
            $paymentStatus = ($paymentType === 'pay_later') ? null : ($data['payment_status'] ?? 'pending');

            // Validate Payment Status Enum
            $validPaymentStatuses = ['pending', 'completed', 'failed', 'refunded'];
            if ($paymentStatus && !in_array($paymentStatus, $validPaymentStatuses)) {
                return $this->failValidationErrors([
                    'status'  => 400,
                    'message' => 'Invalid payment status.',
                    'errors'  => ['payment_status' => 'Allowed values: pending, completed, failed, refunded.']
                ], 400);
            }

            // Determine Booking Status
            $bookingStatus = ($paymentType === 'pay_later') ? 'pending' : (($paymentStatus === 'completed' && $amountDue == 0) ? 'confirmed' : 'pending');

            // Booking Data
            $bookingData = [
                'booking_id'     => 'SE' . date('YmdHis'), // Generates a random 6-digit number
                'user_id'        => $data['user_id'],
                'total_amount'   => $data['total_amount'],
                'discount'       => $data['discount'] ?? 0.00,
                'final_amount'   => $data['final_amount'],
                'paid_amount'    => $paidAmount,
                'amount_due'     => $amountDue,
                'payment_type'   => $paymentType,
                'status'         => $bookingStatus,
                'applied_coupon' => $data['applied_coupon'] ?? null,
                'address_id'     => $data['address_id'] ?? null,
                'slot_date'      => $data['slot_date'] ?? null,
                'created_at'     => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s'),
            ];


            // Start Transaction
            $this->db->transStart();

            // Validate & Insert Booking
            if (!$this->bookingsModel->insert($bookingData)) {
                return $this->failValidationErrors([
                    'status'  => 400,
                    'message' => 'Validation failed.',
                    'errors'  => $this->bookingsModel->errors(),
                ]);
            }
            $bookingId = $this->bookingsModel->insertID();

            // Insert Booking Services (if any)
            if (!empty($data['services'])) {
                foreach ($data['services'] as $service) {
                    $serviceData = [
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
                    ];



                    if (!$this->bookingServicesModel->insert($serviceData)) {
                        return $this->failValidationErrors([
                            'status'  => 400,
                            'message' => 'Validation failed for booking services.',
                            'errors'  => $this->bookingServicesModel->errors(),
                        ]);
                    }
                }
                $this->seebCartModel->where('user_id', $data['user_id'])->delete();
            }

            // Handle Razorpay Order if Payment Type is Online
            $razorpayOrder = null;
            if ($paymentType === 'online') {
                try {
                    $config = new \Config\Razorpay();
                    $razorpay = new \Razorpay\Api\Api($config->keyId, $config->keySecret);
                    $currency = $config->displayCurrency;
                    $receipt = 'order_' . time();

                    // Create order on Razorpay
                    $orderData = [
                        'amount'          => $amountDue * 100, // Convert to paisa
                        'currency'        => $currency,
                        'receipt'         => $receipt,
                        'payment_capture' => 1,
                    ];

                    $razorpayOrder = $razorpay->order->create($orderData);
                    // Store order details in database
                    $razorpayModel = new RazorpayOrdersModel();
                    $orderRecord = [
                        'user_id'   => $data['user_id'],
                        'order_id'  => $razorpayOrder->id,
                        'amount'    => $razorpayOrder->amount / 100, // Convert back to original amount
                        'currency'  => $currency,
                        'status'    => $razorpayOrder->status,
                        'receipt'   => $razorpayOrder->receipt,
                        'offer_id'  => $razorpayOrder->offer_id ?? null,
                        'created_at' => date('Y-m-d H:i:s'),
                    ];
                    $razorpayModel->insert($orderRecord);
                } catch (\Exception $e) {
                    log_message('error', 'Razorpay Error: ' . $e->getMessage());
                    return $this->failServerError('Payment gateway error: ' . $e->getMessage());
                }
            }

            // Remove Booked Items from Cart **ONLY** if payment type is 'pay_later'
            if ($paymentType === 'pay_later') {
                $this->seebCartModel->where('user_id', $data['user_id'])->delete();
            }

            // Commit Transaction
            $this->db->transComplete();
            if ($this->db->transStatus() === false) {
                $dbError = $this->db->error();
                log_message('error', 'Transaction failed: ' . json_encode($dbError));
                return $this->failServerError('Transaction failed. Please try again.');
            }

            return $this->respondCreated([
                'status'         => 201,
                'message'        => 'Booking successfully created!',
                'data' => [
                    'id'     => $bookingId,
                    'booking_id'     => $bookingData['booking_id'],
                    'amount'         => $razorpayOrder->amount ?? $amountDue,
                    'razorpay_order' => $razorpayOrder ? $razorpayOrder->id : null
                ]
            ]);
        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Booking Error: ' . $e->getMessage());
            return $this->failServerError('Something went wrong. ' . $e->getMessage());
        }
    }


    public function getAllBookings()
    {
        try {
            $page  = $this->request->getVar('page') ?? 1; // Default page is 1
            $limit = $this->request->getVar('limit') ?? 10; // Default limit is 10
            $offset = ($page - 1) * $limit;

            $bookings = $this->bookingsModel
                ->select('bookings.*, af_customers.name as user_name')
                ->join('af_customers', 'af_customers.id = bookings.user_id', 'left')
                ->orderBy('bookings.created_at', 'DESC')
                ->findAll($limit, $offset);

            $totalBookings = $this->bookingsModel->countAll(); // Total count for pagination

            return $this->respond([
                'status' => 200,
                'message' => 'Bookings retrieved successfully.',
                'data' => $bookings,
                'pagination' => [
                    'current_page' => (int) $page,
                    'per_page' => (int) $limit,
                    'total_records' => (int) $totalBookings,
                    'total_pages' => ceil($totalBookings / $limit)
                ]
            ], 200);
        } catch (\Exception $e) {
            log_message('error', 'Error fetching bookings: ' . $e->getMessage());
            return $this->respond([
                'status' => 500,
                'message' => 'Something went wrong while fetching bookings.'
            ], 500);
        }
    }

    public function getBookingsByUser($user_id)
    {
        try {
            $bookings = $this->bookingsModel
                ->select('bookings.*, af_customers.name as user_name')
                ->join('af_customers', 'af_customers.id = bookings.user_id', 'left')
                ->where('bookings.user_id', $user_id)
                ->orderBy('bookings.created_at', 'DESC')
                ->findAll();

            if (empty($bookings)) {
                return $this->respond([
                    'status' => 404,
                    'message' => 'No bookings found for this user.'
                ], 404);
            }

            // Fetch booking services for each booking
            foreach ($bookings as &$booking) {
                $booking['services'] = $this->bookingServicesModel
                    ->select('booking_services.*, services.name')
                    ->join('services', 'services.id = booking_services.service_id', 'left')
                    ->where('booking_services.booking_id', $booking['id'])
                    ->findAll();
            }

            return $this->respond([
                'status' => 200,
                'message' => 'User bookings retrieved successfully.',
                'data' => $bookings
            ], 200);
        } catch (\Exception $e) {
            log_message('error', 'Error fetching user bookings: ' . $e->getMessage());
            return $this->respond([
                'status' => 500,
                'message' => 'Something went wrong while fetching user bookings.'
            ], 500);
        }
    }


    public function getBookingById($booking_id)
    {
        try {
            // Fetch booking details with user and address
            $booking = $this->bookingsModel
                ->select('bookings.*, af_customers.name as user_name, af_customers.email as user_email, 
                      customer_addresses.address as customer_address')
                ->join('af_customers', 'af_customers.id = bookings.user_id', 'left')
                ->join('customer_addresses', 'customer_addresses.id = bookings.address_id', 'left')
                ->where('bookings.id', $booking_id)
                ->first();

            if (!$booking) {
                return $this->respond([
                    'status' => 404,
                    'message' => 'Booking not found.'
                ], 404);
            }

            // Fetch booking services
            $services = $this->bookingServicesModel
                ->select('booking_services.*, services.name as service_name, services.description as service_description')
                ->join('services', 'services.id = booking_services.service_id', 'left')
                ->where('booking_services.booking_id', $booking_id)
                ->findAll();

            // Fetch payment details
            $payments = $this->bookingPaymentsModel
                ->where('booking_id', $booking_id)
                ->findAll();

            return $this->respond([
                'status' => 200,
                'message' => 'Booking retrieved successfully.',
                'data' => [
                    'booking' => $booking,
                    'services' => $services,
                    'payments' => $payments
                ]
            ], 200);
        } catch (\Exception $e) {
            log_message('error', 'Error fetching booking by ID: ' . $e->getMessage());
            return $this->respond([
                'status' => 500,
                'message' => 'Something went wrong while fetching the booking.'
            ], 500);
        }
    }

    public function verifyPayment()
    {
        try {
            $data = $this->request->getJSON(true);

            // Validate Required Fields
            if (empty($data['razorpay_payment_id'])) {
                return $this->failValidationErrors([
                    'status'  => 400,
                    'message' => 'Missing required payment details.',
                ]);
            }

            // Get Razorpay Config
            $config = new \Config\Razorpay();
            $razorpay = new \Razorpay\Api\Api($config->keyId, $config->keySecret);

            // Fetch Payment Details
            $payment = $razorpay->payment->fetch($data['razorpay_payment_id']);
            if (!$payment) {
                return $this->failNotFound('Payment not found.');
            }

            $paymentMethod = $payment->method ?? 'razorpay';
            $razorpayStatus = $payment->status; // "created", "authorized", "captured", "failed"

            // Check if it's a UPI Payment
            if ($paymentMethod === 'upi') {
                // If UPI payment is authorized, mark as completed and booking confirmed
                $paymentStatus = ($razorpayStatus === 'authorized' || $razorpayStatus === 'captured') ? 'completed' : 'pending';
                $bookingStatus = ($razorpayStatus === 'authorized' || $razorpayStatus === 'captured') ? 'confirmed' : 'pending';
            } else {
                // Validate Order ID & Signature for Non-UPI payments
                if (empty($data['razorpay_order_id']) || empty($data['razorpay_signature'])) {
                    return $this->failValidationErrors([
                        'status'  => 400,
                        'message' => 'Missing order ID or signature for non-UPI payment.',
                    ]);
                }

                // Verify Signature
                try {
                    $attributes = [
                        'razorpay_order_id'   => $data['razorpay_order_id'],
                        'razorpay_payment_id' => $data['razorpay_payment_id'],
                        'razorpay_signature'  => $data['razorpay_signature']
                    ];
                    $razorpay->utility->verifyPaymentSignature($attributes);
                } catch (\Exception $e) {
                    log_message('error', 'Payment Signature Verification Failed: ' . $e->getMessage());

                    // Store failed payment record
                    $this->bookingPaymentsModel->insert([
                        'booking_id'      => $data['booking_id'],
                        'user_id'         => $data['user_id'],
                        'transaction_id'  => $data['razorpay_payment_id'],
                        'amount'          => 0,
                        'payment_method'  => $paymentMethod,
                        'payment_status'  => 'failed',
                        'razorpay_status' => 'signature_failed',
                        'from_json'       => json_encode(['error' => $e->getMessage()]),
                        'created_at'      => date('Y-m-d H:i:s'),
                    ]);

                    return $this->failValidationErrors([
                        'status'    => 400,
                        'message'   => 'Payment verification failed: ' . $e->getMessage(),
                        'razorpay_status' => 'signature_failed'
                    ]);
                }

                // For non-UPI payments, manually capture payment before updating status
                if ($razorpayStatus === 'authorized') {
                    $payment->capture(['amount' => $payment->amount, 'currency' => $payment->currency]);
                    $razorpayStatus = 'captured';
                }

                $paymentStatus = ($razorpayStatus === 'captured') ? 'completed' : 'pending';
                $bookingStatus = ($razorpayStatus === 'captured') ? 'confirmed' : 'pending';
            }

            // Fetch Booking
            $booking = $this->bookingsModel->where('id', $data['booking_id'])->first();
            if (!$booking) {
                return $this->failNotFound('Booking not found.');
            }

            // Calculate Payment Amount
            $paidAmount = $booking['paid_amount'] + ($payment->amount / 100);
            $amountDue = max($booking['final_amount'] - $paidAmount, 0);

            // Update Booking Record
            $this->bookingsModel->update($booking['id'], [
                'payment_status' => $paymentStatus,
                'status'         => $bookingStatus,
                'paid_amount'    => $paidAmount,
                'amount_due'     => $amountDue,
                'updated_at'     => date('Y-m-d H:i:s'),
            ]);

            if ($paymentStatus === 'completed') {
                $this->seebCartModel->where('user_id', $data['user_id'])->delete();
            }
            // Store Payment Record
            $paymentData = [
                'booking_id'      => $booking['id'],
                'user_id'         => $data['user_id'],
                'transaction_id'  => $data['razorpay_payment_id'],
                'payment_method'  => $paymentMethod,
                'amount'          => $payment->amount / 100,
                'currency'        => $payment->currency,
                'payment_status'  => $paymentStatus,
                'razorpay_status' => $razorpayStatus,
                'from_json'       => json_encode($payment),
                'created_at'      => date('Y-m-d H:i:s'),
            ];
            $this->bookingPaymentsModel->insert($paymentData);

            return $this->respond([
                'status'    => 200,
                'message'   => 'Payment verified and updated.',
                'data'      => [
                    'id'            => $booking['id'],
                    'booking_id'    => $booking['booking_id'],
                    'booking'       => [
                        'status'       => $bookingStatus,
                        'paid_amount'  => $paidAmount,
                        'amount_due'   => $amountDue,
                    ],
                    'payment'       => $paymentData,
                    'payment_status' => $paymentStatus,
                    'razorpay_status' => $razorpayStatus,
                ]
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Payment Verification Error: ' . $e->getMessage());
            return $this->failServerError('Something went wrong. ' . $e->getMessage());
        }
    }


    public function webhookRazorpay()
    {
        try {
            // Read Webhook Payload
            $payload = $this->request->getJSON(true);
            log_message('info', 'Webhook Received: ' . json_encode($payload));

            if (empty($payload['event']) || empty($payload['payload']['payment']['entity'])) {
                return $this->failValidationErrors([
                    'status'  => 400,
                    'message' => 'Invalid Webhook Payload.',
                ]);
            }

            $event = $payload['event'];
            $payment = $payload['payload']['payment']['entity'];
            $razorpayPaymentId = $payment['id'];
            $razorpayStatus = $payment['status']; // "created", "authorized", "captured", "failed"

            // Fetch Payment Details from DB
            $existingPayment = $this->bookingPaymentsModel->where('transaction_id', $razorpayPaymentId)->first();

            if (!$existingPayment) {
                log_message('error', 'Payment not found for Webhook: ' . $razorpayPaymentId);
                return $this->failNotFound('Payment record not found.');
            }

            // Update Payment Record with Razorpay Status Only
            $result = $this->bookingPaymentsModel->update($existingPayment['id'], [
                'razorpay_status' => $razorpayStatus,
                'from_json'       => json_encode($payment),
                'updated_at'      => date('Y-m-d H:i:s'),
            ]);

            if (!$result) {
                log_message('error', 'Update failed: ' . json_encode($this->bookingPaymentsModel->errors()));
            }


            return $this->respond([
                'status'  => 200,
                'message' => 'Webhook processed successfully.',
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Webhook Error: ' . $e->getMessage());
            return $this->failServerError('Something went wrong. ' . $e->getMessage());
        }
    }
}
