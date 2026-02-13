<?php

namespace App\Controllers;

use App\Models\BookingAdditionalServicesModel;
use App\Models\BookingAdjustmentModel;
use App\Models\BookingExpenseModel;
use App\Models\BookingPaymentRequestModel;
use App\Models\BookingsModel;
use App\Models\BookingServicesModel;
use App\Models\BookingPaymentsModel;
use App\Models\RazorpayOrdersModel;
use App\Models\SeebCartModel;
use App\Models\CouponModel;
use App\Models\CustomerModel;
use App\Models\PaymentDisputeModel;
use App\Services\ServiceAmountCalculator;
use CodeIgniter\RESTful\ResourceController;
use Dompdf\Dompdf;

class BookingController extends ResourceController
{
    private const CGST_RATE = 0.00;
    private const SGST_RATE = 0.00;

    protected $bookingsModel;
    protected $bookingServicesModel;
    protected $bookingPaymentsModel;
    protected $seebCartModel;
    protected $couponsModel;
    protected $paymentRequestsModel;
    protected $bookingExpenseModel;
    protected $bookingAdditionalServicesModel;
    protected $bookingAdjustmentModel;
    protected $customerModel;
    protected $servicesModel;
    protected $addonsModel;
    protected $bookingPaymentRequestsModel;
    protected $razorpayOrdersModel;
    protected $bookingAddressModel;
    protected $customerAddressModel;
    protected $db;

    public function __construct()
    {
        $this->bookingsModel = new BookingsModel();
        $this->bookingServicesModel = new BookingServicesModel();
        $this->bookingPaymentsModel = new BookingPaymentsModel();
        $this->seebCartModel = new SeebCartModel();
        $this->couponsModel = new CouponModel();
        $this->paymentRequestsModel = new BookingPaymentRequestModel();
        $this->bookingExpenseModel = new BookingExpenseModel();
        $this->bookingAdditionalServicesModel = new BookingAdditionalServicesModel();
        $this->bookingAdjustmentModel = new BookingAdjustmentModel();
        $this->customerModel = new CustomerModel();
        $this->servicesModel = new \App\Models\ServiceModel();
        $this->addonsModel = new \App\Models\ServiceAddonModel();
        $this->bookingPaymentRequestsModel = new BookingPaymentRequestModel();
        $this->bookingAddressModel = new \App\Models\BookingAddressModel();
        $this->customerAddressModel = new \App\Models\CustomerAddressModel();
        $this->db = \Config\Database::connect();
    }


    public function createBooking()
    {
        try {
            $data = $this->request->getJSON(true) ?? $this->request->getVar();

            // Validate required fields
            if (empty($data['user_id']) || empty($data['payment_type']) || empty($data['address_id'])) {
                return $this->failValidationErrors('Missing required fields: user_id, payment_type, address_id');
            }

            $userId = $data['user_id'];
            $paymentType = $data['payment_type'];
            $appliedCoupon = $data['applied_coupon'] ?? null;

            // Validate payment type
            if (!in_array($paymentType, ['pay_later', 'online'])) {
                return $this->failValidationErrors('Invalid payment_type. Allowed values: pay_later, online');
            }

            // Verify user exists
            $user = $this->customerModel->find($userId);
            if (!$user) {
                return $this->failValidationErrors('User not found.');
            }

            // Fetch cart items for user (only parent items - where parent_cart_id is null)
            $parentCartItems = $this->seebCartModel
                ->where('user_id', $userId)
                ->where('parent_cart_id', null)
                ->findAll();

            if (empty($parentCartItems)) {
                return $this->failValidationErrors('Cart is empty. Add services before booking.');
            }

            // Calculate total from cart
            $subtotal = 0;
            $bookingServices = [];

            foreach ($parentCartItems as $cartItem) {
                // Validate cart item has required fields
                if (empty($cartItem['service_id'])) {
                    return $this->failValidationErrors('Invalid cart item: missing service_id');
                }

                // Get service details
                $service = $this->servicesModel->find($cartItem['service_id']);

                if (!$service) {
                    return $this->failValidationErrors('Service ID ' . $cartItem['service_id'] . ' not found');
                }

                // Calculate service amount with null safety
                $serviceAmount = floatval($cartItem['amount'] ?? 0);

                // Fetch addon items for this parent cart item
                $addonCartItems = $this->seebCartModel
                    ->where('parent_cart_id', $cartItem['id'])
                    ->findAll();

                $addons = [];
                $addonTotal = 0;

                // Process addons
                foreach ($addonCartItems as $addonCart) {
                    if (empty($addonCart['addon_id'])) {
                        continue; // Skip invalid addon entries
                    }

                    $addon = $this->addonsModel->find($addonCart['addon_id']);
                    if ($addon) {
                        $addons[] = [
                            'id' => $addon['id'],
                            'name' => $addon['name'] ?? 'Unknown Addon',
                            'qty' => floatval($addonCart['quantity'] ?? 0),
                            'price' => floatval($addonCart['rate'] ?? 0),
                            'total' => floatval($addonCart['amount'] ?? 0),
                            'unit' => $addonCart['unit'] ?? null,
                        ];
                        $addonTotal += floatval($addonCart['amount'] ?? 0);
                    }
                }

                $itemTotal = $serviceAmount + $addonTotal;
                $subtotal += $itemTotal;

                // Prepare booking service data with null safety
                $bookingServices[] = [
                    'service_id' => $cartItem['service_id'],
                    'service_type_id' => $cartItem['service_type_id'] ?? null,
                    'room_id' => $cartItem['room_id'] ?? null,
                    'quantity' => floatval($cartItem['quantity'] ?? 1),
                    'unit' => $cartItem['unit'] ?? null,
                    'rate' => floatval($cartItem['rate'] ?? 0),
                    'amount' => $serviceAmount,
                    'room_length' => $cartItem['room_length'] ?? null,
                    'room_width' => $cartItem['room_width'] ?? null,
                    'description' => $cartItem['description'] ?? null,
                    'reference_image' => $cartItem['reference_image'] ?? null,
                    'addons' => !empty($addons) ? json_encode($addons) : null,
                ];
            }

            // Apply coupon discount
            $discount = 0.00;
            if (!empty($appliedCoupon)) {
                try {
                    $couponValidator = new \App\Services\CouponValidationService();
                    $validationResult = $couponValidator->validateAndCalculate(
                        $appliedCoupon,
                        $subtotal,
                        $userId
                    );


                    if ($validationResult['valid']) {
                        $discount = $validationResult['discount'];
                    } else {
                        return $this->failValidationErrors($validationResult['message']);
                    }
                } catch (\Exception $e) {
                    log_message('error', 'Coupon validation error: ' . $e->getMessage());
                    return $this->failValidationErrors('Coupon validation failed: ' . $e->getMessage());
                }
            }

            // Calculate final amounts
            $discountedTotal = max(0, $subtotal - $discount);
            $cgstRate = self::CGST_RATE;
            $sgstRate = self::SGST_RATE;
            $cgst = round($discountedTotal * ($cgstRate / 100), 2);
            $sgst = round($discountedTotal * ($sgstRate / 100), 2);
            $tax = $cgst + $sgst;
            $finalAmount = max(0, $discountedTotal + $tax);
            $amountDue = $finalAmount;

            // Booking status logic
            $paymentStatus = 'pending';
            $bookingStatus = 'pending';

            $bookingRef = $this->generateBookingCode();

            // Prepare booking data
            $bookingData = [
                'booking_code'     => $bookingRef,
                'user_id'        => $userId,
                'subtotal_amount' => $subtotal,
                'cgst'           => $cgst,
                'sgst'           => $sgst,
                'cgst_rate'      => $cgstRate,
                'sgst_rate'      => $sgstRate,
                'discount'       => $discount,
                'final_amount'   => $finalAmount,
                'payment_type'   => $paymentType,
                'payment_status' => $paymentStatus,
                'status'         => $bookingStatus,
                'applied_coupon' => $appliedCoupon,
                'slot_date'      => $data['slot_date'] ?? null,
                'created_at'     => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s'),
            ];

            // Start transaction
            $this->db->transStart();
            $transactionStarted = true;

            // Insert booking
            if (!$this->bookingsModel->insert($bookingData)) {
                if ($transactionStarted) {
                    $this->db->transRollback();
                }
                return $this->failValidationErrors([
                    'status'  => 400,
                    'message' => 'Validation failed.',
                    'errors'  => $this->bookingsModel->errors(),
                ]);
            }
            $bookingId = $this->bookingsModel->insertID();

            // Create address snapshot for this booking
            $customerAddress = $this->customerAddressModel->find($data['address_id']);
            if ($customerAddress) {
                $addressSnapshot = [
                    'booking_id'    => $bookingId,
                    'user_id'       => $userId,
                    'house'         => $customerAddress['house'] ?? null,
                    'address'       => $customerAddress['address'] ?? null,
                    'landmark'      => $customerAddress['landmark'] ?? null,
                    'city'          => $customerAddress['city'] ?? null,
                    'state'         => $customerAddress['state'] ?? null,
                    'pincode'       => $customerAddress['pincode'] ?? null,
                    'latitude'      => $customerAddress['latitude'] ?? null,
                    'longitude'     => $customerAddress['longitude'] ?? null,
                    'address_label' => $customerAddress['address_label'] ?? null,
                ];
                $this->bookingAddressModel->insert($addressSnapshot);
            }

            // Insert booking services with parent-child structure for addons
            foreach ($bookingServices as $serviceData) {
                $serviceData['booking_id'] = $bookingId;
                $serviceData['parent_booking_service_id'] = null; // Mark as parent service

                // Extract and decode addons
                $addonsJson = $serviceData['addons'] ?? null;
                unset($serviceData['addons']); // Remove from parent insert

                // Insert parent service
                if (!$this->bookingServicesModel->insert($serviceData)) {
                    if ($transactionStarted) {
                        $this->db->transRollback();
                    }
                    return $this->failValidationErrors([
                        'status'  => 400,
                        'message' => 'Validation failed for booking services.',
                        'errors'  => $this->bookingServicesModel->errors(),
                    ]);
                }

                $parentBookingServiceId = $this->bookingServicesModel->insertID();

                // Insert addons as separate booking service entries
                if (!empty($addonsJson)) {
                    $addons = json_decode($addonsJson, true);
                    if (is_array($addons)) {
                        foreach ($addons as $addon) {
                            $addonServiceData = [
                                'booking_id'                 => $bookingId,
                                'parent_booking_service_id'  => $parentBookingServiceId,
                                'service_id'                 => $serviceData['service_id'], // Same service
                                'addon_id'                   => $addon['id'] ?? null,
                                'quantity'                   => $addon['qty'] ?? 1,
                                'rate'                       => $addon['price'] ?? 0,
                                'amount'                     => $addon['total'] ?? 0,
                                'unit'                       => $addon['unit'] ?? null,
                            ];

                            if (!$this->bookingServicesModel->insert($addonServiceData)) {
                                log_message('error', 'Failed to insert addon: ' . json_encode($this->bookingServicesModel->errors()));
                            }
                        }
                    }
                }
            }

            // Razorpay order for online payments
            $razorpayOrder = null;
            if ($paymentType === 'online') {
                try {
                    $config = new \Config\Razorpay();
                    $razorpay = new \Razorpay\Api\Api($config->keyId, $config->keySecret);
                    $currency = $config->displayCurrency;

                    // Check for existing pending order for this booking
                    $razorpayModel = new RazorpayOrdersModel();
                    $existingOrder = $razorpayModel
                        ->where('booking_id', $bookingId)
                        ->whereIn('status', ['created', 'attempted'])
                        ->orderBy('id', 'DESC')
                        ->first();

                    // Reuse existing order if valid
                    if ($existingOrder) {
                        try {
                            // Verify order still valid on Razorpay
                            $razorpayOrder = $razorpay->order->fetch($existingOrder['razorpay_order_id']);

                            // Check if amount matches and order is still valid
                            $orderAmount = $razorpayOrder->amount / 100;
                            if (
                                abs($orderAmount - $amountDue) < 0.01 &&
                                in_array($razorpayOrder->status, ['created', 'attempted'])
                            ) {
                                log_message('info', 'Reusing existing Razorpay order: ' . $razorpayOrder->id);
                            } else {
                                $razorpayOrder = null; // Force new order creation
                            }
                        } catch (\Exception $e) {
                            log_message('warning', 'Existing order invalid: ' . $e->getMessage());
                            $razorpayOrder = null;
                        }
                    }

                    // Create new order if no valid existing order
                    if (!$razorpayOrder) {
                        $receipt = 'booking_' . $bookingId . '_' . time();
                        $orderData = [
                            'amount'          => $amountDue * 100, // Convert to paisa
                            'currency'        => $currency,
                            'receipt'         => $receipt,
                            'payment_capture' => 1,
                        ];

                        $razorpayOrder = $razorpay->order->create($orderData);

                        // Store new order details
                        $orderRecord = [
                            'user_id'            => $userId,
                            'razorpay_order_id'  => $razorpayOrder->id,
                            'booking_id'         => $bookingId,
                            'amount'             => $razorpayOrder->amount / 100,
                            'currency'           => $currency,
                            'status'             => $razorpayOrder->status,
                            'receipt'            => $razorpayOrder->receipt ?? $receipt,
                            'attempts'           => 0,
                        ];
                        $razorpayModel->insert($orderRecord);
                        log_message('info', 'Created new Razorpay order: ' . $razorpayOrder->id);
                    }
                } catch (\Exception $e) {
                    if ($transactionStarted) {
                        $this->db->transRollback();
                    }
                    log_message('error', 'Razorpay Error: ' . $e->getMessage());
                    return $this->failServerError('Payment gateway error: ' . $e->getMessage());
                }
            }

            // Remove booked items from cart (only for 'pay_later')
            if ($paymentType === 'pay_later') {
                $this->seebCartModel->where('user_id', $userId)->delete();
            }

            // Commit transaction
            $this->db->transComplete();
            if ($this->db->transStatus() === false) {
                $dbError = $this->db->error();
                log_message('error', 'Transaction failed: ' . json_encode($dbError));
                return $this->failServerError('Transaction failed. Please try again.');
            }

            // Send email notification for pay_later bookings
            if ($paymentType === 'pay_later' && !empty($user['email'])) {
                try {
                    $emailController = new EmailController();
                    $emailController->sendBookingSuccessEmail(
                        $user['email'],
                        $user['name'] ?? 'Customer',
                        $bookingData['booking_code'],
                        $bookingId
                    );
                } catch (\Exception $e) {
                    log_message('error', 'Email sending failed: ' . $e->getMessage());
                    // Don't fail the booking if email fails
                }
            }

            return $this->respondCreated([
                'status'  => 201,
                'message' => 'Booking successfully created!',
                'data' => [
                    'id'             => $bookingId,
                    'booking_id'     => $bookingData['booking_code'],
                    'amount'         => $razorpayOrder ? ($razorpayOrder->amount / 100) : $amountDue,
                    'razorpay_order' => $razorpayOrder ? $razorpayOrder->id : null
                ]
            ]);
        } catch (\Exception $e) {
            if (!empty($transactionStarted)) {
                $this->db->transRollback();
            }
            // Transaction auto-rollback handled by transComplete() on failure
            log_message('error', 'Booking Error: ' . $e->getMessage());
            return $this->failServerError('Something went wrong. Please try again.');
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
                ->select('bookings.*, customers.name as user_name')
                ->join('customers', 'customers.id = bookings.user_id', 'left');

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

            // ✅ Search filter (in booking_code and customer name)
            if ($search) {
                $builder->groupStart()
                    ->like('bookings.booking_code', $search)
                    ->orLike('customers.name', $search)
                    ->groupEnd();
            }

            // ✅ Total filtered records (for pagination)
            $totalFiltered = $builder->countAllResults(false); // keep query for fetching data

            $bookings = $builder
                ->orderBy('bookings.created_at', 'DESC')
                ->findAll($limit, $offset);

            $this->appendPaidDueToBookings($bookings);

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
                ->select('bookings.*, customers.name as user_name')
                ->join('customers', 'customers.id = bookings.user_id', 'left')
                ->where('bookings.user_id', $user_id)
                ->orderBy('bookings.created_at', 'DESC')
                ->findAll();

            if (empty($bookings)) {
                return $this->respond([
                    'status' => 404,
                    'message' => 'No bookings found for this user.'
                ], 404);
            }

            // Paid/due recalculated after dynamic totals

            // Fetch booking services for each booking
            foreach ($bookings as &$booking) {
                // Fetch parent services
                $parentServices = $this->bookingServicesModel
                    ->select('booking_services.*, services.name, services.image as service_image')
                    ->join('services', 'services.id = booking_services.service_id', 'left')
                    ->where('booking_services.booking_id', $booking['id'])
                    ->where('booking_services.parent_booking_service_id', null)
                    ->findAll();

                // Fetch addons for each parent service
                foreach ($parentServices as &$parentService) {
                    $addonServices = $this->bookingServicesModel
                        ->select('booking_services.*, service_addons.name as addon_name')
                        ->join('service_addons', 'service_addons.id = booking_services.addon_id', 'left')
                        ->where('booking_services.parent_booking_service_id', $parentService['id'])
                        ->findAll();

                    $parentService['addons'] = $addonServices;
                }

                $booking['services'] = $parentServices;

                // Fetch additional services (parent only)
                $additionalParents = $this->bookingAdditionalServicesModel
                    ->select('booking_additional_services.*, services.name as service_name, services.image as service_image')
                    ->join('services', 'services.id = booking_additional_services.service_id', 'left')
                    ->where('booking_additional_services.booking_id', $booking['id'])
                    ->where('booking_additional_services.parent_booking_service_id', null)
                    ->findAll();

                foreach ($additionalParents as &$additionalParent) {
                    $additionalAddons = $this->bookingAdditionalServicesModel
                        ->select('booking_additional_services.*, service_addons.name as addon_name')
                        ->join('service_addons', 'service_addons.id = booking_additional_services.addon_id', 'left')
                        ->where('booking_additional_services.parent_booking_service_id', $additionalParent['id'])
                        ->findAll();

                    $additionalParent['addons'] = $additionalAddons;
                }
                unset($additionalParent);

                $booking['additional_services'] = $additionalParents;

                // Fetch adjustments
                $booking['adjustments'] = $this->bookingAdjustmentModel
                    ->where('booking_id', $booking['id'])
                    ->orderBy('created_at', 'DESC')
                    ->findAll();

                // Recalculate final and due with approved additional services and adjustments
                $totals = $this->calculateBookingFinalWithExtras(
                    (int) $booking['id'],
                    (float) ($booking['final_amount'] ?? 0)
                );
                $paidAmount = $this->getTotalPaidAmount((int) $booking['id']);
                $booking['paid_amount'] = $paidAmount;
                $booking['calculated_final_amount'] = $totals['final_amount'];
                $booking['amount_due'] = max($booking['calculated_final_amount'] - $paidAmount, 0);
                $booking['additional_approved_total'] = $totals['additional_approved_total'];
                $booking['adjustments_total'] = $totals['adjustments_total'];
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
            // Fetch booking details with user
            $booking = $this->bookingsModel
                ->select('bookings.*, customers.name AS user_name, customers.email AS user_email, customers.mobile_no AS mobile_no')
                ->join('customers', 'customers.id = bookings.user_id', 'left')
                ->where('bookings.id', $booking_id)
                ->first();

            if ($booking) {
                // Fetch address snapshot from booking_addresses
                $bookingAddress = $this->bookingAddressModel
                    ->where('booking_id', $booking_id)
                    ->first();

                if ($bookingAddress) {
                    $booking['customer_address'] = trim(implode(', ', array_filter([
                        $bookingAddress['house'],
                        $bookingAddress['address'],
                        $bookingAddress['landmark']
                    ])));
                    $booking['address_details'] = $bookingAddress;
                } else {
                    $booking['customer_address'] = null;
                    $booking['address_details'] = null;
                }

                // Paid/due recalculated after dynamic totals
            }


            if (!$booking) {
                return $this->respond([
                    'status' => 404,
                    'message' => 'Booking not found.'
                ], 404);
            }

            // Fetch booking services (only parent services)
            $parentServices = $this->bookingServicesModel
                ->select('booking_services.*, services.name as service_name, services.description as service_description, services.image as service_image')
                ->join('services', 'services.id = booking_services.service_id', 'left')
                ->where('booking_services.booking_id', $booking_id)
                ->where('booking_services.parent_booking_service_id', null)
                ->findAll();

            // Fetch addons for each parent service
            foreach ($parentServices as &$parentService) {
                $addonServices = $this->bookingServicesModel
                    ->select('booking_services.*, service_addons.name as addon_name, service_addons.description as addon_description')
                    ->join('service_addons', 'service_addons.id = booking_services.addon_id', 'left')
                    ->where('booking_services.parent_booking_service_id', $parentService['id'])
                    ->findAll();

                $parentService['addons'] = $addonServices;
            }

            $services = $parentServices;

            // Fetch additional services (only parent services)
            $additionalParentServices = $this->bookingAdditionalServicesModel
                ->select('booking_additional_services.*, services.name as service_name, services.description as service_description, services.image as service_image')
                ->join('services', 'services.id = booking_additional_services.service_id', 'left')
                ->where('booking_additional_services.booking_id', $booking_id)
                ->where('booking_additional_services.parent_booking_service_id', null)
                ->findAll();

            // Fetch addons for each additional parent service
            foreach ($additionalParentServices as &$additionalParent) {
                $additionalAddons = $this->bookingAdditionalServicesModel
                    ->select('booking_additional_services.*, service_addons.name as addon_name, service_addons.description as addon_description')
                    ->join('service_addons', 'service_addons.id = booking_additional_services.addon_id', 'left')
                    ->where('booking_additional_services.parent_booking_service_id', $additionalParent['id'])
                    ->findAll();

                $additionalParent['addons'] = $additionalAddons;
            }

            $additionalServices = $additionalParentServices;

            // Fetch payment details
            $payments = $this->bookingPaymentsModel
                ->where('booking_id', $booking_id)
                ->findAll();

            // Fetch payment requests
            $paymentRequests = $this->paymentRequestsModel
                ->where('booking_id', $booking_id)
                ->whereIn('status', ['pending'])
                ->findAll();

            // Fetch adjustments
            $adjustments = $this->bookingAdjustmentModel
                ->where('booking_id', $booking_id)
                ->orderBy('created_at', 'DESC')
                ->findAll();

            // Recalculate final and due with approved additional services and adjustments
            $totals = $this->calculateBookingFinalWithExtras(
                (int) $booking['id'],
                (float) ($booking['final_amount'] ?? 0)
            );
            $paidAmount = $this->getTotalPaidAmount((int) $booking['id']);
            $booking['paid_amount'] = $paidAmount;
            $booking['calculated_final_amount'] = $totals['final_amount'];
            $booking['amount_due'] = max($booking['calculated_final_amount'] - $paidAmount, 0);
            $booking['additional_approved_total'] = $totals['additional_approved_total'];
            $booking['adjustments_total'] = $totals['adjustments_total'];

            return $this->respond([
                'status' => 200,
                'message' => 'Booking retrieved successfully.',
                'data' => [
                    'booking' => $booking,
                    'services' => $services,
                    'additional_services' => $additionalServices,
                    'payments' => $payments,
                    'payment_requests' => $paymentRequests,
                    'adjustments' => $adjustments,
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
    public function getBookingDetails($booking_id)
    {
        try {
            // Fetch booking details with user
            $booking = $this->bookingsModel
                ->select('bookings.*, customers.name AS user_name, customers.email AS user_email, customers.mobile_no AS mobile_no')
                ->join('customers', 'customers.id = bookings.user_id', 'left')
                ->where('bookings.id', $booking_id)
                ->first();

            if ($booking) {
                // Fetch address snapshot from booking_addresses
                $bookingAddress = $this->bookingAddressModel
                    ->where('booking_id', $booking_id)
                    ->first();

                if ($bookingAddress) {
                    $booking['customer_address'] = trim(implode(', ', array_filter([
                        $bookingAddress['house'],
                        $bookingAddress['address'],
                        $bookingAddress['landmark']
                    ])));
                    $booking['address_details'] = $bookingAddress;
                } else {
                    $booking['customer_address'] = null;
                    $booking['address_details'] = null;
                }

                // Paid/due recalculated after dynamic totals
            }


            if (!$booking) {
                return $this->respond([
                    'status' => 404,
                    'message' => 'Booking not found.'
                ], 404);
            }

            // Fetch booking services (only parent services)
            $parentServices = $this->bookingServicesModel
                ->select('booking_services.*, services.name as service_name, services.partner_price as partner_price, services.with_material as with_material')
                ->join('services', 'services.id = booking_services.service_id', 'left')
                ->where('booking_services.booking_id', $booking_id)
                ->where('booking_services.parent_booking_service_id', null)
                ->findAll();

            // Fetch addons for each parent service
            foreach ($parentServices as &$parentService) {
                $addonServices = $this->bookingServicesModel
                    ->select('booking_services.*, service_addons.name as addon_name, service_addons.partner_price as partner_price')
                    ->join('service_addons', 'service_addons.id = booking_services.addon_id', 'left')
                    ->where('booking_services.parent_booking_service_id', $parentService['id'])
                    ->findAll();

                $parentWithMaterial = $parentService['with_material'] ?? null;
                foreach ($addonServices as &$addonService) {
                    $addonService['with_material'] = $parentWithMaterial;
                }
                unset($addonService);

                $parentService['addons'] = $addonServices;
            }

            $services = $parentServices;

            $additionalParentServices = $this->bookingAdditionalServicesModel
                ->select('booking_additional_services.*, services.name as service_name, services.partner_price as partner_price, services.with_material as with_material')
                ->join('services', 'services.id = booking_additional_services.service_id', 'left')
                ->where('booking_additional_services.booking_id', $booking_id)
                ->where('booking_additional_services.parent_booking_service_id', null)
                ->findAll();

            // Fetch addons for each additional parent service
            foreach ($additionalParentServices as &$additionalParent) {
                $additionalAddons = $this->bookingAdditionalServicesModel
                    ->select('booking_additional_services.*, service_addons.name as addon_name, service_addons.partner_price as partner_price')
                    ->join('service_addons', 'service_addons.id = booking_additional_services.addon_id', 'left')
                    ->where('booking_additional_services.parent_booking_service_id', $additionalParent['id'])
                    ->findAll();

                $parentWithMaterial = $additionalParent['with_material'] ?? null;
                foreach ($additionalAddons as &$additionalAddon) {
                    $additionalAddon['with_material'] = $parentWithMaterial;
                }
                unset($additionalAddon);

                $additionalParent['addons'] = $additionalAddons;
            }

            $additionalServices = $additionalParentServices;

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

            // Fetch adjustments
            $adjustments = $this->bookingAdjustmentModel
                ->where('booking_id', $booking_id)
                ->orderBy('created_at', 'DESC')
                ->findAll();

            // Recalculate final and due with approved additional services and adjustments
            $totals = $this->calculateBookingFinalWithExtras(
                (int) $booking['id'],
                (float) ($booking['final_amount'] ?? 0)
            );
            $paidAmount = $this->getTotalPaidAmount((int) $booking['id']);
            $booking['paid_amount'] = $paidAmount;
            $booking['calculated_final_amount'] = $totals['final_amount'];
            $booking['amount_due'] = max($booking['calculated_final_amount'] - $paidAmount, 0);
            $booking['additional_approved_total'] = $totals['additional_approved_total'];
            $booking['adjustments_total'] = $totals['adjustments_total'];

            return $this->respond([
                'status' => 200,
                'message' => 'Booking retrieved successfully.',
                'data' => [
                    'booking' => $booking,
                    'services' => $services,
                    'additional_services' => $additionalServices,
                    'payments' => $payments,
                    'payment_requests' => $paymentRequests,
                    'expenses' => $expenses, // Added expenses here
                    'adjustments' => $adjustments,
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
                        'booking_id'         => $data['booking_id'],
                        'user_id'            => $data['user_id'],
                        'payment_gateway'    => 'razorpay',
                        'payment_method'     => $paymentMethod,
                        'gateway_payment_id' => $data['razorpay_payment_id'],
                        'amount'             => 0,
                        'currency'           => $payment->currency ?? 'INR',
                        'status'             => 'failed',
                        'created_at'         => date('Y-m-d H:i:s'),
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

            $amounts = $this->calculateBookingAmounts(
                $booking,
                $payment->amount / 100
            );

            if ($amounts['payment_status'] === 'completed') {
                $bookingStatus = 'confirmed';
            }

            // Update Booking Record
            $this->bookingsModel->update($booking['id'], [
                'payment_status' => $amounts['payment_status'],
                'status'         => $bookingStatus,
                'updated_at'     => date('Y-m-d H:i:s'),
            ]);

            if ($amounts['payment_status'] === 'completed') {
                $this->seebCartModel->where('user_id', $data['user_id'])->delete();
            }
            // Store Payment Record
            $paymentData = [
                'booking_id'         => $booking['id'],
                'user_id'            => $data['user_id'],
                'payment_gateway'    => 'razorpay',
                'payment_method'     => $paymentMethod,
                'gateway_payment_id' => $data['razorpay_payment_id'],
                'amount'             => $payment->amount / 100,
                'currency'           => $payment->currency,
                'status'             => $amounts['payment_status'] === 'completed' ? 'success' : 'pending',
                'paid_at'            => $amounts['payment_status'] === 'completed' ? date('Y-m-d H:i:s') : null,
                'created_at'         => date('Y-m-d H:i:s'),
            ];
            $this->bookingPaymentsModel->insert($paymentData);

            return $this->respond([
                'status'    => 200,
                'message'   => 'Payment verified and updated.',
                'data'      => [
                    'id'            => $booking['id'],
                    'booking_code'  => $booking['booking_code'],
                    'booking'       => [
                        'status'       => $bookingStatus,
                        'paid_amount'  => $amounts['paid_amount'],
                        'amount_due'   => $amounts['amount_due'],
                    ],
                    'payment'       => $paymentData,
                    'payment_status' => $amounts['payment_status'],
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

            $amountDue = max(
                (float) $booking['final_amount'] - $this->getTotalPaidAmount((int) $booking['id']),
                0
            );

            if ($razorpay['amount'] > $amountDue) {
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

            $amountDue = max(
                (float) $booking['final_amount'] - $this->getTotalPaidAmount((int) $booking['id']),
                0
            );

            if ($razorpay['amount'] > $amountDue) {
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


    private function getTotalPaidAmount(int $bookingId): float
    {
        $row = $this->bookingPaymentsModel
            ->selectSum('amount')
            ->where('booking_id', $bookingId)
            ->where('status', 'success')
            ->first();

        return (float) ($row['amount'] ?? 0);
    }

    private function appendPaidDueToBookings(array &$bookings): void
    {
        if (empty($bookings)) {
            return;
        }

        $bookingIds = array_map(static fn($row) => (int) $row['id'], $bookings);

        $rows = $this->bookingPaymentsModel
            ->select('booking_id, SUM(amount) as paid_amount')
            ->whereIn('booking_id', $bookingIds)
            ->where('status', 'success')
            ->groupBy('booking_id')
            ->findAll();

        $paidMap = [];
        foreach ($rows as $row) {
            $paidMap[(int) $row['booking_id']] = (float) $row['paid_amount'];
        }

        foreach ($bookings as &$booking) {
            $paidAmount = $paidMap[(int) $booking['id']] ?? 0.0;
            $finalAmount = (float) ($booking['final_amount'] ?? 0);

            $booking['paid_amount'] = $paidAmount;
            $booking['amount_due'] = max($finalAmount - $paidAmount, 0);
        }
        unset($booking);
    }


    private function calculateBookingAmounts(array $booking, float $paidNow)
    {
        $paidSoFar = $this->getTotalPaidAmount((int) $booking['id']);
        $newPaid = $paidSoFar + $paidNow;
        $newPaid = min($newPaid, (float) $booking['final_amount']);

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

    private function evaluateCouponForSubtotal(?array $coupon, float $subtotal): array
    {
        if (empty($coupon)) {
            return ['is_valid' => false, 'discount' => 0.0];
        }

        $minAmount = (float) ($coupon['cart_minimum_amount'] ?? 0);
        if ($subtotal < $minAmount) {
            return ['is_valid' => false, 'discount' => 0.0];
        }

        $discount = 0.0;
        $type = (int) ($coupon['coupon_type'] ?? 0);
        $value = (float) ($coupon['coupon_type_name'] ?? 0);

        if ($type === 1) {
            $discount = round(($subtotal * $value) / 100, 2);
        } elseif ($type === 2) {
            $discount = $value;
        }

        $discount = min($discount, $subtotal);

        return ['is_valid' => true, 'discount' => $discount];
    }

    private function calculateBookingFinalWithExtras(int $bookingId, float $baseFinalAmount): array
    {
        $additionalRow = $this->bookingAdditionalServicesModel
            ->selectSum('total_amount')
            ->where('booking_id', $bookingId)
            ->where('status', 'approved')
            ->first();

        $additionalApprovedTotal = (float) ($additionalRow['total_amount'] ?? 0);

        $adjustments = $this->bookingAdjustmentModel
            ->where('booking_id', $bookingId)
            ->findAll();

        $adjustmentsTotal = 0.0;
        foreach ($adjustments as $adjustment) {
            $amount = (float) ($adjustment['amount'] ?? 0);
            $cgstAmount = (float) ($adjustment['cgst_amount'] ?? 0);
            $sgstAmount = (float) ($adjustment['sgst_amount'] ?? 0);
            $lineTotal = $amount + $cgstAmount + $sgstAmount;

            $isAddition = (int) ($adjustment['is_addition'] ?? 0) === 1;
            $adjustmentsTotal += $isAddition ? $lineTotal : (-1 * $lineTotal);
        }

        $finalAmount = max($baseFinalAmount + $additionalApprovedTotal + $adjustmentsTotal, 0);

        return [
            'final_amount' => $finalAmount,
            'additional_approved_total' => $additionalApprovedTotal,
            'adjustments_total' => $adjustmentsTotal,
        ];
    }

    private function createBookingPaymentEntry(
        array $booking,
        array $razorpay,
        array $data,
        string $context
    ) {
        $this->bookingPaymentsModel->insert([
            'booking_id'         => $booking['id'],
            'user_id'            => $data['user_id'],
            'payment_gateway'    => 'razorpay',
            'payment_method'     => $razorpay['method'],
            'gateway_payment_id' => $data['razorpay_payment_id'],
            'amount'             => $razorpay['amount'],
            'currency'           => $razorpay['payment']->currency,
            'status'             => 'success',
            'paid_at'            => date('Y-m-d H:i:s'),
            'created_at'         => date('Y-m-d H:i:s'),
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
                $amountDue = max(
                    (float) $booking['final_amount'] - $this->getTotalPaidAmount((int) $booking['id']),
                    0
                );

                if ($amountDue <= 0) {
                    return $this->failValidationErrors('No pending amount for this booking.');
                }
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
                'razorpay_order_id'  => $razorpayOrder->id,
                'amount'             => $razorpayOrder->amount / 100,
                'currency'           => $razorpayOrder->currency,
                'status'             => $razorpayOrder->status,
                'receipt'            => $razorpayOrder->receipt,
                'attempts'           => 0,
            ]);

            /* ---------------------------------------------
         * RESPONSE FOR FRONTEND
         * --------------------------------------------*/
            return $this->respond([
                'status'  => 200,
                'message' => 'Payment initiated successfully.',
                'data'    => [
                    'razorpay_order_id' => $razorpayOrder->id,
                    'amount'            => $razorpayOrder->amount,
                    'currency'          => $razorpayOrder->currency,
                    'receipt'           => $razorpayOrder->receipt,
                ],
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Razorpay Init Error: ' . $e->getMessage());
            return $this->failServerError('Payment gateway error.');
        }
    }

    public function cancelService()
    {
        try {
            $data = $this->request->getJSON(true) ?? $this->request->getVar();

            // Validate required fields
            if (empty($data['booking_id']) || empty($data['service_id']) || empty($data['service_source'])) {
                return $this->failValidationErrors([
                    'status' => 400,
                    'message' => 'Missing required fields: booking_id, service_id, service_source'
                ]);
            }

            $bookingId = (int) $data['booking_id'];
            $serviceId = (int) $data['service_id'];
            $serviceSource = $data['service_source']; // 'booking_service' or 'additional_service'
            $refundReason = $data['refund_reason'] ?? null;
            $notes = $data['notes'] ?? null;
            $addonIds = $data['addon_ids'] ?? [];

            // Validate service_source
            if (!in_array($serviceSource, ['booking_service', 'additional_service'])) {
                return $this->failValidationErrors([
                    'status' => 400,
                    'message' => 'Invalid service_source. Allowed: booking_service, additional_service'
                ]);
            }

            // Fetch booking
            $booking = $this->bookingsModel->find($bookingId);
            if (!$booking) {
                return $this->failNotFound('Booking not found.');
            }

            // Fetch service record
            $model = ($serviceSource === 'booking_service') ? $this->bookingServicesModel : $this->bookingAdditionalServicesModel;
            $service = $model->find($serviceId);

            if (!$service) {
                return $this->failNotFound('Service not found.');
            }

            // Check if service is already cancelled
            if ($service['status'] === 'cancelled') {
                return $this->respond([
                    'status' => 400,
                    'message' => 'Service is already cancelled.'
                ], 400);
            }

            // Check if it's a parent service
            $isParentService = empty($service['parent_booking_service_id']);

            // Start transaction
            $this->db->transStart();

            // Update service status to cancelled
            $model->update($serviceId, [
                'status' => 'cancelled',
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            // If parent service, cancel all addons
            $totalRefundAmount = floatval($service['amount'] ?? 0);
            $addonRecords = [];

            if ($isParentService) {
                $addons = $model
                    ->where('parent_booking_service_id', $serviceId)
                    ->findAll();

                foreach ($addons as $addon) {
                    $model->update($addon['id'], [
                        'status' => 'cancelled',
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $totalRefundAmount += floatval($addon['amount'] ?? 0);
                    $addonRecords[] = $addon;
                }
            }

            // Apply booking-level discount only for booking services
            $bookingDiscountBefore = (float) ($booking['discount'] ?? 0);
            $discountAfter = $bookingDiscountBefore;
            $discountRemoved = 0.0;

            if ($serviceSource === 'booking_service') {
                $couponCode = $booking['applied_coupon'] ?? null;
                $coupon = $couponCode
                    ? $this->couponsModel->where('coupon_code', $couponCode)->first()
                    : null;

                $remainingSubtotal = (float) $this->bookingServicesModel
                    ->selectSum('amount')
                    ->where('booking_id', $bookingId)
                    ->where('status !=', 'cancelled')
                    ->get()
                    ->getRow('amount');

                $couponResult = $this->evaluateCouponForSubtotal($coupon, $remainingSubtotal);
                $discountAfter = (float) ($couponResult['discount'] ?? 0);
                $discountRemoved = max($bookingDiscountBefore - $discountAfter, 0);

                $discountedTotal = max($remainingSubtotal - $discountAfter, 0);
                $bookingCgstRate = (float) ($booking['cgst_rate'] ?? self::CGST_RATE);
                $bookingSgstRate = (float) ($booking['sgst_rate'] ?? self::SGST_RATE);
                $bookingCgst = round($discountedTotal * ($bookingCgstRate / 100), 2);
                $bookingSgst = round($discountedTotal * ($bookingSgstRate / 100), 2);
                $bookingFinal = round($discountedTotal + $bookingCgst + $bookingSgst, 2);

                $this->bookingsModel->update($bookingId, [
                    'subtotal_amount' => $remainingSubtotal,
                    'discount'        => $discountAfter,
                    'cgst'            => $bookingCgst,
                    'sgst'            => $bookingSgst,
                    'final_amount'    => $bookingFinal,
                    'updated_at'      => date('Y-m-d H:i:s'),
                ]);
            }

            $refundBaseAmount = max($totalRefundAmount - $discountRemoved, 0);

            // Calculate GST on refund (if applicable)
            // First try service rates, then fallback to booking rates, then class constants
            $cgstRate = floatval($service['cgst_rate'] ?? $booking['cgst_rate'] ?? self::CGST_RATE);
            $sgstRate = floatval($service['sgst_rate'] ?? $booking['sgst_rate'] ?? self::SGST_RATE);
            $cgstAmount = round($refundBaseAmount * ($cgstRate / 100), 2);
            $sgstAmount = round($refundBaseAmount * ($sgstRate / 100), 2);
            $totalRefundWithGST = $refundBaseAmount + $cgstAmount + $sgstAmount;

            // Create adjustment record
            $adjustmentRecord = [
                'booking_id' => $bookingId,
                'user_id' => $booking['user_id'],
                'adjustment_type' => 'refund',
                'service_source' => $serviceSource,
                'service_id' => $serviceId,
                'reason' => $refundReason ?? 'Service cancellation',
                'notes' => $notes,
                'refund_amount' => $refundBaseAmount,
                'cgst_rate' => $cgstRate,
                'sgst_rate' => $sgstRate,
                'cgst_amount' => $cgstAmount,
                'sgst_amount' => $sgstAmount,
                'total_refund_amount' => $totalRefundWithGST,
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            // Insert adjustment record (using booking expense model or creating new one)
            // For now, we'll use bookingExpenseModel with type='refund'
            $this->bookingExpenseModel->insert($adjustmentRecord);
            $adjustmentId = $this->bookingExpenseModel->insertID();

            // Check if all services in booking are cancelled
            $activeServices = $this->bookingServicesModel
                ->where('booking_id', $bookingId)
                ->where('status !=', 'cancelled')
                ->countAllResults();

            $activeAdditionalServices = $this->bookingAdditionalServicesModel
                ->where('booking_id', $bookingId)
                ->where('status !=', 'cancelled')
                ->countAllResults();

            $allCancelled = ($activeServices == 0) && ($activeAdditionalServices == 0);

            // If all services cancelled, update booking status
            if ($allCancelled) {
                $this->bookingsModel->update($bookingId, [
                    'status' => 'cancelled',
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }

            // Commit transaction
            $this->db->transComplete();
            if ($this->db->transStatus() === false) {
                $dbError = $this->db->error();
                log_message('error', 'Service cancellation transaction failed: ' . json_encode($dbError));
                return $this->failServerError('Transaction failed. Please try again.');
            }

            return $this->respond([
                'status' => 200,
                'message' => $allCancelled ? 'Service cancelled and all booking services are now cancelled.' : 'Service cancelled successfully.',
                'data' => [
                    'service_id' => $serviceId,
                    'service_source' => $serviceSource,
                    'adjustment_id' => $adjustmentId,
                    'refund_amount' => $refundBaseAmount,
                    'discount_allocated' => $discountRemoved,
                    'discount_after' => $discountAfter,
                    'cgst_amount' => $cgstAmount,
                    'sgst_amount' => $sgstAmount,
                    'total_refund_amount' => $totalRefundWithGST,
                    'booking_status' => $allCancelled ? 'cancelled' : 'active',
                    'all_services_cancelled' => $allCancelled,
                    'addons_cancelled' => count($addonRecords),
                ]
            ], 200);
        } catch (\Exception $e) {
            log_message('error', 'Service cancellation error: ' . $e->getMessage());
            return $this->failServerError('Something went wrong while cancelling the service. ' . $e->getMessage());
        }
    }

    public function createAdjustment($bookingId = null)
    {
        try {
            $bookingId = (int) $bookingId;

            if ($bookingId <= 0) {
                return $this->failValidationErrors('Valid booking ID is required.');
            }

            $booking = $this->bookingsModel->find($bookingId);
            if (!$booking) {
                return $this->failNotFound('Booking not found.');
            }

            $adjustmentType = $this->request->getVar('adjustment_type');
            $label = $this->request->getVar('label');
            $amount = (float) $this->request->getVar('amount');
            $isAddition = (int) ($this->request->getVar('is_addition') ?? 0);
            $isTaxable = (int) ($this->request->getVar('is_taxable') ?? 0);
            $createdBy = $this->request->getVar('created_by') ?? 'admin';

            if (empty($adjustmentType) || empty($label) || $amount <= 0) {
                return $this->failValidationErrors('adjustment_type, label and valid amount are required.');
            }

            if (!in_array($createdBy, ['admin', 'system'], true)) {
                $createdBy = 'admin';
            }

            $cgstAmount = 0.0;
            $sgstAmount = 0.0;

            if ($isTaxable === 1) {
                $cgstRate = (float) ($booking['cgst_rate'] ?? self::CGST_RATE);
                $sgstRate = (float) ($booking['sgst_rate'] ?? self::SGST_RATE);
                $cgstAmount = round($amount * ($cgstRate / 100), 2);
                $sgstAmount = round($amount * ($sgstRate / 100), 2);
            }

            $adjustmentData = [
                'booking_id' => $bookingId,
                'adjustment_type' => $adjustmentType,
                'label' => $label,
                'amount' => $amount,
                'is_addition' => $isAddition ? 1 : 0,
                'is_taxable' => $isTaxable ? 1 : 0,
                'cgst_amount' => $cgstAmount,
                'sgst_amount' => $sgstAmount,
                'created_by' => $createdBy,
            ];

            $this->bookingAdjustmentModel->insert($adjustmentData);
            $adjustmentId = $this->bookingAdjustmentModel->getInsertID();

            return $this->respond([
                'status' => 201,
                'message' => 'Booking adjustment created successfully',
                'data' => [
                    'id' => $adjustmentId,
                    'booking_id' => $bookingId,
                    'adjustment_type' => $adjustmentType,
                    'label' => $label,
                    'amount' => $amount,
                    'is_addition' => $isAddition ? 1 : 0,
                    'is_taxable' => $isTaxable ? 1 : 0,
                    'cgst_amount' => $cgstAmount,
                    'sgst_amount' => $sgstAmount,
                    'created_by' => $createdBy,
                ],
            ], 201);
        } catch (\Exception $e) {
            return $this->failServerError('Failed to create booking adjustment: ' . $e->getMessage());
        }
    }

    public function getAdjustments($bookingId = null)
    {
        try {
            $bookingId = (int) $bookingId;

            if ($bookingId <= 0) {
                return $this->failValidationErrors('Valid booking ID is required.');
            }

            $booking = $this->bookingsModel->find($bookingId);
            if (!$booking) {
                return $this->failNotFound('Booking not found.');
            }

            $adjustments = $this->bookingAdjustmentModel
                ->where('booking_id', $bookingId)
                ->orderBy('created_at', 'DESC')
                ->findAll();

            return $this->respond([
                'status' => 200,
                'message' => 'Booking adjustments retrieved successfully',
                'data' => $adjustments,
            ], 200);
        } catch (\Exception $e) {
            return $this->failServerError('Failed to fetch booking adjustments: ' . $e->getMessage());
        }
    }

    public function getCancellationDetails($bookingId)
    {
        try {
            $bookingId = (int) $bookingId;

            // Get query parameters for single service cancellation
            $serviceId = $this->request->getGet('service_id');
            $serviceSource = $this->request->getGet('service_source');

            $booking = $this->bookingsModel->find($bookingId);
            if (!$booking) {
                return $this->failNotFound('Booking not found.');
            }

            // If service_id and service_source provided, return details for single service only
            if ($serviceId && $serviceSource) {
                return $this->getSingleServiceCancellationDetails($bookingId, (int)$serviceId, $serviceSource, $booking);
            }

            // Otherwise return all services (existing behavior)
            $bookingDiscount = (float) ($booking['discount'] ?? 0);
            $cgstRateBooking = (float) ($booking['cgst_rate'] ?? self::CGST_RATE);
            $sgstRateBooking = (float) ($booking['sgst_rate'] ?? self::SGST_RATE);

            $couponCode = $booking['applied_coupon'] ?? null;
            $coupon = $couponCode
                ? $this->couponsModel->where('coupon_code', $couponCode)->first()
                : null;

            $bookingServicesTotal = (float) $this->bookingServicesModel
                ->selectSum('amount')
                ->where('booking_id', $bookingId)
                ->where('status !=', 'cancelled')
                ->get()
                ->getRow('amount');

            $parentServices = $this->bookingServicesModel
                ->select('booking_services.*, services.name as service_name, services.description as service_description, services.image as service_image')
                ->join('services', 'services.id = booking_services.service_id', 'left')
                ->where('booking_services.booking_id', $bookingId)
                ->where('booking_services.parent_booking_service_id', null)
                ->findAll();

            $bookingServices = [];

            foreach ($parentServices as $parentService) {
                $addons = $this->bookingServicesModel
                    ->select('booking_services.*, service_addons.name as addon_name, service_addons.description as addon_description')
                    ->join('service_addons', 'service_addons.id = booking_services.addon_id', 'left')
                    ->where('booking_services.parent_booking_service_id', $parentService['id'])
                    ->findAll();

                $addonsTotal = 0.0;
                foreach ($addons as $addon) {
                    $addonsTotal += (float) ($addon['amount'] ?? 0);
                }

                $serviceTotal = (float) ($parentService['amount'] ?? 0) + $addonsTotal;

                $remainingSubtotal = max($bookingServicesTotal - $serviceTotal, 0);
                $couponResult = $this->evaluateCouponForSubtotal($coupon, $remainingSubtotal);
                $discountAfter = (float) ($couponResult['discount'] ?? 0);
                $discountRemoved = max($bookingDiscount - $discountAfter, 0);

                $refundBase = max($serviceTotal - $discountRemoved, 0);
                $cgstAmount = round($refundBase * ($cgstRateBooking / 100), 2);
                $sgstAmount = round($refundBase * ($sgstRateBooking / 100), 2);
                $totalRefund = $refundBase + $cgstAmount + $sgstAmount;

                $bookingServices[] = [
                    'id' => $parentService['id'],
                    'service_id' => $parentService['service_id'],
                    'service_name' => $parentService['service_name'] ?? null,
                    'service_description' => $parentService['service_description'] ?? null,
                    'service_image' => $parentService['service_image'] ?? null,
                    'amount' => (float) ($parentService['amount'] ?? 0),
                    'addons_total' => $addonsTotal,
                    'discount_allocated' => $discountRemoved,
                    'discount_after' => $discountAfter,
                    'refund_base_amount' => $refundBase,
                    'cgst_rate' => $cgstRateBooking,
                    'sgst_rate' => $sgstRateBooking,
                    'cgst_amount' => $cgstAmount,
                    'sgst_amount' => $sgstAmount,
                    'total_refund_amount' => $totalRefund,
                    'status' => $parentService['status'] ?? null,
                    'can_cancel' => ($parentService['status'] ?? null) !== 'cancelled',
                    'addons' => $addons,
                ];
            }

            $additionalParents = $this->bookingAdditionalServicesModel
                ->select('booking_additional_services.*, services.name as service_name, services.description as service_description, services.image as service_image')
                ->join('services', 'services.id = booking_additional_services.service_id', 'left')
                ->where('booking_additional_services.booking_id', $bookingId)
                ->where('booking_additional_services.parent_booking_service_id', null)
                ->findAll();

            $additionalServices = [];

            foreach ($additionalParents as $parent) {
                $addons = $this->bookingAdditionalServicesModel
                    ->select('booking_additional_services.*, service_addons.name as addon_name, service_addons.description as addon_description')
                    ->join('service_addons', 'service_addons.id = booking_additional_services.addon_id', 'left')
                    ->where('booking_additional_services.parent_booking_service_id', $parent['id'])
                    ->findAll();

                $addonsTotal = 0.0;
                foreach ($addons as $addon) {
                    $addonsTotal += (float) ($addon['amount'] ?? 0);
                }

                $serviceTotal = (float) ($parent['amount'] ?? 0) + $addonsTotal;
                $cgstRate = (float) ($parent['cgst_rate'] ?? $cgstRateBooking);
                $sgstRate = (float) ($parent['sgst_rate'] ?? $sgstRateBooking);
                $cgstAmount = round($serviceTotal * ($cgstRate / 100), 2);
                $sgstAmount = round($serviceTotal * ($sgstRate / 100), 2);
                $totalRefund = $serviceTotal + $cgstAmount + $sgstAmount;

                $additionalServices[] = [
                    'id' => $parent['id'],
                    'service_id' => $parent['service_id'],
                    'service_name' => $parent['service_name'] ?? null,
                    'service_description' => $parent['service_description'] ?? null,
                    'service_image' => $parent['service_image'] ?? null,
                    'amount' => (float) ($parent['amount'] ?? 0),
                    'addons_total' => $addonsTotal,
                    'discount_allocated' => 0.0,
                    'discount_after' => 0.0,
                    'refund_base_amount' => $serviceTotal,
                    'cgst_rate' => $cgstRate,
                    'sgst_rate' => $sgstRate,
                    'cgst_amount' => $cgstAmount,
                    'sgst_amount' => $sgstAmount,
                    'total_refund_amount' => $totalRefund,
                    'status' => $parent['status'] ?? null,
                    'can_cancel' => ($parent['status'] ?? null) !== 'cancelled',
                    'addons' => $addons,
                ];
            }

            return $this->respond([
                'status' => 200,
                'message' => 'Cancellation details retrieved successfully.',
                'data' => [
                    'booking_id' => $bookingId,
                    'booking_discount' => $bookingDiscount,
                    'cgst_rate' => $cgstRateBooking,
                    'sgst_rate' => $sgstRateBooking,
                    'booking_services' => $bookingServices,
                    'additional_services' => $additionalServices,
                ]
            ], 200);
        } catch (\Exception $e) {
            log_message('error', 'Cancellation details error: ' . $e->getMessage());
            return $this->failServerError('Something went wrong while fetching cancellation details.');
        }
    }

    private function getSingleServiceCancellationDetails(int $bookingId, int $serviceId, string $serviceSource, array $booking)
    {
        // Validate service_source
        if (!in_array($serviceSource, ['booking_service', 'additional_service'])) {
            return $this->failValidationErrors([
                'status' => 400,
                'message' => 'Invalid service_source. Allowed: booking_service, additional_service'
            ]);
        }

        $model = ($serviceSource === 'booking_service') ? $this->bookingServicesModel : $this->bookingAdditionalServicesModel;
        $tableName = ($serviceSource === 'booking_service') ? 'booking_services' : 'booking_additional_services';

        // Fetch the specific service
        $service = $model
            ->select("{$tableName}.*, services.name as service_name, services.description as service_description, services.image as service_image")
            ->join('services', "services.id = {$tableName}.service_id", 'left')
            ->where("{$tableName}.id", $serviceId)
            ->where("{$tableName}.booking_id", $bookingId)
            ->first();

        if (!$service) {
            return $this->failNotFound('Service not found.');
        }

        // Check if service is already cancelled
        if ($service['status'] === 'cancelled') {
            return $this->respond([
                'status' => 400,
                'message' => 'Service is already cancelled.'
            ], 400);
        }

        // Fetch child addons
        $addons = $model
            ->select("{$tableName}.*, service_addons.name as addon_name, service_addons.description as addon_description")
            ->join('service_addons', "service_addons.id = {$tableName}.addon_id", 'left')
            ->where("{$tableName}.parent_booking_service_id", $serviceId)
            ->findAll();

        $addonsTotal = 0.0;
        $addonBreakdown = [];
        foreach ($addons as $addon) {
            $addonAmount = (float) ($addon['amount'] ?? 0);
            $addonsTotal += $addonAmount;
            $addonBreakdown[] = [
                'id' => $addon['id'],
                'addon_id' => $addon['addon_id'] ?? null,
                'name' => $addon['addon_name'] ?? null,
                'qty' => (float) ($addon['quantity'] ?? 0),
                'unit' => $addon['unit'] ?? null,
                'rate' => (float) ($addon['rate'] ?? 0),
                'base_amount' => $addonAmount,
            ];
        }

        $serviceBaseAmount = (float) ($service['amount'] ?? 0);
        $serviceTotal = $serviceBaseAmount + $addonsTotal;

        $bookingDiscount = (float) ($booking['discount'] ?? 0);
        $cgstRateBooking = (float) ($booking['cgst_rate'] ?? self::CGST_RATE);
        $sgstRateBooking = (float) ($booking['sgst_rate'] ?? self::SGST_RATE);

        $serviceProportion = 1.0;
        $discountAllocated = 0.0;

        if ($serviceSource === 'booking_service') {
            $parentServices = $this->bookingServicesModel
                ->select('id, amount')
                ->where('booking_id', $bookingId)
                ->where('parent_booking_service_id', null)
                ->where('status !=', 'cancelled')
                ->findAll();

            $bookingServicesTotal = 0.0;
            foreach ($parentServices as $parent) {
                $parentAddonsTotal = (float) $this->bookingServicesModel
                    ->selectSum('amount')
                    ->where('parent_booking_service_id', $parent['id'])
                    ->where('status !=', 'cancelled')
                    ->get()
                    ->getRow('amount');
                $bookingServicesTotal += (float) ($parent['amount'] ?? 0) + $parentAddonsTotal;
            }

            if ($bookingServicesTotal > 0) {
                $serviceProportion = min(max($serviceTotal / $bookingServicesTotal, 0), 1);
            }

            $discountAllocated = round($bookingDiscount * $serviceProportion, 2);
        }

        $refundBase = max($serviceTotal - $discountAllocated, 0);

        // Calculate GST on refund
        $cgstRate = (float) ($service['cgst_rate'] ?? $cgstRateBooking);
        $sgstRate = (float) ($service['sgst_rate'] ?? $sgstRateBooking);
        $cgstAmount = round($refundBase * ($cgstRate / 100), 2);
        $sgstAmount = round($refundBase * ($sgstRate / 100), 2);
        $totalRefund = $refundBase + $cgstAmount + $sgstAmount;

        $serviceData = [
            'id' => $service['id'],
            'service_id' => $service['service_id'],
            'service_name' => $service['service_name'] ?? null,
            'service_description' => $service['service_description'] ?? null,
            'service_image' => $service['service_image'] ?? null,
            'quantity' => (float) ($service['quantity'] ?? 0),
            'unit' => $service['unit'] ?? null,
            'rate' => (float) ($service['rate'] ?? 0),
            'service_base_amount' => $serviceBaseAmount,
            'addons' => $addonBreakdown,
            'addons_total' => $addonsTotal,
            'subtotal_before_gst' => $serviceTotal,
            'cgst_rate' => $cgstRate,
            'sgst_rate' => $sgstRate,
            'cgst_amount' => $cgstAmount,
            'sgst_amount' => $sgstAmount,
            'total_gst' => $cgstAmount + $sgstAmount,
            'subtotal_with_gst' => $serviceTotal + $cgstAmount + $sgstAmount,
            'booking_discount' => $bookingDiscount,
            'service_proportion' => round($serviceProportion * 100, 2),
            'proportional_discount' => $discountAllocated,
            'final_refund_amount' => $totalRefund,
            'status' => $service['status'] ?? null,
            'can_cancel' => true,
        ];

        return $this->respond([
            'status' => 200,
            'message' => 'Single service cancellation details retrieved successfully.',
            'data' => [
                'service_source' => $serviceSource,
                'service_details' => $serviceData,
            ]
        ], 200);
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
            $paidSoFar = $this->getTotalPaidAmount((int) $booking['id']);
            $paidAmount = $paidSoFar + $data['amount'];
            $amountDue = max((float) $booking['final_amount'] - $paidAmount, 0);
            $paymentStatus = ($amountDue == 0) ? 'completed' : 'partial';
            $bookingStatus = ($amountDue == 0) ? 'confirmed' : 'pending';

            // Update Booking Record
            $this->bookingsModel->update($booking['id'], [
                'payment_status' => $paymentStatus,
                'status'         => $bookingStatus,
                'updated_at'     => date('Y-m-d H:i:s'),
            ]);

            // Store Payment Record
            $paymentData = [
                'booking_id'         => $booking['id'],
                'user_id'            => $data['user_id'],
                'payment_gateway'    => 'manual',
                'payment_method'     => strtolower($data['payment_method']),
                'gateway_payment_id' => $data['transaction_id'] ?? null,
                'amount'             => $data['amount'],
                'currency'           => 'INR',
                'status'             => 'success',
                'paid_at'            => date('Y-m-d H:i:s'),
                'created_at'         => date('Y-m-d H:i:s'),
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
                    'booking_code'  => $booking['booking_code'],
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
            $existingPayment = $this->bookingPaymentsModel->where('gateway_payment_id', $paymentId)->first();
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
        $this->bookingPaymentsModel->where('gateway_payment_id', $paymentId)->set([
            'status'     => 'pending',
            'updated_at' => date('Y-m-d H:i:s')
        ])->update();

        log_message('info', "Payment Authorized for Booking ID: $bookingId");
    }

    private function handleCaptured($bookingId, $payment, $paymentId)
    {
        $this->bookingPaymentsModel->where('gateway_payment_id', $paymentId)->set([
            'status'     => 'success',
            'paid_at'    => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ])->update();

        $this->bookingsModel->update($bookingId, [
            'payment_status' => 'completed',
            'status'         => 'confirmed',
            'updated_at'     => date('Y-m-d H:i:s')
        ]);
    }

    private function handleFailed($bookingId, $payment, $paymentId)
    {
        $this->bookingPaymentsModel->where('gateway_payment_id', $paymentId)->set([
            'status'     => 'failed',
            'updated_at' => date('Y-m-d H:i:s')
        ])->update();

        $this->bookingsModel->update($bookingId, [
            'payment_status' => 'failed',
            'status'         => 'pending',
            'updated_at'     => date('Y-m-d H:i:s')
        ]);
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

    public function addAdditionalServices()
    {
        try {
            $data = $this->request->getJSON(true);

            $bookingId = $data['booking_id'] ?? null;

            // Support both array format (services: [...]) and single-item format (flat structure)
            if (isset($data['services']) || isset($data['items'])) {
                $items = $data['services'] ?? $data['items'] ?? [];
            } elseif (isset($data['service_id']) || isset($data['is_additional'])) {
                // Single item passed directly
                $items = [$data];
            } else {
                $items = [];
            }

            if (!$bookingId || empty($items)) {
                return $this->failValidationErrors('Booking ID and additional services are required.');
            }

            $booking = $this->bookingsModel->find($bookingId);
            if (!$booking) {
                return $this->failNotFound('Booking not found.');
            }

            if ($booking['status'] === 'cancelled') {
                return $this->fail('Cannot modify cancelled booking.');
            }

            $authUser = session('auth_user') ?? [];
            $createdBy = $data['created_by'] ?? ($authUser['role'] ?? 'admin');
            $createdById = $data['created_by_id'] ?? ($authUser['id'] ?? null);

            if (!in_array($createdBy, ['admin', 'partner'], true)) {
                return $this->failValidationErrors('Invalid created_by. Allowed: admin, partner.');
            }

            $allowedUnits = ['unit', 'square_feet', 'running_feet', 'running_meter', 'point', 'sqft'];

            $this->db->transStart();

            $createdItems = [];
            $totalAmount = 0.0;

            foreach ($items as $item) {
                $quantity = (float) ($item['quantity'] ?? $item['qty'] ?? 0);
                $unit = $item['unit'] ?? null;
                $rate = (float) ($item['rate'] ?? 0);
                $amount = round($quantity * $rate, 2);

                // GST calculation
                $cgstRate =  self::CGST_RATE;
                $sgstRate =   self::SGST_RATE;
                $cgstAmount =  round($amount * ($cgstRate / 100), 2);
                $sgstAmount =  round($amount * ($sgstRate / 100), 2);
                $totalAmount = round($amount + $cgstAmount + $sgstAmount, 2);

                if ($quantity <= 0 || !$unit || $rate < 0) {
                    $this->db->transRollback();
                    return $this->failValidationErrors('Each item must include quantity, unit, and rate.');
                }

                if (!in_array($unit, $allowedUnits, true)) {
                    $this->db->transRollback();
                    return $this->failValidationErrors('Invalid unit for additional service.');
                }

                $parentData = [
                    'booking_id'                => $bookingId,
                    'parent_booking_service_id' => null,
                    'service_id'                => $item['service_id'] ?? null,
                    'addon_id'                  => null,
                    'service_type_id'           => $item['service_type_id'] ?? null,
                    'room_id'                   => $item['room_id'] ?? null,
                    'quantity'                  => $quantity,
                    'unit'                      => $unit,
                    'rate'                      => $rate,
                    'amount'                    => $amount,
                    'cgst_rate'                 => $cgstRate,
                    'sgst_rate'                 => $sgstRate,
                    'cgst_amount'               => $cgstAmount,
                    'sgst_amount'               => $sgstAmount,
                    'total_amount'              => $totalAmount,
                    'room_length'               => $item['room_length'] ?? null,
                    'room_width'                => $item['room_width'] ?? null,
                    'status'                    => 'pending',
                    'is_payment_required'       => isset($item['is_payment_required'])
                        ? (int) $item['is_payment_required']
                        : ($totalAmount > 0 ? 1 : 0),
                    'created_by'                => $createdBy,
                    'created_by_id'             => $createdById,
                ];

                if (!$this->bookingAdditionalServicesModel->insert($parentData)) {
                    $this->db->transRollback();
                    return $this->failValidationErrors([
                        'status'  => 400,
                        'message' => 'Validation failed for additional service.',
                        'errors'  => $this->bookingAdditionalServicesModel->errors(),
                    ]);
                }

                $parentId = $this->bookingAdditionalServicesModel->insertID();
                $addons = $item['addons'] ?? [];

                foreach ($addons as $addon) {
                    $addonQty = (float) ($addon['quantity'] ?? $addon['qty'] ?? 0);
                    $addonUnit = $addon['unit'] ?? $unit;
                    $addonRate = (float) ($addon['rate'] ?? $addon['price'] ?? 0);
                    $addonAmount = isset($addon['amount'])
                        ? (float) $addon['amount']
                        : round($addonQty * $addonRate, 2);

                    // GST calculation for addons (use parent item's GST rates if not specified)
                    $addonCgstRate = isset($addon['cgst']) ? (float) $addon['cgst'] : $cgstRate;
                    $addonSgstRate = isset($addon['sgst']) ? (float) $addon['sgst'] : $sgstRate;
                    $addonCgstAmount = isset($addon['cgst_amount'])
                        ? (float) $addon['cgst_amount']
                        : round($addonAmount * ($addonCgstRate / 100), 2);
                    $addonSgstAmount = isset($addon['sgst_amount'])
                        ? (float) $addon['sgst_amount']
                        : round($addonAmount * ($addonSgstRate / 100), 2);
                    $addonTotalAmount = round($addonAmount + $addonCgstAmount + $addonSgstAmount, 2);

                    if ($addonQty <= 0 || !$addonUnit || $addonRate < 0) {
                        $this->db->transRollback();
                        return $this->failValidationErrors('Each addon must include quantity, unit, and rate.');
                    }

                    if (!in_array($addonUnit, $allowedUnits, true)) {
                        $this->db->transRollback();
                        return $this->failValidationErrors('Invalid unit for additional service addon.');
                    }

                    $addonData = [
                        'booking_id'                => $bookingId,
                        'parent_booking_service_id' => $parentId,
                        'service_id'                =>  null,
                        'addon_id'                  => $addon['addon_id'] ?? $addon['id'] ?? null,
                        'service_type_id'           => $item['service_type_id'] ?? null,
                        'room_id'                   => $item['room_id'] ?? null,
                        'quantity'                  => $addonQty,
                        'unit'                      => $addonUnit,
                        'rate'                      => $addonRate,
                        'amount'                    => $addonAmount,
                        'cgst_rate'                 => $addonCgstRate,
                        'sgst_rate'                 => $addonSgstRate,
                        'cgst_amount'               => $addonCgstAmount,
                        'sgst_amount'               => $addonSgstAmount,
                        'total_amount'              => $addonTotalAmount,
                        'room_length'               => $item['room_length'] ?? null,
                        'room_width'                => $item['room_width'] ?? null,
                        'status'                    => 'pending',
                        'is_payment_required'       => isset($addon['is_payment_required'])
                            ? (int) $addon['is_payment_required']
                            : ($addonTotalAmount > 0 ? 1 : 0),
                        'created_by'                => $createdBy,
                        'created_by_id'             => $createdById,
                    ];

                    if (!$this->bookingAdditionalServicesModel->insert($addonData)) {
                        $this->db->transRollback();
                        return $this->failValidationErrors([
                            'status'  => 400,
                            'message' => 'Validation failed for additional service addon.',
                            'errors'  => $this->bookingAdditionalServicesModel->errors(),
                        ]);
                    }

                    $totalAmount += $addonTotalAmount;
                }

                $totalAmount += $totalAmount;
                $createdItems[] = [
                    'id'           => $parentId,
                    'amount'       => $amount,
                    'cgst_amount'  => $cgstAmount,
                    'sgst_amount'  => $sgstAmount,
                    'total_amount' => $totalAmount,
                    'addons'       => count($addons),
                    'status'       => 'pending',
                ];
            }

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaction failed');
            }

            return $this->respond([
                'status'  => 200,
                'message' => 'Additional services submitted for approval.',
                'data'    => [
                    'booking_id'              => $bookingId,
                    'total_additional_amount' => round($totalAmount, 2),
                    'items'                   => $createdItems,
                ],
            ]);
        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Add New Service Error: ' . $e->getMessage());

            return $this->failServerError('Something went wrong.');
        }
    }

    public function approveAdditionalServices()
    {
        try {
            $data = $this->request->getJSON(true) ?? $this->request->getVar();

            $bookingId = (int) ($data['booking_id'] ?? 0);
            $userId = (int) ($data['user_id'] ?? 0);
            $action = $data['action'] ?? null; // approve | reject
            $serviceIds = $data['service_ids'] ?? null;

            if (!$bookingId || !$userId || !in_array($action, ['approve', 'reject'], true)) {
                return $this->failValidationErrors('booking_id, user_id and action (approve|reject) are required.');
            }

            if (empty($serviceIds)) {
                $singleId = $data['service_id'] ?? null;
                if ($singleId) {
                    $serviceIds = [(int) $singleId];
                }
            }

            if (empty($serviceIds) || !is_array($serviceIds)) {
                return $this->failValidationErrors('service_id or service_ids is required.');
            }

            $serviceIds = array_values(array_unique(array_map('intval', $serviceIds)));

            $booking = $this->bookingsModel->find($bookingId);
            if (!$booking) {
                return $this->failNotFound('Booking not found.');
            }

            if ((int) ($booking['user_id'] ?? 0) !== $userId) {
                return $this->failValidationErrors('Booking does not belong to this user.');
            }

            if (($booking['status'] ?? null) === 'cancelled') {
                return $this->failValidationErrors('Cannot approve/reject services for cancelled booking.');
            }

            $parentServices = $this->bookingAdditionalServicesModel
                ->where('booking_id', $bookingId)
                ->whereIn('id', $serviceIds)
                ->where('parent_booking_service_id', null)
                ->findAll();

            if (empty($parentServices)) {
                return $this->failNotFound('Additional services not found.');
            }

            $pendingOnly = array_filter($parentServices, static function ($row) {
                return ($row['status'] ?? null) === 'pending';
            });

            if (count($pendingOnly) !== count($parentServices)) {
                return $this->failValidationErrors('Only pending services can be approved or rejected.');
            }

            $status = $action === 'approve' ? 'approved' : 'rejected';
            $now = date('Y-m-d H:i:s');

            $updateData = [
                'status' => $status,
                'approved_at' => $now,
                'approved_by' => 'customer',
                'approved_by_id' => $userId,
                'updated_at' => $now,
            ];

            $this->db->transStart();

            foreach ($parentServices as $service) {
                $this->bookingAdditionalServicesModel->update((int) $service['id'], $updateData);

                $this->bookingAdditionalServicesModel
                    ->where('parent_booking_service_id', (int) $service['id'])
                    ->set($updateData)
                    ->update();
            }

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                return $this->failServerError('Transaction failed. Please try again.');
            }

            return $this->respond([
                'status' => 200,
                'message' => $status === 'approved'
                    ? 'Additional services approved successfully.'
                    : 'Additional services rejected successfully.',
                'data' => [
                    'booking_id' => $bookingId,
                    'service_ids' => $serviceIds,
                    'status' => $status,
                    'approved_at' => $now,
                ]
            ], 200);
        } catch (\Exception $e) {
            log_message('error', 'Additional service approval error: ' . $e->getMessage());
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
                    'booking_ref'            => $booking['booking_code'],
                    'booking_created_at'     => $bookingCreatedAtString,
                    'total_amount'           => $booking['subtotal_amount'],
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
            $authUser = session('auth_user') ?? [];
            $created_by_id    = $data['created_by_id'] ?? ($authUser['id'] ?? null);
            $created_by_role  = $data['created_by_role'] ?? ($authUser['role'] ?? null);
            $manualDiscount  = isset($data['discount_amount'])
                ? (float) $data['discount_amount']
                : null;

            if (!$userId || !$slotDate || empty($services)) {
                return $this->failValidationErrors('Required fields missing.');
            }

            $this->db->transStart();
            $bookingCode = $this->generateBookingCode();
            /** -----------------------------------------
             * 1️⃣ CREATE EMPTY BOOKING FIRST
             * ----------------------------------------*/
            $bookingId = $this->bookingsModel->insert([
                'booking_code'      => $bookingCode,
                'user_id'           => $userId,
                'slot_date'         => $slotDate,

                'subtotal_amount'   => 0,
                'discount'          => 0,
                'cgst'              => 0,
                'sgst'              => 0,
                'cgst_rate'         => 0,
                'sgst_rate'         => 0,
                'final_amount'      => 0,

                'status'            => 'pending',
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
            $cgstRate = self::CGST_RATE;
            $sgstRate = self::SGST_RATE;
            $cgst = round($discounted * ($cgstRate / 100), 2);
            $sgst = round($discounted * ($sgstRate / 100), 2);
            $finalAmount = round($discounted + $cgst + $sgst, 2);

            /** -----------------------------------------
             * 5️⃣ UPDATE BOOKING TOTALS
             * ----------------------------------------*/
            $this->bookingsModel->update($bookingId, [
                'subtotal_amount' => $subtotal,
                'discount'        => $discount,
                'cgst'            => $cgst,
                'sgst'            => $sgst,
                'cgst_rate'       => $cgstRate,
                'sgst_rate'       => $sgstRate,
                'final_amount'    => $finalAmount,
                'applied_coupon'  => $appliedCoupon,
            ]);

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
            ->select('booking_payments.*, bookings.booking_code, customers.name, customers.mobile_no')
            ->join('bookings', 'bookings.id = booking_payments.booking_id')
            ->join('customers', 'customers.id = booking_payments.user_id')
            ->where('booking_payments.id', $paymentId)
            ->where('booking_payments.status', 'success')
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
                'attachment; filename="receipt_' . $payment['booking_code'] . '.pdf"'
            )
            ->setBody($dompdf->output());
    }
    private function generateBookingCode(): string
    {
        $prefix = 'SE';
        $booking_code = $prefix . date('ymdHis');

        $exists = $this->bookingsModel->where('booking_code', $booking_code)->first();
        if (!$exists) {
            return $booking_code;
        }

        return $prefix . date('ymdHis') . rand(10, 99);
    }
}
