<?php

namespace App\Controllers;

use App\Models\BookingExpenseModel;
use App\Models\BookingPaymentRequest;
use App\Models\BookingsModel;
use App\Models\BookingServicesModel;
use App\Models\BookingPaymentsModel;
use App\Models\RazorpayOrdersModel;
use App\Models\SeebCartModel;
use App\Models\CouponModel;
use App\Models\CustomerModel;
use App\Models\PaymentDisputeModel;
use App\Services\BookingVersionService;
use App\Services\ServiceAmountCalculator;
use CodeIgniter\RESTful\ResourceController;
use Dompdf\Dompdf;

class BookingController extends ResourceController
{
    protected $bookingsModel;
    protected $bookingServicesModel;
    protected $bookingPaymentsModel;
    protected $seebCartModel;
    protected $couponsModel;
    protected $paymentRequestsModel;
    protected $bookingExpenseModel;
    protected $customerModel;
    protected $servicesModel;
    protected $addonsModel;
    protected $bookingPaymentRequestsModel;
    protected $razorpayOrdersModel;
    protected $db;

    public function __construct()
    {
        $this->bookingsModel = new BookingsModel();
        $this->bookingServicesModel = new BookingServicesModel();
        $this->bookingPaymentsModel = new BookingPaymentsModel();
        $this->seebCartModel = new SeebCartModel();
        $this->couponsModel = new CouponModel();
        $this->paymentRequestsModel = new BookingPaymentRequest();
        $this->bookingExpenseModel = new BookingExpenseModel();
        $this->customerModel = new CustomerModel();
        $this->servicesModel = new \App\Models\ServiceModel();
        $this->addonsModel = new \App\Models\ServiceAddonModel();
        $this->bookingPaymentRequestsModel = new BookingPaymentRequest();
        $this->db = \Config\Database::connect();
    }

    /**
     * Create Booking (After Payment)
     */
    // public function createBooking()
    // {
    //     try {
    //         $data = $this->request->getJSON(true) ?? $this->request->getVar();

    //         // Normalize Payment Type
    //         // $paymentType = strtolower(str_replace(' ', '_', $data['payment_type'] ?? 'pay_later'));
    //         $paymentType = $data['payment_type'];

    //         // Determine Payment Status & Amounts
    //         $paidAmount = ($paymentType === 'pay_later') ? 0.00 : 0.00;
    //         $amountDue = max(0, $data['final_amount'] - $paidAmount);
    //         $paymentStatus = ($paymentType === 'pay_later') ? null : ($data['payment_status'] ?? 'pending');

    //         // Validate Payment Status Enum
    //         $validPaymentStatuses = ['pending', 'completed', 'failed', 'refunded'];
    //         if ($paymentStatus && !in_array($paymentStatus, $validPaymentStatuses)) {
    //             return $this->failValidationErrors([
    //                 'status'  => 400,
    //                 'message' => 'Invalid payment status.',
    //                 'errors'  => ['payment_status' => 'Allowed values: pending, completed, failed, refunded.']
    //             ], 400);
    //         }

    //         // Determine Booking Status
    //         $bookingStatus = ($paymentType === 'pay_later') ? 'pending' : (($paymentStatus === 'completed' && $amountDue == 0) ? 'confirmed' : 'pending');

    //         // Booking Data
    //         $bookingData = [
    //             'booking_id'     => 'SE' . date('YmdHis'), // Generates a random 6-digit number
    //             'user_id'        => $data['user_id'],
    //             'total_amount'   => $data['total_amount'],
    //             'discount'       => $data['discount'] ?? 0.00,
    //             'final_amount'   => $data['final_amount'],
    //             'paid_amount'    => $paidAmount,
    //             'amount_due'     => $amountDue,
    //             'payment_type'   => $paymentType,
    //             'status'         => $bookingStatus,
    //             'applied_coupon' => $data['applied_coupon'] ?? null,
    //             'address_id'     => $data['address_id'] ?? null,
    //             'slot_date'      => $data['slot_date'] ?? null,
    //             'created_at'     => date('Y-m-d H:i:s'),
    //             'updated_at'     => date('Y-m-d H:i:s'),
    //         ];


    //         // Start Transaction
    //         $this->db->transStart();

    //         // Validate & Insert Booking
    //         if (!$this->bookingsModel->insert($bookingData)) {
    //             return $this->failValidationErrors([
    //                 'status'  => 400,
    //                 'message' => 'Validation failed.',
    //                 'errors'  => $this->bookingsModel->errors(),
    //             ]);
    //         }
    //         $bookingId = $this->bookingsModel->insertID();

    //         // Insert Booking Services (if any)
    //         if (!empty($data['services'])) {
    //             foreach ($data['services'] as $service) {
    //                 $serviceData = [
    //                     'booking_id'      => $bookingId,
    //                     'service_id'      => $service['service_id'],
    //                     'service_type_id' => $service['service_type_id'],
    //                     'room_id'         => $service['room_id'],
    //                     'rate_type'       => $service['rate_type'],
    //                     'value'           => $service['value'],
    //                     'rate'            => $service['rate'],
    //                     'amount'          => $service['amount'],
    //                     'description'     => $service['description'] ?? null,
    //                     'reference_image' => $service['reference_image'] ?? null,
    //                 ];



    //                 if (!$this->bookingServicesModel->insert($serviceData)) {
    //                     return $this->failValidationErrors([
    //                         'status'  => 400,
    //                         'message' => 'Validation failed for booking services.',
    //                         'errors'  => $this->bookingServicesModel->errors(),
    //                     ]);
    //                 }
    //             }
    //             $this->seebCartModel->where('user_id', $data['user_id'])->delete();
    //         }

    //         // Handle Razorpay Order if Payment Type is Online
    //         $razorpayOrder = null;
    //         if ($paymentType === 'online') {
    //             try {
    //                 $config = new \Config\Razorpay();
    //                 $razorpay = new \Razorpay\Api\Api($config->keyId, $config->keySecret);
    //                 $currency = $config->displayCurrency;
    //                 $receipt = 'order_' . time();

    //                 // Create order on Razorpay
    //                 $orderData = [
    //                     'amount'          => $amountDue * 100, // Convert to paisa
    //                     'currency'        => $currency,
    //                     'receipt'         => $receipt,
    //                     'payment_capture' => 1,
    //                 ];

    //                 $razorpayOrder = $razorpay->order->create($orderData);
    //                 // Store order details in database
    //                 $razorpayModel = new RazorpayOrdersModel();
    //                 $orderRecord = [
    //                     'user_id'   => $data['user_id'],
    //                     'order_id'  => $razorpayOrder->id,
    //                     'amount'    => $razorpayOrder->amount / 100, // Convert back to original amount
    //                     'currency'  => $currency,
    //                     'status'    => $razorpayOrder->status,
    //                     'receipt'   => $razorpayOrder->receipt,
    //                     'offer_id'  => $razorpayOrder->offer_id ?? null,
    //                     'created_at' => date('Y-m-d H:i:s'),
    //                 ];
    //                 $razorpayModel->insert($orderRecord);
    //             } catch (\Exception $e) {
    //                 log_message('error', 'Razorpay Error: ' . $e->getMessage());
    //                 return $this->failServerError('Payment gateway error: ' . $e->getMessage());
    //             }
    //         }

    //         // Remove Booked Items from Cart **ONLY** if payment type is 'pay_later'
    //         if ($paymentType === 'pay_later') {
    //             $this->seebCartModel->where('user_id', $data['user_id'])->delete();
    //         }

    //         // Commit Transaction
    //         $this->db->transComplete();
    //         if ($this->db->transStatus() === false) {
    //             $dbError = $this->db->error();
    //             log_message('error', 'Transaction failed: ' . json_encode($dbError));
    //             return $this->failServerError('Transaction failed. Please try again.');
    //         }

    //         return $this->respondCreated([
    //             'status'         => 201,
    //             'message'        => 'Booking successfully created!',
    //             'data' => [
    //                 'id'     => $bookingId,
    //                 'booking_id'     => $bookingData['booking_id'],
    //                 'amount'         => $razorpayOrder->amount ?? $amountDue,
    //                 'razorpay_order' => $razorpayOrder ? $razorpayOrder->id : null
    //             ]
    //         ]);
    //     } catch (\Exception $e) {
    //         $this->db->transRollback();
    //         log_message('error', 'Booking Error: ' . $e->getMessage());
    //         return $this->failServerError('Something went wrong. ' . $e->getMessage());
    //     }
    // }
    public function createBooking()
    {
        try {

            $emailController = new EmailController();
            $data = $this->request->getJSON(true) ?? $this->request->getVar();
            $userId = $data['user_id'];
            $paymentType = $data['payment_type'];
            $appliedCoupon = $data['applied_coupon'] ?? null;

            // Fetch Cart Data for User
            $cartItems = $this->seebCartModel->where('user_id', $userId)->findAll();

            if (empty($cartItems)) {
                return $this->failValidationErrors('Cart is empty. Add services before booking.');
            }

            // Calculate Pricing
            $subtotal = array_sum(array_column($cartItems, 'amount'));
            $discount = 0.00;

            $coupon = $this->couponsModel->where('coupon_code', $appliedCoupon)->first();

            // Apply Coupon Discount
            if (!empty($coupon)) {
                switch ($coupon['coupon_type']) {
                    case 1:
                        $discount = ($subtotal / 100 * $coupon['coupon_type_name']);
                        break;
                    case 2:
                        $discount = $coupon['coupon_type_name'];
                        break;
                }
            }

            // Apply Discount to Subtotal
            $discountedTotal = max(0, $subtotal - $discount); // Ensure it doesn't go negative

            // Now Calculate GST on the Discounted Total
            $cgst = round($discountedTotal * 0.09, 2); // 9% CGST
            $sgst = round($discountedTotal * 0.09, 2); // 9% SGST
            $tax = $cgst + $sgst; // Total GST

            // Final Amount Calculation
            $finalAmount = max(0, $discountedTotal + $tax);
            $paidAmount = ($paymentType === 'pay_later') ? 0.00 : 0.00; // Modify if payment processing happens here
            $amountDue = $finalAmount - $paidAmount;

            // Booking Status Logic
            $paymentStatus = ($paymentType === 'pay_later') ? null : 'pending';
            $bookingStatus = ($paymentType === 'pay_later') ? 'pending' : (($paymentStatus === 'completed' && $amountDue == 0) ? 'confirmed' : 'pending');

            // Prepare Booking Data
            $bookingData = [
                'booking_id'     => 'SE' . date('YmdHis'),
                'user_id'        => $userId,
                'total_amount'   => $subtotal,
                'address_id'     => $data['address_id'],
                'cgst'           => $cgst, // 9% CGST
                'sgst'           => $sgst, // 9% SGST
                'discount'       => $discount,
                'final_amount'   => $finalAmount,
                'paid_amount'    => $paidAmount,
                'amount_due'     => $amountDue,
                'payment_type'   => $paymentType,
                'status'         => $bookingStatus,
                'applied_coupon' => $appliedCoupon,
                'slot_date'      => $data['slot_date'] ?? null,
                'created_at'     => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s'),
            ];

            // Start Transaction
            $this->db->transStart();

            // Insert Booking
            if (!$this->bookingsModel->insert($bookingData)) {
                return $this->failValidationErrors([
                    'status'  => 400,
                    'message' => 'Validation failed.',
                    'errors'  => $this->bookingsModel->errors(),
                ]);
            }
            $bookingId = $this->bookingsModel->insertID();
            // Insert Booking Services
            foreach ($cartItems as $service) {
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
                    'addons'          => $service['addons'] ?? null,
                ];

                if (!$this->bookingServicesModel->insert($serviceData)) {
                    return $this->failValidationErrors([
                        'status'  => 400,
                        'message' => 'Validation failed for booking services.',
                        'errors'  => $this->bookingServicesModel->errors(),
                    ]);
                }
            }

            // Razorpay Order for Online Payments
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

                    // Store order details
                    $razorpayModel = new RazorpayOrdersModel();
                    $orderRecord = [
                        'user_id'   => $userId,
                        'order_id'  => $razorpayOrder->id,
                        'booking_id' => $bookingId,
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

            // Remove Booked Items from Cart (only for 'pay_later')
            if ($paymentType === 'pay_later') {
                $this->seebCartModel->where('user_id', $userId)->delete();
            }

            // Create version history for new booking
            (new BookingVersionService())->create(
                $bookingId,
                'Booking created',
                'user'
            );

            // Commit Transaction
            $this->db->transComplete();
            if ($this->db->transStatus() === false) {
                $dbError = $this->db->error();
                log_message('error', 'Transaction failed: ' . json_encode($dbError));
                return $this->failServerError('Transaction failed. Please try again.');
            }

            $user = $this->customerModel->where('id', $userId)->first();
            if ($paymentType === 'pay_later') {
                $email = $emailController->sendBookingSuccessEmail($user['email'], $user['name'], $bookingData['booking_id'],  $bookingId);
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
            $page       = $this->request->getVar('page') ?? 1;
            $limit      = $this->request->getVar('limit') ?? 10;
            $offset     = ($page - 1) * $limit;

            $status     = $this->request->getVar('status');
            $search     = $this->request->getVar('search');
            $startDate  = $this->request->getVar('startDate');
            $endDate    = $this->request->getVar('endDate');
            $filter     = $this->request->getVar('filter'); // today, this_week, this_month

            $builder = $this->bookingsModel
                ->select('bookings.*, af_customers.name as user_name')
                ->join('af_customers', 'af_customers.id = bookings.user_id', 'left');

            // ✅ Status filter
            if ($status) {
                $builder->where('bookings.status', $status);
            }

            // ✅ Date range filter
            if ($startDate && $endDate) {
                $builder->where('bookings.created_at >=', $startDate)
                    ->where('bookings.created_at <=', $endDate . ' 23:59:59');
            }

            // ✅ Quick filter: today, this_week, this_month
            if ($filter === 'today') {
                $today = date('Y-m-d');
                $builder->where('DATE(bookings.created_at)', $today);
            } elseif ($filter === 'this_week') {
                $builder->where('YEARWEEK(bookings.created_at, 1) = YEARWEEK(CURDATE(), 1)');
            } elseif ($filter === 'this_month') {
                $builder->where('MONTH(bookings.created_at)', date('m'))
                    ->where('YEAR(bookings.created_at)', date('Y'));
            }

            // ✅ Search filter (in booking_id and customer name)
            if ($search) {
                $builder->groupStart()
                    ->like('bookings.booking_id', $search)
                    ->orLike('af_customers.name', $search)
                    ->groupEnd();
            }

            // ✅ Total filtered records (for pagination)
            $totalFiltered = $builder->countAllResults(false); // keep query for fetching data

            $bookings = $builder
                ->orderBy('bookings.created_at', 'DESC')
                ->findAll($limit, $offset);

            return $this->respond([
                'status' => 200,
                'message' => 'Bookings retrieved successfully.',
                'data' => $bookings,
                'pagination' => [
                    'current_page'   => (int)$page,
                    'per_page'       => (int)$limit,
                    'total_records'  => (int)$totalFiltered,
                    'total_pages'    => ceil($totalFiltered / $limit)
                ]
            ]);
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
                    ->select('booking_services.*, services.name, services.image as service_image')
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
                ->select("
        bookings.*,
        af_customers.name AS user_name,
        af_customers.email AS user_email,
        af_customers.mobile_no AS mobile_no,

        customer_addresses.id AS address_id,
        customer_addresses.house,
        customer_addresses.address,
        customer_addresses.landmark,
        CONCAT_WS(', ',
            customer_addresses.house,
            customer_addresses.address,
            customer_addresses.landmark
        ) AS customer_address
    ")
                //     ->select('bookings.*, af_customers.name as user_name, af_customers.email as user_email, af_customers.mobile_no as mobile_no,
                //   customer_addresses.address as customer_address')
                ->join('af_customers', 'af_customers.id = bookings.user_id', 'left')
                ->join('customer_addresses', 'customer_addresses.id = bookings.address_id', 'left')
                ->where('bookings.id', $booking_id)
                ->first();

            if ($booking) {
                // $booking['customer_addresses'] = [
                //     'id'           => $booking['address_id'],
                //     'customer_id'  => $booking['customer_id'],
                //     'house'        => $booking['house'],
                //     'address'      => $booking['address'],
                //     'landmark'     => $booking['landmark'],
                //     'address_label'         => $booking['address_label'],
                // ];

                // Remove flat address fields
                unset(
                    // $booking['address_id'],
                    // $booking['customer_id'],
                    $booking['house'],
                    $booking['address'],
                    $booking['landmark'],
                    // $booking['address_label'],
                );
            }


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

            // Fetch payment requests
            $paymentRequests = $this->paymentRequestsModel
                ->where('booking_id', $booking_id)
                ->findAll();

            // Fetch expenses related to the booking
            $expenses = $this->bookingExpenseModel
                ->where('booking_id', $booking_id)
                ->findAll();

            return $this->respond([
                'status' => 200,
                'message' => 'Booking retrieved successfully.',
                'data' => [
                    'booking' => $booking,
                    'services' => $services,
                    'payments' => $payments,
                    'payment_requests' => $paymentRequests,
                    'expenses' => $expenses // Added expenses here
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

    public function deleteBooking($booking_id)
    {
        try {
            $booking = $this->bookingsModel->find($booking_id);
            if (!$booking) {
                return $this->respond([
                    'status' => 404,
                    'message' => 'Booking not found.'
                ], 404);
            }

            // Delete related services
            $this->bookingServicesModel->where('booking_id', $booking_id)->delete();

            // Delete related payments
            $this->bookingPaymentsModel->where('booking_id', $booking_id)->delete();

            // Delete related payment requests
            $this->paymentRequestsModel->where('booking_id', $booking_id)->delete();

            // Delete related expenses
            $this->bookingExpenseModel->where('booking_id', $booking_id)->delete();

            // Finally, delete the booking
            $this->bookingsModel->delete($booking_id);

            return $this->respond([
                'status' => 200,
                'message' => 'Booking and all related records deleted successfully.'
            ], 200);
        } catch (\Exception $e) {
            log_message('error', 'Error deleting booking: ' . $e->getMessage());
            return $this->respond([
                'status' => 500,
                'message' => 'Something went wrong while deleting the booking.'
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

            // Create version history
            $reason = ($paymentStatus === 'completed') ? 'Payment completed' : 'Payment status updated';
            (new BookingVersionService())->create(
                $booking['id'],
                $reason,
                'user'
            );

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


    //New function with support of Payments Requests and Post Booking Payments
    // public function verifyPayment()
    // {
    //     try {
    //         $data = $this->request->getJSON(true);

    //         /* -------------------------------------------------
    //      * BASIC VALIDATION
    //      * -------------------------------------------------*/
    //         if (
    //             empty($data['razorpay_payment_id']) ||
    //             empty($data['booking_id']) ||
    //             empty($data['user_id'])
    //         ) {
    //             return $this->failValidationErrors([
    //                 'status'  => 400,
    //                 'message' => 'Missing required payment details.',
    //             ]);
    //         }

    //         $context = $data['payment_context'] ?? 'booking';
    //         $paymentRequestId = $data['payment_request_id'] ?? null;

    //         $allowedContexts = ['booking', 'admin_request', 'post_booking'];
    //         if (!in_array($context, $allowedContexts)) {
    //             return $this->failValidationErrors([
    //                 'status'  => 400,
    //                 'message' => 'Invalid payment context.',
    //             ]);
    //         }

    //         /* -------------------------------------------------
    //      * INIT RAZORPAY
    //      * -------------------------------------------------*/
    //         $config   = new \Config\Razorpay();
    //         $razorpay = new \Razorpay\Api\Api($config->keyId, $config->keySecret);

    //         /* -------------------------------------------------
    //      * FETCH PAYMENT
    //      * -------------------------------------------------*/
    //         $payment = $razorpay->payment->fetch($data['razorpay_payment_id']);
    //         if (!$payment) {
    //             return $this->failNotFound('Payment not found.');
    //         }

    //         $paymentMethod  = $payment->method ?? 'razorpay';
    //         $razorpayStatus = $payment->status;
    //         $paidNow        = $payment->amount / 100; // Razorpay amount is in paise

    //         /* -------------------------------------------------
    //      * VERIFY PAYMENT
    //      * -------------------------------------------------*/
    //         if ($paymentMethod !== 'upi') {

    //             if (empty($data['razorpay_order_id']) || empty($data['razorpay_signature'])) {
    //                 return $this->failValidationErrors([
    //                     'status'  => 400,
    //                     'message' => 'Missing Razorpay signature details.',
    //                 ]);
    //             }

    //             try {
    //                 $razorpay->utility->verifyPaymentSignature([
    //                     'razorpay_order_id'   => $data['razorpay_order_id'],
    //                     'razorpay_payment_id' => $data['razorpay_payment_id'],
    //                     'razorpay_signature'  => $data['razorpay_signature'],
    //                 ]);
    //             } catch (\Exception $e) {

    //                 $this->bookingPaymentsModel->insert([
    //                     'booking_id'      => $data['booking_id'],
    //                     'user_id'         => $data['user_id'],
    //                     'transaction_id'  => $data['razorpay_payment_id'],
    //                     'payment_method'  => $paymentMethod,
    //                     'amount'          => 0,
    //                     'payment_status'  => 'failed',
    //                     'razorpay_status' => 'signature_failed',
    //                     'payment_context' => $context,
    //                     'from_json'       => json_encode(['error' => $e->getMessage()]),
    //                     'created_at'      => date('Y-m-d H:i:s'),
    //                 ]);

    //                 return $this->failValidationErrors([
    //                     'status'  => 400,
    //                     'message' => 'Payment verification failed.',
    //                 ]);
    //             }

    //             if ($razorpayStatus === 'authorized') {
    //                 $payment->capture([
    //                     'amount'   => $payment->amount,
    //                     'currency' => $payment->currency,
    //                 ]);
    //                 $razorpayStatus = 'captured';
    //             }
    //         }

    //         $paymentStatus = ($razorpayStatus === 'captured' || $razorpayStatus === 'authorized')
    //             ? 'completed'
    //             : 'pending';

    //         /* -------------------------------------------------
    //      * FETCH BOOKING
    //      * -------------------------------------------------*/
    //         $booking = $this->bookingsModel->find($data['booking_id']);
    //         if (!$booking) {
    //             return $this->failNotFound('Booking not found.');
    //         }

    //         /* -------------------------------------------------
    //      * CONTEXT-SPECIFIC VALIDATION
    //      * -------------------------------------------------*/
    //         if ($context === 'admin_request') {
    //             if (!$paymentRequestId) {
    //                 return $this->failValidationErrors([
    //                     'status'  => 400,
    //                     'message' => 'Payment request ID required.',
    //                 ]);
    //             }
    //         }

    //         if ($context === 'post_booking' && $booking['amount_due'] <= 0) {
    //             return $this->failValidationErrors([
    //                 'status'  => 400,
    //                 'message' => 'No pending amount for this booking.',
    //             ]);
    //         }

    //         /* -------------------------------------------------
    //      * PREVENT OVERPAYMENT (CRITICAL)
    //      * -------------------------------------------------*/
    //         if ($paidNow > $booking['amount_due']) {

    //             // Record payment but do NOT update booking
    //             $this->bookingPaymentsModel->insert([
    //                 'booking_id'      => $booking['id'],
    //                 'user_id'         => $data['user_id'],
    //                 'transaction_id'  => $data['razorpay_payment_id'],
    //                 'payment_method'  => $paymentMethod,
    //                 'amount'          => $paidNow,
    //                 'currency'        => $payment->currency,
    //                 'payment_status'  => 'excess',
    //                 'razorpay_status' => $razorpayStatus,
    //                 'payment_context' => $context,
    //                 'from_json'       => json_encode($payment),
    //                 'created_at'      => date('Y-m-d H:i:s'),
    //             ]);

    //             return $this->respond([
    //                 'status'  => 409,
    //                 'message' => 'Payment exceeds pending amount. Admin review required.',
    //             ]);
    //         }

    //         /* -------------------------------------------------
    //      * CALCULATE & UPDATE BOOKING
    //      * -------------------------------------------------*/
    //         $newPaidAmount = $booking['paid_amount'] + $paidNow;
    //         $newAmountDue  = max($booking['final_amount'] - $newPaidAmount, 0);

    //         $bookingUpdate = [
    //             'paid_amount' => $newPaidAmount,
    //             'amount_due'  => $newAmountDue,
    //             'updated_at'  => date('Y-m-d H:i:s'),
    //         ];

    //         if ($context === 'booking' && $paymentStatus === 'completed') {
    //             $bookingUpdate['payment_status'] = 'completed';
    //             $bookingUpdate['status']         = 'confirmed';
    //         }

    //         $this->bookingsModel->update($booking['id'], $bookingUpdate);

    //         /* -------------------------------------------------
    //      * UPDATE ADMIN PAYMENT REQUEST
    //      * -------------------------------------------------*/
    //         if ($context === 'admin_request' && $paymentStatus === 'completed') {
    //             $this->bookingPaymentRequestsModel->update($paymentRequestId, [
    //                 'status'  => 'paid',
    //                 'paid_at' => date('Y-m-d H:i:s'),
    //             ]);
    //         }

    //         /* -------------------------------------------------
    //      * VERSION HISTORY
    //      * -------------------------------------------------*/
    //         $reasonMap = [
    //             'booking'       => 'Booking payment completed',
    //             'admin_request' => 'Admin payment request paid',
    //             'post_booking'  => 'Post booking payment received',
    //         ];

    //         (new BookingVersionService())->create(
    //             $booking['id'],
    //             $reasonMap[$context],
    //             'user'
    //         );

    //         /* -------------------------------------------------
    //      * CLEAR CART (ONLY BOOKING FLOW)
    //      * -------------------------------------------------*/
    //         if ($context === 'booking') {
    //             $this->seebCartModel->where('user_id', $data['user_id'])->delete();
    //         }

    //         /* -------------------------------------------------
    //      * STORE PAYMENT RECORD
    //      * -------------------------------------------------*/
    //         $this->bookingPaymentsModel->insert([
    //             'booking_id'         => $booking['id'],
    //             'user_id'            => $data['user_id'],
    //             'transaction_id'     => $data['razorpay_payment_id'],
    //             'payment_method'     => $paymentMethod,
    //             'amount'             => $paidNow,
    //             'currency'           => $payment->currency,
    //             'payment_status'     => $paymentStatus,
    //             'razorpay_status'    => $razorpayStatus,
    //             'payment_context'    => $context,
    //             'payment_request_id' => $paymentRequestId,
    //             'from_json'          => json_encode($payment),
    //             'created_at'         => date('Y-m-d H:i:s'),
    //         ]);

    //         return $this->respond([
    //             'status'  => 200,
    //             'message' => 'Payment verified successfully.',
    //             'data'    => [
    //                 'booking_id'   => $booking['booking_id'],
    //                 'paid_amount'  => $newPaidAmount,
    //                 'amount_due'   => $newAmountDue,
    //                 'context'      => $context,
    //             ],
    //         ]);
    //     } catch (\Exception $e) {
    //         log_message('error', 'Payment Verification Error: ' . $e->getMessage());
    //         return $this->failServerError('Something went wrong.');
    //     }
    // }
    public function verifyPostBookingPayment()
    {
        try {
            $data = $this->request->getJSON(true);

            if (
                empty($data['razorpay_payment_id']) ||
                empty($data['booking_id']) ||
                empty($data['user_id'])
            ) {
                return $this->failValidationErrors([
                    'status'  => 400,
                    'message' => 'Missing required payment details.',
                ]);
            }

            $booking = $this->bookingsModel->find($data['booking_id']);
            if (!$booking) {
                return $this->failNotFound('Booking not found.');
            }

            $razorpay = $this->verifyRazorpayPayment($data);

            if ($razorpay['amount'] > $booking['amount_due']) {
                return $this->failValidationErrors([
                    'message' => 'Payment exceeds pending amount.',
                ]);
            }

            $amounts = $this->calculateBookingAmounts(
                $booking,
                $razorpay['amount']
            );

            $this->updateBookingPaymentStatus(
                $booking['id'],
                $amounts,
                'post_booking'
            );

            $this->createBookingPaymentEntry(
                $booking,
                $razorpay,
                $data,
                'post_booking'
            );

            return $this->respond([
                'status' => 200,
                'message' => 'Payment processed successfully',
                'data' => $amounts,
            ]);
        } catch (\Exception $e) {
            log_message('error', $e->getMessage());
            return $this->failServerError($e->getMessage());
        }
    }

    public function verifyAdminPaymentRequest()
    {
        try {
            $data = $this->request->getJSON(true);

            if (
                empty($data['razorpay_payment_id']) ||
                empty($data['booking_id']) ||
                empty($data['user_id'])
            ) {
                return $this->failValidationErrors([
                    'status'  => 400,
                    'message' => 'Missing required payment details.',
                ]);
            }

            $booking = $this->bookingsModel->find($data['booking_id']);
            if (!$booking) {
                return $this->failNotFound('Booking not found.');
            }

            $razorpay = $this->verifyRazorpayPayment($data);

            if ($razorpay['amount'] > $booking['amount_due']) {
                return $this->failValidationErrors([
                    'message' => 'Payment exceeds pending amount.',
                ]);
            }

            $amounts = $this->calculateBookingAmounts(
                $booking,
                $razorpay['amount']
            );

            $this->updateBookingPaymentStatus(
                $booking['id'],
                $amounts,
                'admin_request'
            );

            $this->updatePaymentRequest(
                'admin_request',
                $data['payment_request_id'] ?? null
            );

            $this->createBookingPaymentEntry(
                $booking,
                $razorpay,
                $data,
                'admin_request'
            );

            return $this->respond([
                'status' => 200,
                'message' => 'Payment processed successfully',
                'data' => $amounts,
            ]);
        } catch (\Exception $e) {
            log_message('error', $e->getMessage());
            return $this->failServerError($e->getMessage());
        }
    }


    private function verifyRazorpayPayment(array $data)
    {
        $config   = new \Config\Razorpay();
        $razorpay = new \Razorpay\Api\Api($config->keyId, $config->keySecret);

        $payment = $razorpay->payment->fetch($data['razorpay_payment_id']);
        if (!$payment) {
            throw new \Exception('Payment not found.');
        }

        $method = $payment->method ?? 'razorpay';
        $status = $payment->status;

        if ($method !== 'upi') {

            if (empty($data['razorpay_order_id']) || empty($data['razorpay_signature'])) {
                throw new \Exception('Missing Razorpay signature.');
            }

            $razorpay->utility->verifyPaymentSignature([
                'razorpay_order_id'   => $data['razorpay_order_id'],
                'razorpay_payment_id' => $data['razorpay_payment_id'],
                'razorpay_signature'  => $data['razorpay_signature'],
            ]);

            if ($status === 'authorized') {
                $payment->capture([
                    'amount'   => $payment->amount,
                    'currency' => $payment->currency,
                ]);
                $status = 'captured';
            }
        }

        if (!in_array($status, ['captured', 'authorized'])) {
            throw new \Exception('Payment not completed.');
        }

        return [
            'payment' => $payment,
            'method'  => $method,
            'status'  => $status,
            'amount'  => $payment->amount / 100,
        ];
    }


    private function calculateBookingAmounts(array $booking, float $paidNow)
    {
        $newPaid = $booking['paid_amount'] + $paidNow;
        $newPaid = min($newPaid, $booking['final_amount']);

        $due = max($booking['final_amount'] - $newPaid, 0);

        if ($newPaid <= 0) {
            $status = 'pending';
        } elseif ($newPaid < $booking['final_amount']) {
            $status = 'partial';
        } else {
            $status = 'completed';
        }

        return [
            'paid_amount'    => $newPaid,
            'amount_due'     => $due,
            'payment_status' => $status,
        ];
    }
    private function updateBookingPaymentStatus(
        int $bookingId,
        array $amounts,
        string $context
    ) {
        $update = [
            'paid_amount'    => $amounts['paid_amount'],
            'amount_due'     => $amounts['amount_due'],
            'payment_status' => $amounts['payment_status'],
            'updated_at'     => date('Y-m-d H:i:s'),
        ];

        if (
            $context === 'booking' &&
            $amounts['payment_status'] === 'completed'
        ) {
            $update['status'] = 'confirmed';
        }

        $this->bookingsModel->update($bookingId, $update);
    }

    private function updatePaymentRequest(
        string $context,
        ?int $requestId
    ) {
        if ($context !== 'admin_request' || !$requestId) {
            return;
        }

        $this->bookingPaymentRequestsModel->update($requestId, [
            'request_status'  => 'completed',
            // 'paid_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function createBookingPaymentEntry(
        array $booking,
        array $razorpay,
        array $data,
        string $context
    ) {
        $this->bookingPaymentsModel->insert([
            'booking_id'      => $booking['id'],
            'user_id'         => $data['user_id'],
            'transaction_id'  => $data['razorpay_payment_id'],
            'payment_method'  => $razorpay['method'],
            'amount'          => $razorpay['amount'],
            'currency'        => $razorpay['payment']->currency,
            'payment_status'  => 'completed',
            'razorpay_status' => $razorpay['status'],
            // 'payment_context' => $context,
            'from_json'       => json_encode($razorpay['payment']),
            'created_at'      => date('Y-m-d H:i:s'),
        ]);
    }

    public function initiatePayment()
    {
        try {
            $data = $this->request->getJSON(true);

            /* ---------------------------------------------
         * BASIC VALIDATION
         * --------------------------------------------*/
            if (empty($data['booking_id']) || empty($data['user_id'])) {
                return $this->failValidationErrors('Booking ID and User ID are required.');
            }

            $context          = $data['payment_context'] ?? 'post_booking';
            $paymentRequestId = $data['payment_request_id'] ?? null;

            $allowedContexts = ['booking', 'admin_request', 'post_booking'];
            if (!in_array($context, $allowedContexts)) {
                return $this->failValidationErrors('Invalid payment context.');
            }

            /* ---------------------------------------------
         * FETCH BOOKING
         * --------------------------------------------*/
            $booking = $this->bookingsModel->find($data['booking_id']);
            if (!$booking) {
                return $this->failNotFound('Booking not found.');
            }

            /* ---------------------------------------------
         * DETERMINE PAYABLE AMOUNT
         * --------------------------------------------*/
            if ($context === 'admin_request') {

                if (!$paymentRequestId) {
                    return $this->failValidationErrors('Payment request ID is required.');
                }

                $request = $this->bookingPaymentRequestsModel->find($paymentRequestId);
                if (!$request || $request['request_status'] !== 'pending') {
                    return $this->failValidationErrors('Invalid or already paid payment request.');
                }

                $amountDue = $request['amount'];
            } else {
                // booking or post_booking
                if ($booking['amount_due'] <= 0) {
                    return $this->failValidationErrors('No pending amount for this booking.');
                }

                $amountDue = $booking['amount_due'];
            }

            // Validate Amount from Frontend
            if (empty($data['amount'])) {
                return $this->failValidationErrors('Payment amount is required.');
            }

            $amountFromFrontend = (float) $data['amount'];
            if ($amountFromFrontend > $amountDue) {
                return $this->failValidationErrors('Payment amount exceeds pending amount.');
            }

            if ($amountFromFrontend <= 0) {
                return $this->failValidationErrors('Payment amount must be greater than zero.');
            }

            /* ---------------------------------------------
         * INIT RAZORPAY
         * --------------------------------------------*/
            $config   = new \Config\Razorpay();
            $razorpay = new \Razorpay\Api\Api($config->keyId, $config->keySecret);

            $receipt = $context . '_' . $booking['id'] . '_' . time();

            /* ---------------------------------------------
         * CREATE RAZORPAY ORDER
         * --------------------------------------------*/
            $razorpayOrder = $razorpay->order->create([
                'amount'          => $amountDue * 100, // paisa
                'currency'        => $config->displayCurrency,
                'receipt'         => $receipt,
                'payment_capture' => 1,
            ]);

            /* ---------------------------------------------
         * STORE ORDER LOCALLY
         * --------------------------------------------*/
            $razorpayOrdersModel = new RazorpayOrdersModel();

            $razorpayOrdersModel->insert([
                'user_id'            => $data['user_id'],
                'booking_id'         => $booking['id'],
                'order_id'           => $razorpayOrder->id,
                'amount'             => $razorpayOrder->amount / 100,
                'currency'           => $razorpayOrder->currency,
                'status'             => $razorpayOrder->status,
                'receipt'            => $razorpayOrder->receipt,
                'payment_context'    => $context,
                'payment_request_id' => $paymentRequestId,
                'created_at'         => date('Y-m-d H:i:s'),
            ]);

            /* ---------------------------------------------
         * RESPONSE FOR FRONTEND
         * --------------------------------------------*/
            return $this->respond([
                'status'  => 200,
                'message' => 'Payment initiated successfully.',
                'data'    => [
                    'razorpay_order' => $razorpayOrder->id,
                    'amount'            => $razorpayOrder->amount,
                    'currency'          => $razorpayOrder->currency,
                    'receipt'           => $razorpayOrder->receipt,
                    'payment_context'   => $context,
                ],
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Razorpay Init Error: ' . $e->getMessage());
            return $this->failServerError('Payment gateway error.');
        }
    }


    // public function webhookRazorpay()
    // {
    //     try {
    //         // Read Webhook Payload
    //         $payload = $this->request->getJSON(true);
    //         log_message('info', 'Webhook Received: ' . json_encode($payload));

    //         if (empty($payload['event']) || empty($payload['payload']['payment']['entity'])) {
    //             return $this->failValidationErrors([
    //                 'status'  => 400,
    //                 'message' => 'Invalid Webhook Payload.',
    //             ]);
    //         }

    //         $event = $payload['event'];
    //         $payment = $payload['payload']['payment']['entity'];
    //         $razorpayPaymentId = $payment['id'];
    //         $razorpayStatus = $payment['status']; // "created", "authorized", "captured", "failed"

    //         // Fetch Payment Details from DB
    //         $existingPayment = $this->bookingPaymentsModel->where('transaction_id', $razorpayPaymentId)->first();

    //         if (!$existingPayment) {
    //             log_message('error', 'Payment not found for Webhook: ' . $razorpayPaymentId);
    //             return $this->failNotFound('Payment record not found.');
    //         }

    //         // Update Payment Record with Razorpay Status Only
    //         $result = $this->bookingPaymentsModel->update($existingPayment['id'], [
    //             'razorpay_status' => $razorpayStatus,
    //             'from_json'       => json_encode($payment),
    //             'updated_at'      => date('Y-m-d H:i:s'),
    //         ]);

    //         if (!$result) {
    //             log_message('error', 'Update failed: ' . json_encode($this->bookingPaymentsModel->errors()));
    //         }


    //         return $this->respond([
    //             'status'  => 200,
    //             'message' => 'Webhook processed successfully.',
    //         ]);
    //     } catch (\Exception $e) {
    //         log_message('error', 'Webhook Error: ' . $e->getMessage());
    //         return $this->failServerError('Something went wrong. ' . $e->getMessage());
    //     }
    // }

    public function addManualPayment()
    {
        try {
            // Start Database Transaction
            $this->db->transStart();

            $data = $this->request->getJSON(true);

            // Validate Required Fields
            if (empty($data['booking_id']) || empty($data['user_id']) || empty($data['amount']) || empty($data['payment_method'])) {
                return $this->failValidationErrors([
                    'status'  => 400,
                    'message' => 'Missing required payment details.',
                ]);
            }

            // // Validate Payment Method
            // $validMethods = ['cash', 'online'];
            // if (!in_array(strtolower($data['payment_method']), $validMethods)) {
            //     return $this->failValidationErrors([
            //         'status'  => 400,
            //         'message' => 'Invalid payment method. Allowed: cash, online.',
            //     ]);
            // }

            // Fetch Booking
            $booking = $this->bookingsModel->where('id', $data['booking_id'])->first();
            if (!$booking) {
                $this->db->transRollback();
                return $this->failNotFound('Booking not found.');
            }

            // Calculate Payment Amount
            $paidAmount = $booking['paid_amount'] + $data['amount'];
            $amountDue = max($booking['final_amount'] - $paidAmount, 0);
            $paymentStatus = ($amountDue == 0) ? 'completed' : 'partial';
            $bookingStatus = ($amountDue == 0) ? 'confirmed' : 'pending';

            // Update Booking Record
            $this->bookingsModel->update($booking['id'], [

                'payment_status' => $paymentStatus,
                'status'         => $bookingStatus,
                'paid_amount'    => $paidAmount,
                'amount_due'     => $amountDue,
                'updated_at'     => date('Y-m-d H:i:s'),
            ]);

            // Create version history
            $reason = ($amountDue == 0) ? 'Manual payment completed' : 'Manual payment received';
            (new BookingVersionService())->create(
                $booking['id'],
                $reason,
                'admin',
                $this->request->getVar('admin_id')
            );

            // Store Payment Record
            $paymentData = [
                'booking_id'      => $booking['id'],
                'user_id'         => $data['user_id'],
                'transaction_id'  => 'TXN_' . uniqid(), // Unique transaction ID for manual payments
                'payment_method'  => strtolower($data['payment_method']),
                'amount'          => $data['amount'],
                'currency'        => 'INR',
                'payment_status'  => 'completed',
                'razorpay_status' => 'manual',
                'from_json'       => json_encode($data),
                'created_at'      => date('Y-m-d H:i:s'),
            ];
            $this->bookingPaymentsModel->insert($paymentData);

            // Complete Transaction
            $this->db->transComplete();

            // Check Transaction Status
            if ($this->db->transStatus() === false) {
                log_message('error', 'Manual Payment Transaction Failed: ' . json_encode($this->db->error()));
                return $this->failServerError('Transaction failed. Please try again.');
            }

            return $this->respond([
                'status'    => 200,
                'message'   => 'Manual payment added successfully.',
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
                ]
            ]);
        } catch (\Exception $e) {
            // Rollback on Exception
            $this->db->transRollback();
            log_message('error', 'Manual Payment Error: ' . $e->getMessage());
            return $this->failServerError('Something went wrong. ' . $e->getMessage());
        }
    }
    public function changeStatus($id = null)
    {
        try {
            $bookingModel = new BookingsModel();

            $booking = $bookingModel->find($id);
            if (!$booking) {
                return $this->respond([
                    'status'  => 404,
                    'message' => 'Booking not found.',
                ], 404);
            }

            $input = $this->request->getJSON(true); // expects: { "status": "confirmed" }

            if (empty($input['status'])) {
                return $this->respond([
                    'status'  => 400,
                    'message' => 'Status field is required.',
                ], 400);
            }

            $bookingModel->update($id, ['status' => $input['status']]);
            log_message('info', "Booking ID $id status changed to " . $input['status']);
            return $this->respond([
                'status'  => 200,
                'message' => 'Booking status updated successfully.',
                'data'    => ['id' => $id, 'new_status' => $input['status']],
            ], 200);
        } catch (\Exception $e) {
            log_message('error', 'Change Status Error: ' . $e->getMessage());
            return $this->respond([
                'status'  => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function webhookRazorpay()
    {
        try {
            $payload = $this->request->getJSON(true);
            log_message('info', 'Webhook Received: ' . json_encode($payload));

            if (empty($payload['event']) || empty($payload['payload']['payment']['entity'])) {
                return $this->failValidationErrors([
                    'status'  => 400,
                    'message' => 'Invalid Webhook Payload.',
                ]);
            }

            $event   = $payload['event'];
            $payment = $payload['payload']['payment']['entity'];
            $paymentId = $payment['id'];
            $orderId   = $payment['order_id'] ?? null;
            $status    = $payment['status'];

            // Find payment
            $existingPayment = $this->bookingPaymentsModel->where('transaction_id', $paymentId)->first();
            $bookingId = $existingPayment['booking_id'] ?? null;

            // Fallback to Razorpay Order if payment not found
            if (!$existingPayment && $orderId) {
                $orderRow = $this->db->table('razorpay_orders')->where('order_id', $orderId)->get()->getRowArray();
                $bookingId = $orderRow['booking_id'] ?? null;

                // Update payment_id in razorpay_orders
                if ($orderRow) {
                    $this->db->table('razorpay_orders')->where('id', $orderRow['id'])->update([
                        'payment_id' => $paymentId,
                        'status'     => $status === 'captured' ? 'paid' : 'failed',
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                }
            }

            if (!$bookingId) {
                return $this->failNotFound('Booking not found from payment or order ID.');
            }

            // 🔄 Event-specific Logic
            switch ($event) {
                case 'payment.authorized':
                    $this->handleAuthorized($bookingId, $payment, $paymentId);
                    break;

                case 'payment.captured':
                    $this->handleCaptured($bookingId, $payment, $paymentId);
                    break;

                case 'payment.failed':
                    $this->handleFailed($bookingId, $payment, $paymentId);
                    break;

                case 'payment.dispute.created':
                    $this->logDispute($bookingId, $payment, 'created');
                    break;

                case 'payment.dispute.won':
                    $this->logDispute($bookingId, $payment, 'won');
                    break;

                case 'payment.dispute.lost':
                    $this->logDispute($bookingId, $payment, 'lost');
                    break;

                case 'payment.dispute.closed':
                    $this->logDispute($bookingId, $payment, 'closed');
                    break;

                default:
                    log_message('warning', 'Unhandled Razorpay Event: ' . $event);
                    break;
            }

            return $this->respond([
                'status' => 200,
                'message' => 'Webhook handled: ' . $event,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Webhook Error: ' . $e->getMessage());
            return $this->failServerError('Error: ' . $e->getMessage());
        }
    }

    private function handleAuthorized($bookingId, $payment, $paymentId)
    {
        $this->bookingPaymentsModel->where('transaction_id', $paymentId)->set([
            'payment_status' => 'pending',
            'razorpay_status' => 'authorized',
            'from_json' => json_encode($payment),
            'updated_at' => date('Y-m-d H:i:s')
        ])->update();

        log_message('info', "Payment Authorized for Booking ID: $bookingId");
    }

    private function handleCaptured($bookingId, $payment, $paymentId)
    {
        $this->bookingPaymentsModel->where('transaction_id', $paymentId)->set([
            'payment_status' => 'completed',
            'razorpay_status' => 'captured',
            'from_json' => json_encode($payment),
            'updated_at' => date('Y-m-d H:i:s')
        ])->update();

        $this->bookingsModel->update($bookingId, [
            'payment_status' => 'completed',
            'status'         => 'confirmed',
            'updated_at'     => date('Y-m-d H:i:s')
        ]);

        // Create version history
        (new BookingVersionService())->create(
            $bookingId,
            'Payment captured and booking confirmed',
            'user'
        );
    }

    private function handleFailed($bookingId, $payment, $paymentId)
    {
        $this->bookingPaymentsModel->where('transaction_id', $paymentId)->set([
            'payment_status' => 'failed',
            'razorpay_status' => 'failed',
            'from_json' => json_encode($payment),
            'updated_at' => date('Y-m-d H:i:s')
        ])->update();

        $this->bookingsModel->update($bookingId, [
            'payment_status' => 'failed',
            'status'         => 'failed_payment',
            'updated_at'     => date('Y-m-d H:i:s')
        ]);

        // Create version history
        (new BookingVersionService())->create(
            $bookingId,
            'Payment failed',
            'user'
        );
    }

    private function logDispute($bookingId, $payment, $status)
    {
        $disputeModel = new PaymentDisputeModel();

        $disputeModel->insert([
            'payment_id' => $payment['id'],
            'booking_id' => $bookingId,
            'status'     => $status,
            'reason'     => $payment['error_description'] ?? null,
            'payload'    => json_encode($payment),
        ]);

        log_message('info', "Dispute $status logged for Booking ID: $bookingId");
    }

    public function addNewServiceToBooking()
    {
        try {
            $data = $this->request->getJSON(true);

            $bookingId = $data['booking_id'] ?? null;
            $services  = $data['services'] ?? [];

            if (!$bookingId || empty($services)) {
                return $this->failValidationErrors('Booking ID and services are required.');
            }

            /** -----------------------------------------
             * Fetch Booking
             * ----------------------------------------*/
            $booking = $this->bookingsModel->find($bookingId);
            if (!$booking) {
                return $this->failNotFound('Booking not found.');
            }

            if ($booking['status'] === 'cancelled') {
                return $this->fail('Cannot modify cancelled booking.');
            }

            $this->db->transStart();

            $newAmount = 0;

            /** -----------------------------------------
             * ADD ONLY NEW SERVICES
             * ----------------------------------------*/
            foreach ($services as $service) {

                /** 1️⃣ Validate service */
                $serviceMaster = $this->servicesModel->find($service['service_id']);
                if (!$serviceMaster) {
                    throw new \RuntimeException('Invalid service selected');
                }

                $rateType = $serviceMaster['rate_type'];
                $rate     = (float) $serviceMaster['rate']; // ✅ DB price

                /** 2️⃣ Resolve addons (DB PRICE → SNAPSHOT) */
                $resolvedAddons = [];

                foreach ($service['addons'] ?? [] as $addonInput) {

                    $addonMaster = $this->addonsModel->find($addonInput['id']);
                    if (!$addonMaster) {
                        continue;
                    }

                    $qty   = (float) $addonInput['qty'];
                    $price = (float) $addonMaster['price'];

                    $resolvedAddons[] = [
                        'id'          => (string) $addonMaster['id'],
                        'name'        => $addonMaster['name'],
                        'price'       => (string) $price,
                        'price_type'  => $addonMaster['price_type'],
                        'qty'         => $qty,
                        'description' => $addonMaster['description'] ?? null,
                        'group_name'  => $addonMaster['group_name'] ?? null,
                        'is_required' => (string) ($addonMaster['is_required'] ?? 0),
                        'total'       => (string) round($qty * $price, 2),
                    ];
                }

                /** 3️⃣ Backend amount calculation */
                $calc = ServiceAmountCalculator::calculate([
                    'rate_type' => $rateType,
                    'value'     => $service['value'],
                    'rate'      => $rate,
                    'addons'    => $resolvedAddons,
                ]);

                /** 4️⃣ Insert booking service (price locked) */
                $this->bookingServicesModel->insert([
                    'booking_id'      => $bookingId,
                    'service_id'      => $service['service_id'],
                    'service_type_id' => $service['service_type_id'],
                    'room_id'         => $service['room_id'],
                    'rate_type'       => $rateType,
                    'value'           => (string) $service['value'],
                    'rate'            => $rate,
                    'amount'          => $calc['total'],
                    'addons'          => json_encode($resolvedAddons),
                    'description'     => $service['description'] ?? null,
                    'reference_image' => $service['reference_image'] ?? null,
                    'created_at'      => date('Y-m-d H:i:s'),
                    'updated_at'      => date('Y-m-d H:i:s'),
                ]);

                $newAmount += $calc['total'];
            }

            /** -----------------------------------------
             * RECALCULATE BOOKING TOTAL
             * ----------------------------------------*/
            $updatedSubtotal = (float) $booking['total_amount'] + $newAmount;

            /** Coupon recalculation */
            $discount = 0.00;

            if (!empty($booking['applied_coupon'])) {
                $coupon = $this->couponsModel
                    ->where('coupon_code', $booking['applied_coupon'])
                    ->first();

                if ($coupon) {
                    if ((int) $coupon['coupon_type'] === 1) {
                        $discount = round(
                            ($updatedSubtotal * $coupon['coupon_type_name']) / 100,
                            2
                        );
                    } elseif ((int) $coupon['coupon_type'] === 2) {
                        $discount = min(
                            (float) $coupon['coupon_type_name'],
                            $updatedSubtotal
                        );
                    }
                }
            }

            $discountedTotal = max(0, $updatedSubtotal - $discount);
            $cgst = round($discountedTotal * 0.09, 2);
            $sgst = round($discountedTotal * 0.09, 2);
            $finalAmount = round($discountedTotal + $cgst + $sgst, 2);
            $amountDue   = round($finalAmount - (float) $booking['paid_amount'], 2);

            /** Update booking */
            $this->bookingsModel->update($bookingId, [
                'total_amount' => $updatedSubtotal,
                'discount'     => $discount,
                'cgst'         => $cgst,
                'sgst'         => $sgst,
                'final_amount' => $finalAmount,
                'amount_due'   => $amountDue,
                'updated_at'   => date('Y-m-d H:i:s'),
            ]);

            /** -----------------------------------------
             * CREATE VERSION HISTORY
             * ----------------------------------------*/
            (new BookingVersionService())->create(
                $bookingId,
                'New service added to booking',
                'user'
            );

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaction failed');
            }

            return $this->respond([
                'status'  => 200,
                'message' => 'New service added successfully',
                'data'    => [
                    'booking_id'   => $bookingId,
                    'subtotal'     => $updatedSubtotal,
                    'discount'     => $discount,
                    'final_amount' => $finalAmount,
                    'amount_due'   => $amountDue,
                ],
            ]);
        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Add New Service Error: ' . $e->getMessage());

            return $this->failServerError('Something went wrong.');
        }
    }

    /**
     * Get original booked services and newly added services (by coordinator/admin)
     * Shows initial services first, then services added later by coordinators
     */
    public function getNewlyAddedServices()
    {
        try {
            $bookingId = $this->request->getVar('booking_id');
            $userId = $this->request->getVar('user_id');

            if (!$bookingId && !$userId) {
                return $this->failValidationErrors('Provide either booking_id or user_id.');
            }

            // Get booking(s)
            $bookings = [];
            if ($bookingId) {
                $booking = $this->bookingsModel->find($bookingId);
                if (!$booking) {
                    return $this->failNotFound('Booking not found.');
                }
                $bookings = [$booking];
            } else {
                // Get all user's bookings
                $bookings = $this->bookingsModel
                    ->where('user_id', $userId)
                    ->orderBy('created_at', 'DESC')
                    ->findAll();

                if (empty($bookings)) {
                    return $this->respond([
                        'status'  => 404,
                        'message' => 'No bookings found for this user.',
                        'data'    => []
                    ]);
                }
            }

            $result = [];

            // Process each booking
            foreach ($bookings as $booking) {
                $bookingCreatedAt = strtotime($booking['created_at']);
                $bookingCreatedAtString = $booking['created_at'];

                // Get all services for this booking
                $allServices = $this->bookingServicesModel
                    ->select('booking_services.*, services.name as service_name, services.description as service_description, services.image as service_image')
                    ->join('services', 'services.id = booking_services.service_id', 'left')
                    ->where('booking_services.booking_id', $booking['id'])
                    ->orderBy('booking_services.created_at', 'ASC')
                    ->findAll();

                if (empty($allServices)) {
                    continue;
                }

                // Separate original services and newly added services
                // Original services = created around the same time as booking (within 5 minutes)
                $originalServices = [];
                $addedLaterServices = [];

                foreach ($allServices as $service) {
                    $serviceCreatedAt = strtotime($service['created_at']);
                    $timeDifference = abs($serviceCreatedAt - $bookingCreatedAt);

                    // If service created within 5 minutes of booking = original service
                    if ($timeDifference <= 300) { // 300 seconds = 5 minutes
                        $originalServices[] = $service;
                    } else {
                        $addedLaterServices[] = $service;
                    }
                }

                // Format original services
                $formattedOriginalServices = array_map(function ($service) {
                    return [
                        'id'                    => $service['id'],
                        'booking_id'            => $service['booking_id'],
                        'service_id'            => $service['service_id'],
                        'service_name'          => $service['service_name'],
                        'service_description'   => $service['service_description'],
                        'service_image'         => $service['service_image'],
                        'room_id'               => $service['room_id'],
                        'rate_type'             => $service['rate_type'],
                        'value'                 => $service['value'],
                        'rate'                  => $service['rate'],
                        'amount'                => $service['amount'],
                        'addons'                => $service['addons'],
                        'booked_at'             => $service['created_at'],
                    ];
                }, $originalServices);

                // Format services added later
                $formattedAddedLaterServices = array_map(function ($service) {
                    return [
                        'id'                    => $service['id'],
                        'booking_id'            => $service['booking_id'],
                        'service_id'            => $service['service_id'],
                        'service_name'          => $service['service_name'],
                        'service_description'   => $service['service_description'],
                        'service_image'         => $service['service_image'],
                        'room_id'               => $service['room_id'],
                        'rate_type'             => $service['rate_type'],
                        'value'                 => $service['value'],
                        'rate'                  => $service['rate'],
                        'amount'                => $service['amount'],
                        'addons'                => $service['addons'],
                        'added_by_coordinator_at' => $service['created_at'],
                    ];
                }, $addedLaterServices);

                $result[] = [
                    'booking_id'             => $booking['id'],
                    'booking_ref'            => $booking['booking_id'],
                    'booking_created_at'     => $bookingCreatedAtString,
                    'total_amount'           => $booking['total_amount'],
                    'discount'               => $booking['discount'],
                    'final_amount'           => $booking['final_amount'],
                    'status'                 => $booking['status'],
                    'original_services'      => $formattedOriginalServices,
                    'original_services_count' => count($formattedOriginalServices),
                    'added_later_services'   => $formattedAddedLaterServices,
                    'added_later_services_count' => count($formattedAddedLaterServices),
                ];
            }

            if (empty($result)) {
                return $this->respond([
                    'status'  => 200,
                    'message' => 'No services found.',
                    'data'    => []
                ]);
            }

            return $this->respond([
                'status'  => 200,
                'message' => 'Services retrieved successfully.',
                'data'    => $result,
                'total_bookings' => count($result)
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Get Newly Added Services Error: ' . $e->getMessage());
            return $this->failServerError('Something went wrong.');
        }
    }

    public function createBookingByAdmin()
    {
        try {
            $data = $this->request->getJSON(true);

            $userId          = $data['user_id'] ?? null;
            $addressId       = $data['address_id'] ?? null;
            $slotDate        = $data['slot_date'] ?? null;
            $services        = $data['services'] ?? [];
            $couponCode      = $data['coupon_code'] ?? null;
            $created_by_id    = $data['created_by_id'] ?? ($this->request->user?->id ?? null);
            $created_by_role  = $data['created_by_role'] ?? ($this->request->user?->role ?? null);
            $manualDiscount  = isset($data['discount_amount'])
                ? (float) $data['discount_amount']
                : null;

            if (!$userId || !$slotDate || empty($services)) {
                return $this->failValidationErrors('Required fields missing.');
            }

            $this->db->transStart();

            /** -----------------------------------------
             * 1️⃣ CREATE EMPTY BOOKING FIRST
             * ----------------------------------------*/
            $bookingId = $this->bookingsModel->insert([
                'booking_id'        => 'SE' . date('YmdHis'),
                'user_id'           => $userId,
                'address_id'        => $addressId,
                'slot_date'         => $slotDate,

                'total_amount'      => 0,
                'discount'          => 0,
                'cgst'              => 0,
                'sgst'              => 0,
                'final_amount'      => 0,
                'paid_amount'       => 0,
                'amount_due'        => 0,

                'status'            => 'user_confirmation_waiting',
                'payment_status'    => 'pending',

                'created_by_type'   => 'admin',
                'created_by_id'     => $created_by_id,
                'created_by_role'   => $created_by_role,

                'created_at'        => date('Y-m-d H:i:s'),
                'updated_at'        => date('Y-m-d H:i:s'),
            ], true);


            // Check if booking insertion failed
            if (!$bookingId) {
                throw new \RuntimeException('Failed to create booking: ' . json_encode($this->bookingsModel->errors()));
            }

            $subtotal = 0;

            /** -----------------------------------------
             * 2️⃣ ADD SERVICES (LOCK PRICES)
             * ----------------------------------------*/
            foreach ($services as $service) {

                $serviceMaster = $this->servicesModel->find($service['service_id']);
                if (!$serviceMaster) {
                    throw new \RuntimeException('Invalid service');
                }

                $rateType = $serviceMaster['rate_type'];
                $rate     = (float) $serviceMaster['rate'];

                $calc = ServiceAmountCalculator::calculate([
                    'rate_type' => $rateType,
                    'value'     => $service['value'],
                    'rate'      => $rate,
                    'addons'    => $service['addons'] ?? [],
                ]);

                if (!$this->bookingServicesModel->insert([
                    'booking_id'  => $bookingId,
                    'service_id'  => $service['service_id'],
                    'service_type_id' => $service['service_type_id'],
                    'room_id'     => $service['room_id'],
                    'reference_image' => $service['reference_image'] ?? null,
                    'rate_type'   => $rateType,
                    'value'       => (string) $service['value'],
                    'rate'        => $rate,
                    'amount'      => $calc['total'],
                    'addons'      => json_encode($service['addons'] ?? []),
                    'created_at'  => date('Y-m-d H:i:s'),
                    'updated_at'  => date('Y-m-d H:i:s'),
                ])) {
                    throw new \RuntimeException('Failed to insert booking service: ' . json_encode($this->bookingServicesModel->errors()));
                }

                $subtotal += $calc['total'];
            }

            /** -----------------------------------------
             * 3️⃣ DISCOUNT LOGIC (NEW)
             * ----------------------------------------*/
            $discount = 0.00;
            $appliedCoupon = null;

            if ($manualDiscount !== null && $manualDiscount > 0) {
                $discount = min($manualDiscount, $subtotal);
            } elseif (!empty($couponCode)) {
                // Use coupon validation service
                $couponValidator = new \App\Services\CouponValidationService();
                $validationResult = $couponValidator->validateAndCalculate(
                    $couponCode,
                    $subtotal,
                    $userId
                );

                if (!$validationResult['valid']) {
                    throw new \Exception($validationResult['message'], 409);
                }

                $discount = $validationResult['discount'];
                $appliedCoupon = $couponCode;
            }

            /** -----------------------------------------
             * 4️⃣ FINAL AMOUNT
             * ----------------------------------------*/
            $discounted = max(0, $subtotal - $discount);
            $cgst = round($discounted * 0.09, 2);
            $sgst = round($discounted * 0.09, 2);
            $finalAmount = round($discounted + $cgst + $sgst, 2);

            /** -----------------------------------------
             * 5️⃣ UPDATE BOOKING TOTALS
             * ----------------------------------------*/
            $this->bookingsModel->update($bookingId, [
                'total_amount'   => $subtotal,
                'discount'       => $discount,
                'cgst'           => $cgst,
                'sgst'           => $sgst,
                'final_amount'   => $finalAmount,
                'amount_due'     => $finalAmount,
                'applied_coupon' => $appliedCoupon,
            ]);

            /** -----------------------------------------
             * 6️⃣ CREATE VERSION v1
             * ----------------------------------------*/
            (new BookingVersionService())->create(
                $bookingId,
                'Booking created by admin',
                'admin',
                $created_by_id
            );

            $this->db->transComplete();

            return $this->respondCreated([
                'booking_id' => $bookingId,
                'message'    => 'Booking created. Waiting for user confirmation.',
                'status'     => 201,
            ]);
        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', $e->getMessage());
            return $this->failServerError('Failed to create booking');
        }
    }

    public function downloadReceipt($paymentId)
    {
        $payment = $this->bookingPaymentsModel
            ->select('booking_payments.*, bookings.booking_id, af_customers.name, af_customers.mobile_no')
            ->join('bookings', 'bookings.id = booking_payments.booking_id')
            ->join('af_customers', 'af_customers.id = booking_payments.user_id')
            ->where('booking_payments.id', $paymentId)
            ->where('booking_payments.payment_status', 'completed')
            ->first();
        if (!$payment) {
            return $this->failNotFound('Receipt not found');
        }

        $html = view('receipts/payment_receipt', [
            'payment' => $payment
        ]);

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader(
                'Content-Disposition',
                'attachment; filename="receipt_' . $payment['booking_id'] . '.pdf"'
            )
            ->setBody($dompdf->output());
    }
}
