<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\CartModel;
use App\Models\CouponModel;
use App\Models\CustomerAddressModel;
use App\Models\CustomerModel;
use App\Models\InvoiceModel;
use App\Models\OrderProductsModel;
use App\Models\OrdersModel;
use App\Models\OrdersTimelineModel;
use App\Models\PhonePeTransactionModel;
use App\Models\ProductModel;
use App\Models\TransactionModel;
use CodeIgniter\API\ResponseTrait;
use Config\Razorpay;
use DateTime;
use Exception;
use Kint\Zval\Representation\MicrotimeRepresentation;
use PSpell\Config;
use Razorpay\Api\Api;

class RazorpayController extends BaseController
{
    use ResponseTrait;
    function get_discounted_price($productRecord)
    {
        // var_dump($productRecord);
        $increasePrice = ($productRecord['actual_price'] / 100 * $productRecord['increase_percent']);
        $discount = $increasePrice - ($increasePrice / 100 * $productRecord['discounted_percent']);
        // $singleProductPrice = ($productRecord['actual_price'] - ($productRecord['actual_price'] / 100 * $productRecord['discounted_percent']));
        $singleProductPrice = $productRecord['actual_price'] + $discount;
        return ($singleProductPrice * $productRecord['quantity']);
    }

    function get_subtotal_price($productRecord)
    {
        $singleProductPrice = ($productRecord['actual_price'] + ($productRecord['actual_price'] / 100 * $productRecord['increase_percent']));
        return $singleProductPrice;
        // return ($productRecord['actual_price']);
    }

    public function makeCOD($customerID, $addressID, $coupon)
    {
        try {
            $cartModel = new CartModel();
            $validation = &$cartModel;
            $cartProducts = $cartModel->select([
                'af_cart.product_id AS product_id',
                'af_cart.quantity AS quantity',
                'af_products.actual_price AS actual_price',
                // 'af_products.actual_price AS base_price',
                'af_products.increase_percent AS increase_percent',
                'af_products.discounted_percent AS discounted_percent',
            ])->join('af_products', 'af_products.id = af_cart.product_id')->where('af_cart.customer_id', $customerID)->findAll();
            /* foreach ($cartProducts as &$product) {
                $product['actual_price'] = $product['actual_price'] + ($product['actual_price'] / 100 * $product['increase_percent']);
            } */
            /* helper('products');
            $cartProducts = array_map('get_discounted_price', $cartProducts); */

            $cartProductsPrice = array_map([$this, 'get_total_price'], $cartProducts);
            $total = array_sum($cartProductsPrice);
            $cartProductsDiscountedPrice = array_map([$this, 'get_discounted_price'], $cartProducts);
            $subTotal = array_sum($cartProductsDiscountedPrice);
            // echo 't' . $total . 'st' . $subTotal;
            // die;
            $couponID = null;
            $ordersModel = new OrdersModel();
            $validation = &$ordersModel;
            // $codOrderID = 'cod' . bin2hex(microtime(true));
            $codOrderID = 'cod_' . date('YmdHis');
            $orderData = [
                'customer_id' => $customerID,
                'address_id' => $addressID,
                'total' => $total,
                'subtotal' => $subTotal,
                'razorpay_order_id' => $codOrderID,
                'is_cod' => 1,
                'status' => 1,
            ];
            $couponDiscount = 0;
            if (!empty($coupon)) {
                switch ($coupon['coupon_type']) {
                    case 1:
                        $couponDiscount = ($subTotal / 100 * $coupon['coupon_type_name']);
                        break;
                    case 2:
                        $couponDiscount = $coupon['coupon_type_name'];
                        break;
                }
                // $subTotal = $couponDiscount;
                $couponID = $coupon['id'];
                // $orderData['coupon_discount'] = $$couponDiscount;
                $orderData['coupon'] = $couponID;
            }
            $orderData['discount'] = $total - $subTotal;
            // echo $orderData['discount'];die;

            $ordersModel->insert($orderData);
            if ($validation->db->error()['code']) {
                throw new Exception($validation->db->error()['message'], 500);
            }
            if (!empty($validation->errors())) {
                throw new Exception('Validation', 400);
            }
            $orderID = $ordersModel->db->insertID();
            //Order TimeLine
            $ordersTimelineModel = new OrdersTimelineModel();
            $timelineData = [
                "order_id" => $orderID,
                "status" => 1,
                "timeline" => date("d-m-y H:i:s"),
                "remark" => 'Order confirmed',
            ];
            $ordersTimelineModel->insert($timelineData);

            $orderProducts = array_map(
                function ($cartProduct) use ($orderID) {
                    $cartProduct['order_id'] = $orderID;
                    return $cartProduct;
                },
                $cartProducts
            );
            $orderProductsModel = new OrderProductsModel();
            $validation = &$orderProductsModel;
            /* foreach($orderProducts as &$product)
            {
                $product['actual_price'] = $product['base_price'];
            } */
            $orderProductsModel->insertBatch($orderProducts);
            if ($validation->db->error()['code']) {
                throw new Exception($validation->db->error()['message'], 500);
            }
            if (!empty($validation->errors())) {
                throw new Exception('Validation', 400);
            }

            $ordersModel = new OrdersModel();
            $orderDetails = $ordersModel->where('razorpay_order_id', $codOrderID)->first();
            $orderDetails['coupon_discount'] = $couponDiscount;
            $invoicePath = $this->makeInvoice($orderDetails, $ordersModel);
            $statusCode = 200;
            $response = [
                'invoice_path' => $invoicePath,
                'message' => 'Order placed successfully.',
            ];
            $response['status'] = $statusCode;

            $cartModel = new CartModel();
            $cartModel->where('customer_id', $customerID)->delete();
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'status' => $statusCode,
                'error' => $e->getCode() === 400 ? ['validation' => $validation->errors()] : $e->getMessage()
            ];
            // return $this->respond($response, $statusCode);
        }

        return $response;
    }

    public function createPayment()
    {
        try {
            $cartModel = new CartModel();
            $customerID = $this->request->getVar('customer_id');
            $addressID = $this->request->getVar('address_id');
            $isCOD = $this->request->getVar('is_cod');
            $couponCode = $this->request->getVar('coupon_code');
            $hasCoupon = false;
            $coupon = array();

            if ($couponCode != null) {
                $couponModel = new CouponModel();
                // Coupon
                $coupon = $couponModel->where('coupon_code', $couponCode)->first();
                $exp_date = explode(',', $coupon['coupon_expiry']);
                $exp_date = $exp_date[1];
                $exp_date = substr($exp_date, 2, strlen($exp_date) - 6);
                $exp_date = new DateTime($exp_date);
                $today = new DateTime('now');
                // print_r($coupon['coupon_expiry']);die;

                if ($coupon === null) {
                    throw new Exception('Invalid coupon code', 409);
                }

                if ($exp_date < $today) {
                    throw new Exception('The coupon has expired!', 409);
                }

                if ($coupon['coupon_used_count'] >= $coupon['coupon_use_limit']) {
                    throw new Exception('The coupon used limit has exceeded!', 409);
                }

                $hasCoupon = true;
            }

            // 
            if ($isCOD) {
                $response = $this->makeCOD($customerID, $addressID, $coupon);
                if ($response['status'] == 200) {
                    // return redirect()->to('http://localhost:3000/success?invoice=' . $response['invoice_path'], 200, 'refresh');
                    return $this->respond($response, 200);
                }
                // var_dump($response);
                return $this->respond($response, $response['status']);
                exit;
            }
            $validation = &$cartModel;
            $cartProducts = $cartModel->select([
                'af_cart.product_id AS product_id',
                'af_cart.quantity AS quantity',
                'af_products.actual_price AS actual_price',
                'af_products.increase_percent AS increase_percent',
                'af_products.discounted_percent AS discounted_percent',
            ])->join('af_products', 'af_products.id = af_cart.product_id')->where('af_cart.customer_id', $customerID)->findAll();
            if ($validation->db->error()['code']) {
                throw new Exception($validation->db->error()['message'], 500);
            }

            $cartProductsPrice = array_map([$this, 'get_discounted_price'], $cartProducts);
            $subTotal = array_sum($cartProductsPrice);
            $coupondTotal = $subTotal;
            if ($couponCode != null) {
                $couponDiscount = 0;
                switch ($coupon['coupon_type']) {
                    case 1:
                        $couponDiscount = ($coupondTotal / 100) * $coupon['coupon_type_name'];
                        break;
                    case 2:
                        $couponDiscount = $coupon['coupon_type_name'];
                        break;
                }
                $coupondTotal -= $couponDiscount;
            }
            // die(var_dump($cartProductsPrice));

            $config = new Razorpay();
            $razorpay = new \Razorpay\Api\Api($config->keyId, $config->keySecret);
            $currency = $config->displayCurrency;
            $receipt = 'order_' . time();
            $data = [
                'amount' => $coupondTotal * 100,
                'currency' => $currency,
                'receipt' => $receipt,
                'payment_capture' => 1,
            ];
            $razorpayOrder = $razorpay->order->create($data);


            $razorpayOrderData = [
                'order_id' => $razorpayOrder->id,
                'receipt' => $razorpayOrder->receipt,
                'amount' => $razorpayOrder->amount,
                'offer_id' => $razorpayOrder->offer_id,
                'status' => $razorpayOrder->status,
            ];

            $ordersModel = new OrdersModel();
            $validation = &$ordersModel;
            $orderData = [
                'customer_id' => $customerID,
                'address_id' => $addressID,
                'subtotal' => $subTotal,
                'razorpay_order_id' => $razorpayOrderData['order_id'],
                'status' => 1,
            ];


            if ($hasCoupon) {
                $orderData['coupon'] = $coupon['id'];
            }
            $ordersModel->insert($orderData);
            if ($validation->db->error()['code']) {
                throw new Exception($validation->db->error()['message'], 500);
            }
            if (!empty($validation->errors())) {
                throw new Exception('Validation', 400);
            }
            $orderID = $ordersModel->db->insertID();

            $orderProducts = array_map(
                function ($cartProduct) use ($orderID) {
                    $cartProduct['order_id'] = $orderID;
                    return $cartProduct;
                },
                $cartProducts
            );
            $orderProductsModel = new OrderProductsModel();
            $validation = &$orderProductsModel;
            $orderProductsModel->insertBatch($orderProducts);

            $statusCode = 200;
            $response = [
                'order_id' => $orderID,
                'razorpay_order' => [
                    'razorpay_order_id' => $razorpayOrderData['order_id'],
                    'receipt' => $razorpayOrderData['receipt'],
                    'amount' => $razorpayOrderData['amount'] / 100,
                    'offer_id' => $razorpayOrderData['offer_id'],
                    'status' => $razorpayOrderData['status'],
                ],
            ];

            /* // Invoice
            $customerModel = new CustomerModel();
            $validation = &$customerModel;
            $customerData = $customerModel->find($customerID);
            $customerAddressModel = new CustomerAddressModel();
            $validation = &$customerAddressModel;
            $addressData = $customerAddressModel->find($orderData['address_id']);
            // $address = $

            $headData = [
                'full_name' => $addressData['first_name'] . ' ' . $addressData['last_name'],
                'address_line1' => $addressData['street_address'],
                'address_line2' => implode(', ', [$addressData['city'], $addressData['state'], $addressData['pincode']]),
                'phone_no' => $addressData['phone'],
                'email_id' => $addressData['email'],
                'invoice_id' => 'AF0001',
                'order_id' => $orderData['razorpay_order_id'],
                'invoice_date' => date('Y-m-d'),
                'customer_id' => $customerID,
                'subtotal' => $subTotal,
            ];
            $validation = &$orderProductsModel;
            $invoiceProductsData = $orderProductsModel->select([
                'af_products.name AS title',
                'af_products.brand AS brand',
                'af_products.product_code AS product_code',
                'af_order_products.actual_price AS actual_price',
                'af_order_products.discounted_percent AS discounted_percent',
                'af_order_products.quantity AS quantity',
            ])->join('af_products', 'af_products.id = af_order_products.product_id')->where('af_order_products.order_id', $orderID)->findAll();
            foreach($invoiceProductsData as &$product){
                $product['price'] = $product['actual_price'] - ($product['actual_price'] / 100 * $product['discounted_percent']);
                $product['total'] = $product['price'] * $product['quantity'];
                unset($product['actual_price'], $product['discounted_percent']);
            }
            $invoiceController = new InvoiceController();
            $invoicePath = $invoiceController->makeInvoice($headData, $invoiceProductsData);
            $response['invoice_path'] = base_url($invoicePath); */
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? ['validation' => $validation->errors()] : $e->getMessage(),
                "line" => $e->getLine()
            ];
        }
        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }



    public function razorPayinitiate()
    {
        try {
            // $this->response->setHeader('Content-Type', 'application/json');
            $customerID = $this->request->getVar('customer_id');
            $addressID = $this->request->getVar('address_id');
            $isCOD = $this->request->getVar('is_cod');
            $couponCode = $this->request->getVar('coupon_code');
            $hasCoupon = false;
            $coupon = array();

            // Validating coupon if exist
            if ($couponCode != null) {
                $couponModel = new CouponModel();
                // Coupon
                $coupon = $couponModel->where('coupon_code', $couponCode)->first();
                $exp_date = explode(',', $coupon['coupon_expiry']);
                $exp_date = $exp_date[1];
                $exp_date = substr($exp_date, 2, strlen($exp_date) - 6);
                $exp_date = new DateTime($exp_date);
                $today = new DateTime('now');
                // print_r($coupon['coupon_expiry']);die;

                if ($coupon === null) {
                    throw new Exception('Invalid coupon code', 409);
                }

                if ($exp_date < $today) {
                    throw new Exception('The coupon has expired!', 409);
                }

                if ($coupon['coupon_used_count'] >= $coupon['coupon_use_limit']) {
                    throw new Exception('The coupon used limit has exceeded!', 409);
                }

                $hasCoupon = true;
            }

            // Fetching cart details
            $cartModel = new CartModel();
            $validation = &$cartModel;
            $cartProducts = $cartModel->select([
                'af_cart.product_id AS product_id',
                'af_cart.quantity AS quantity',
                'af_products.actual_price AS actual_price',
                // 'af_products.actual_price AS base_price',
                'af_products.increase_percent AS increase_percent',
                'af_products.discounted_percent AS discounted_percent',
            ])->join('af_products', 'af_products.id = af_cart.product_id')->where('af_cart.customer_id', $customerID)->findAll();
            /* foreach ($cartProducts as &$product) {
                $product['actual_price'] = $product['actual_price'] + ($product['actual_price'] / 100 * $product['increase_percent']);
            } */
            /* helper('products');
            $cartProducts = array_map('get_discounted_price', $cartProducts); */

            // getting total amount
            // print_r($cartProducts);die;
            $cartProductsPrice = array_map([$this, 'get_subtotal_price'], $cartProducts);
            $subTotal = array_sum($cartProductsPrice);
            // getting subtotal amount
            $cartProductsDiscountedPrice = array_map([$this, 'get_discounted_price'], $cartProducts);
            $total = array_sum($cartProductsDiscountedPrice);

            // $codOrderID = 'cod' . bin2hex(microtime(true));



            // Getting coupon discount if exist
            $couponDiscount = 0;
            $couponID = 0;
            if (!empty($coupon)) {
                switch ($coupon['coupon_type']) {
                    case 1:
                        $couponDiscount = ($subTotal / 100 * $coupon['coupon_type_name']);
                        break;
                    case 2:
                        $couponDiscount = $coupon['coupon_type_name'];
                        break;
                }
                // $subTotal = $couponDiscount;
                $couponID = $coupon['id'];
                // $orderData['coupon_discount'] = $couponDiscount;
                $orderData['coupon'] = $couponID;
            }

            if ($validation->db->error()['code']) {
                throw new Exception($validation->db->error()['message'], 500);
            }
            if (!empty($validation->errors())) {
                throw new Exception('Validation', 400);
            }


            $totalAmount = $total - $couponDiscount;
            $config = new Razorpay();
            $razorpay = new Api($config->keyId, $config->keySecret);
            // print_r($razorpay);
            $currency = $config->displayCurrency;
            $receipt = 'ord' . date("Ymdhis");
            $data = [
                'amount' => (int)($totalAmount * 100),
                'currency' => $currency,
                'receipt' => $receipt,
                'payment_capture' => 1,
            ];
            $razorpayOrder = $razorpay->order->create($data);
            $razorpayOrderData = [
                'order_id' => $razorpayOrder->id,
                'receipt' => $razorpayOrder->receipt,
                'amount' => $razorpayOrder->amount,
                'offer_id' => $razorpayOrder->offer_id,
                'RazerPaystatus' => $razorpayOrder->status,
                'status' => 200,
            ];


            $orderUniqueID = 'ORD' . date('YmdHis');

            $orderData = [
                'customer_id' => $customerID,
                'address_id' => $addressID,
                'razorpay_initiate_order_id' => $razorpayOrder->id,
                'total' => $total,
                'subtotal' => $subTotal,
                'razorpay_order_id' => $orderUniqueID,
                'is_cod' => 0,
                'status' => 0,
                'coupon' => $couponID,
            ];
            $orderData['discount'] = $subTotal - $total;
            // echo $orderData['discount'];die;

            // Creating order
            $ordersModel = new OrdersModel();

            $validation = &$ordersModel;
            $ordersModel->insert($orderData);



            $orderID = $ordersModel->db->insertID();

            /* //Order TimeLine
            $ordersTimelineModel = new OrdersTimelineModel();
            $timelineData = [
                "order_id" => $orderID,
                "status" => 1,
                "timeline" => date("d-m-y H:i:s"),
                "remark" => 'Order confirmed',
            ];
            $ordersTimelineModel->insert($timelineData); */

            // Order products
            $orderProducts = array_map(
                function ($cartProduct) use ($orderID) {
                    $cartProduct['order_id'] = $orderID;
                    return $cartProduct;
                },
                $cartProducts
            );
            $orderProductsModel = new OrderProductsModel();
            $validation = &$orderProductsModel;
            $orderProductsModel->insertBatch($orderProducts);
            if ($validation->db->error()['code']) {
                throw new Exception($validation->db->error()['message'], 500);
            }
            if (!empty($validation->errors())) {
                throw new Exception('Validation', 400);
            }


            return $this->respond($razorpayOrderData, 200);
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? ['validation' => $validation->errors()] : $e->getMessage(),
                'line' => $e->getLine()
            ];
        }
        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    public function verfiyAppPayment()
    {
        $razorpayPaymentId = $this->request->getVar('razorpay_payment_id');
        $razorpayOrderId = $this->request->getVar('razorpay_order_id');
        $razorpaySignature = $this->request->getVar('razorpay_signature');
        $customerID = $this->request->getVar('customer_id');
        $response = $this->verifyPayment($razorpayPaymentId, $razorpayOrderId, $razorpaySignature, $customerID);
        // print_r($response);
        // echo 'working';die;
        return $this->respond($response, $response['status']);
    }

    public function verifyWebPayment()
    {
        $data = $this->request->getVar();
        // print_r($data);die;
        $razorpayPaymentId = $this->request->getVar('razorpay_payment_id');
        $razorpayOrderId = $this->request->getVar('razorpay_order_id');
        $razorpaySignature = $this->request->getVar('razorpay_signature');
        $customerID = $this->request->getVar('customer_id');
        $response = $this->verifyPayment($razorpayPaymentId, $razorpayOrderId, $razorpaySignature, $customerID);
        // print_r($response);die;
        return $this->respond($response, $response['status']);
        /* if ($response['status'] == 200) {
            $invoicePath = $response['invoice_path'];
            $frontURL = getenv('FRONT_URL');
            return redirect()->to($frontURL . 'success?invoice=' . $invoicePath, 200, 'refresh');
        } else {
            return $this->respond($response, $response['status']);
        } */
    }
    public function objectToArray($obj)
    {
        if (is_object($obj)) {
            $obj = get_object_vars($obj);
        }

        if (is_array($obj)) {
            return array_map([$this, 'objectToArray'], $obj);
        } else {
            return $obj;
        }
    }
    public function verifyPayment($razorpayPaymentId, $razorpayOrderId, $razorpaySignature, $customerID)
    {
        try {
            // echo "wwww";die;
            $config = new Razorpay();
            // $api = new \Razorpay\Api\Api($config->keyId, $config->keySecret);
            $api = new Api($config->keyId, $config->keySecret);
            $paymentData = $api->payment->fetch($razorpayPaymentId);

            // die(var_dump($paymentData));
            $paymentStatus = $paymentData->status;
            $orderID = $paymentData->order_id;
            // echo "<pre>";
            // print_r(serialize($paymentData));
            // die;
            // print_r($paymentData->attributes);
            // die(var_dump($paymentData->order_id));       
            $attributes = array('razorpay_signature' => $razorpaySignature, 'razorpay_payment_id' => $razorpayPaymentId, 'razorpay_order_id' => $orderID);

            $res = $api->utility->verifyPaymentSignature($attributes);

            if ($res == null) {
                $phonePeTransactionModel = new PhonePeTransactionModel();
                $validation = &$phonePeTransactionModel;

                $txnID = "TXN" . date("Ymdhis");
                $datatoinsert = [
                    "merchantTransactionId" => $txnID,
                    "transactionId" => $paymentData["id"],
                    "payment_status" => $paymentData["status"],
                    "transation_status" => $paymentData["status"],
                    "amount" => $paymentData["amount"],
                    "form_json" => serialize($paymentData),
                ];


                $phonePeTransactionModel->insert($datatoinsert);

                $transactionID = $phonePeTransactionModel->db->insertID();
                $ordersModel = new OrdersModel();
                $validation = &$ordersModel;
                $ordersModel->set(['transaction_id' => $transactionID, 'status' => 1])->where('razorpay_initiate_order_id', $orderID)->update();
                //Order TimeLine
                $ordersTimelineModel = new OrdersTimelineModel();
                $timelineData = [
                    "order_id" => $orderID,
                    "status" => 1,
                    "timeline" => date("d-m-y H:i:s"),
                    "remark" => 'Order confirmed',
                ];
                $ordersTimelineModel->insert($timelineData);
                $statusCode = 200;
                $response = [
                    'message' => 'Payment successfull',
                ];

                $ordersModel = new OrdersModel();
                $orderDetails = $ordersModel->where('razorpay_initiate_order_id', $orderID)->first();

                $total = $orderDetails["total"];
                $couponDiscount = 0;
                if ($orderDetails['coupon'] != 0) {
                    $couponModel = new CouponModel();
                    $coupon = $couponModel->find($orderDetails['coupon']);
                    switch ($coupon['coupon_type']) {
                        case 1:
                            $couponDiscount = ($total / 100 * $coupon['coupon_type_name']);
                            break;
                        case 2:
                            $couponDiscount = $coupon['coupon_type_name'];
                            break;
                    }
                }
                // $invoicePath = $this->makeInvoice($orderDetails, $ordersModel);
                $invoiceController = new InvoiceController();
                $orderDetails['coupon_discount'] = $couponDiscount;
                // print_r($orderDetails);
                $invoicePath = $invoiceController->makeInvoice($orderDetails, $ordersModel);

                $statusCode = 200;
                $response = [
                    'invoice_path' => $invoicePath,
                ];
                $cartModel = new CartModel();
                $cartModel->where('customer_id', $customerID)->delete();
            }
        } catch (Exception $e) {
            // echo "<pre>";
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getMessage(),
            ];
            $response['status'] = $statusCode;
            // print_r($response);die;

            // return $this->respond($response, $statusCode);
            return $response;
            // return redirect()->to('http://localhost:3000/failure', 200, 'refresh');
        }
        // return view('inc/template/payment_success');
        // return redirect()->to('http://localhost:3000/success?invoice=' . $invoicePath, 200, 'refresh');

        $response['status'] = $statusCode;

        // return $this->respond($response, $statusCode);

        return $response;
    }


    public function paymentSuccess()
    {
        return view('inc/template/payment_success');
    }

    public function makeInvoiceRazorPay($orderData, &$ordersModel)
    {
        $invoiceModel = new InvoiceModel();
        $validation = &$invoiceModel;
        $invoiceModel->insert(['invoice_number' => '']);
        $invoiceID = $invoiceModel->db->insertID();
        $invoiceNumber = 'HP' . str_pad($invoiceID, 4, '0', STR_PAD_LEFT);

        // die(var_dump($orderData));
        $ordersModel->set(['invoice_id' => $invoiceID])->where('razorpay_initiate_order_id', $orderData['razorpay_initiate_order_id'])->update();
        // Invoice
        $customerID = $orderData['customer_id'];
        $subTotal = $orderData['subtotal'];
        $total = $orderData['total'];
        $discount = $orderData['discount'] ? $orderData['discount'] : 0;
        $couponDiscount = $orderData['coupon_discount'] ? $orderData['coupon_discount'] : 0;
        $orderID = $orderData['razorpay_initiate_order_id'];
        $customerModel = new CustomerModel();
        $validation = &$customerModel;
        $customerData = $customerModel->find($customerID);
        $customerAddressModel = new CustomerAddressModel();
        $validation = &$customerAddressModel;
        $addressData = $customerAddressModel->find($orderData['address_id']);
        // $address = $
        $headData = [
            'full_name' => $addressData['first_name'] . ' ' . $addressData['last_name'],
            'address_line1' => $addressData['street_address'],
            'address_line2' => implode(', ', [$addressData['city'], $addressData['state'], $addressData['pincode']]),
            'phone_no' => $addressData['phone'],
            'email_id' => $addressData['email'],
            'invoice_number' => $invoiceNumber,
            'order_id' => $orderData['razorpay_order_id'],
            'invoice_date' => date('Y-m-d'),
            'invoice_time' => date('H:i:s'),
            'customer_id' => $customerID,
            'total' => $total,
            'discount' => $discount,
            'coupon_discount' => $couponDiscount,
            'subtotal' => $subTotal,
            'is_cod' => $orderData['is_cod'],
            'coupon' => $orderData['coupon'],
        ];
        $orderProductsModel = new OrderProductsModel();
        $validation = &$orderProductsModel;
        $invoiceProductsData = $orderProductsModel->select([
            'af_products.name AS title',
            'af_brands.id AS brand_id',
            'af_brands.name AS brand_name',
            'af_products.product_code AS product_code',
            'af_order_products.actual_price AS actual_price',
            'af_order_products.increase_percent AS increase_percent',
            'af_order_products.discounted_percent AS discounted_percent',
            'af_order_products.quantity AS quantity',
        ])->join('af_products', 'af_products.id = af_order_products.product_id')
            ->join('af_brands', 'af_brands.id = af_products.brand_id')
            ->where('af_order_products.order_id', $orderData['id'])->findAll();
        helper('products');
        // print_r($invoiceProductsData);
        $invoiceProductsData = array_map('get_discounted_price', $invoiceProductsData);
        // print_r($invoiceProductsData);
        // die;
        foreach ($invoiceProductsData as &$product) {
            // $product['price'] = $product['actual_price'] - ($product['actual_price'] / 100 * $product['discounted_percent']);
            $product['total'] = $product['actual_price'] * $product['quantity'];
            // unset($product['actual_price'], $product['discounted_percent']);
        }
        $invoiceController = new InvoiceController();
        $invoicePath = $invoiceController->makeInvoice($headData, $invoiceProductsData);

        $invoiceModel->set(['invoice_number' => $invoiceNumber, 'invoice_path' => $invoicePath])->update($invoiceID);

        $email = $this->sendEmail($headData['email_id'], $invoicePath);



        $order_id = $headData['order_id'];
        $subtotal = $headData["subtotal"];
        $emailController = new EmailController();
        $subject = 'New Order ' . $headData['order_id'];
        $message = "Hey! Admin,\n There is new order in Dorfee.\nOrder Details:\nOrder ID: $order_id\nSubtotal:$subtotal";
        $emailController->sendMail('sales@dorfee.com', $subject, $message);
        // var_dump($email);exit;
        // if($email) echo 'success';die;

        // empty Cart
        $cartModel = new CartModel();
        $cartModel->where('customer_id', $customerID)->delete();


        return base_url($invoicePath);
    }
    public function makeInvoice($orderData, &$ordersModel)
    {
        $invoiceModel = new InvoiceModel();
        $validation = &$invoiceModel;
        $invoiceModel->insert(['invoice_number' => '']);
        $invoiceID = $invoiceModel->db->insertID();
        $invoiceNumber = 'HP' . str_pad($invoiceID, 4, '0', STR_PAD_LEFT);

        // die(var_dump($orderData));
        $ordersModel->set(['invoice_id' => $invoiceID])->where('razorpay_order_id', $orderData['razorpay_order_id'])->update();
        // Invoice
        $customerID = $orderData['customer_id'];
        $subTotal = $orderData['subtotal'];
        $total = $orderData['total'];
        $discount = $orderData['discount'];
        $couponDiscount = $orderData['coupon_discount'];
        $orderID = $orderData['razorpay_order_id'];
        $customerModel = new CustomerModel();
        $validation = &$customerModel;
        $customerData = $customerModel->find($customerID);
        $customerAddressModel = new CustomerAddressModel();
        $validation = &$customerAddressModel;
        $addressData = $customerAddressModel->find($orderData['address_id']);
        // $address = $
        $headData = [
            'full_name' => $addressData['first_name'] . ' ' . $addressData['last_name'],
            'address_line1' => $addressData['street_address'],
            'address_line2' => implode(', ', [$addressData['city'], $addressData['state'], $addressData['pincode']]),
            'phone_no' => $addressData['phone'],
            'email_id' => $addressData['email'],
            'invoice_number' => $invoiceNumber,
            'order_id' => $orderData['razorpay_order_id'],
            'invoice_date' => date('Y-m-d'),
            'invoice_time' => date('H:i:s'),
            'customer_id' => $customerID,
            'total' => $total,
            'discount' => $discount,
            'coupon_discount' => $couponDiscount,
            'subtotal' => $subTotal,
            'is_cod' => $orderData['is_cod'],
            'coupon' => $orderData['coupon'],
        ];
        $orderProductsModel = new OrderProductsModel();
        $validation = &$orderProductsModel;
        $invoiceProductsData = $orderProductsModel->select([
            'af_products.name AS title',
            'af_brands.id AS brand_id',
            'af_brands.name AS brand_name',
            'af_products.product_code AS product_code',
            'af_order_products.actual_price AS actual_price',
            'af_order_products.increase_percent AS increase_percent',
            'af_order_products.discounted_percent AS discounted_percent',
            'af_order_products.quantity AS quantity',
        ])->join('af_products', 'af_products.id = af_order_products.product_id')
            ->join('af_brands', 'af_brands.id = af_products.brand_id')
            ->where('af_order_products.order_id', $orderData['id'])->findAll();
        helper('products');
        // print_r($invoiceProductsData);
        $invoiceProductsData = array_map('get_discounted_price', $invoiceProductsData);
        // print_r($invoiceProductsData);
        // die;
        foreach ($invoiceProductsData as &$product) {
            // $product['price'] = $product['actual_price'] - ($product['actual_price'] / 100 * $product['discounted_percent']);
            $product['total'] = $product['actual_price'] * $product['quantity'];
            // unset($product['actual_price'], $product['discounted_percent']);
        }
        $invoiceController = new InvoiceController();
        $invoicePath = $invoiceController->makeInvoice($headData, $invoiceProductsData);

        $invoiceModel->set(['invoice_number' => $invoiceNumber, 'invoice_path' => $invoicePath])->update($invoiceID);

        $email = $this->sendEmail($headData['email_id'], $invoicePath);



        $order_id = $headData['order_id'];
        $subtotal = $headData["subtotal"];
        $emailController = new EmailController();
        $subject = 'New Order ' . $headData['order_id'];
        $message = "Hey! Admin,\n There is new order in Dorfee.\nOrder Details:\nOrder ID: $order_id\nSubtotal:$subtotal";
        $emailController->sendMail('sales@dorfee.com', $subject, $message);
        // var_dump($email);exit;
        // if($email) echo 'success';die;

        // empty Cart
        $cartModel = new CartModel();
        $cartModel->where('customer_id', $customerID)->delete();


        return base_url($invoicePath);
    }

    public function sendEmail($emailID, $invoicePath)
    {
        $email = \Config\Services::email();
        $to = $emailID;
        $subject = 'Order Placed Successfully.';
        $message = 'Hey, Congratulations your order placed successfully.';

        $email->setTo($to);
        $email->setFrom('sales@arrangefree.com', 'Arrange Free Sales');
        $email->setSubject($subject);
        $email->setMessage($message);
        $email->attach($invoicePath);
        // print_r($email->send());die;
        return $email->send();
    }
}
