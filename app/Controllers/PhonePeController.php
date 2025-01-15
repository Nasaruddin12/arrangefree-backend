<?php


namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\CartModel;
use App\Models\CouponModel;
use App\Models\CustomerModel;
use App\Models\OrderProductsModel;
use App\Models\OrdersModel;
use App\Models\OrdersTimelineModel;
use App\Models\PhonePeTransactionModel;
use App\Models\UsersModel;
use CodeIgniter\API\ResponseTrait;
use Config\Razorpay;
use DateTime;
use Exception;


class PhonePeController extends BaseController
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
        return $singleProductPrice * $productRecord['quantity'];
        // return ($productRecord['actual_price']);
    }

    public function makeCOD()
    {
        try {
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

            $cartProductsPrice = array_map([$this, 'get_subtotal_price'], $cartProducts);
            $subTotal = array_sum($cartProductsPrice);
            // getting total amount
            $cartProductsDiscountedPrice = array_map([$this, 'get_discounted_price'], $cartProducts);
            $total = array_sum($cartProductsDiscountedPrice);

            // $codOrderID = 'cod' . bin2hex(microtime(true));
            $orderUniqueID = 'ORD' . date('YmdHis');
            $orderData = [
                'customer_id' => $customerID,
                'address_id' => $addressID,
                'total' => $total,
                'subtotal' => $subTotal,
                'razorpay_order_id' => $orderUniqueID,
                'is_cod' => 1,
                'status' => 1,
            ];

            // Getting coupon discount if exist
            $couponDiscount = 0;
            if (!empty($coupon)) {
                switch ($coupon['coupon_type']) {
                    case 1:
                        $couponDiscount = ($total / 100 * $coupon['coupon_type_name']);
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
            $orderData['discount'] = $subTotal - $total;
            // $orderData['total'] -= $couponDiscount;
            // $orderData['total'] = $subTotal - $total;
            // echo $orderData['discount'];die;


            // Creating order
            $ordersModel = new OrdersModel();
            $validation = &$ordersModel;
            $orderID = $ordersModel->insert($orderData);
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


            // Getting customer details
            $CustomerModel = new CustomerModel();
            $validation = &$CustomerModel;
            $userdata = $CustomerModel->where("id", $customerID)->first();
            // print_r($userdata);
            // echo $customerID;
            // echo "test12";
            // die;
            $mobile = $userdata["mobile_no"];
            $txnID = "TXN" . date("Ymdhis");

            $totalAmount = $subTotal - $couponDiscount;

            $ordersModel = new OrdersModel();
            $orderDetails = $ordersModel->find($orderID);
            $invoiceController = new InvoiceController();
            $orderDetails['coupon_discount'] = $couponDiscount;

            $invoicePath = $invoiceController->makeInvoice($orderDetails, $ordersModel);
            $statusCode = 200;
            $response = [
                'invoice_path' => $invoicePath,
                'message' => 'Order placed successfully.',
            ];
            $response['status'] = $statusCode;
            $cartModel->where('customer_id', $customerID)->delete();
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'status' => $statusCode,
                'error' => $e->getCode() === 400 ? ['validation' => $validation->errors()] : $e->getMessage()
            ];
            // return $this->respond($response, $statusCode);
        }

        return $this->respond($response, $statusCode);
    }

    public function initiatePayment()
    {
        try {
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
                    throw new \Exception('Invalid coupon code', 409);
                }

                if ($exp_date < $today) {
                    throw new \Exception('The coupon has expired!', 409);
                }

                if ($coupon['coupon_used_count'] >= $coupon['coupon_use_limit']) {
                    throw new \Exception('The coupon used limit has exceeded!', 409);
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
            $cartProductsPrice = array_map([$this, 'get_total_price'], $cartProducts);
            $total = array_sum($cartProductsPrice);
            // getting subtotal amount
            $cartProductsDiscountedPrice = array_map([$this, 'get_discounted_price'], $cartProducts);
            $subTotal = array_sum($cartProductsDiscountedPrice);

            // $codOrderID = 'cod' . bin2hex(microtime(true));
            $orderUniqueID = 'ORD' . date('YmdHis');
            $orderData = [
                'customer_id' => $customerID,
                'address_id' => $addressID,
                'total' => $total,
                'subtotal' => $subTotal,
                'razorpay_order_id' => $orderUniqueID,
                'is_cod' => 0,
                'status' => 0,
            ];

            // Getting coupon discount if exist
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
                // $orderData['coupon_discount'] = $couponDiscount;
                $orderData['coupon'] = $couponID;
            }
            $orderData['discount'] = $total - $subTotal;
            // echo $orderData['discount'];die;

            // Creating order
            $ordersModel = new OrdersModel();
            $validation = &$ordersModel;
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

            // Getting customer details
            $CustomerModel = new CustomerModel();
            $validation = &$CustomerModel;
            $userdata = $CustomerModel->where("id", $customerID)->first();
            $mobile = $userdata["mobile_no"];

            $txnID = "TXN" . date("Ymdhis");

            $totalAmount = $subTotal - $couponDiscount;
            $token = [
                "merchantId" => "DECORAONLINE",
                "merchantTransactionId" => $txnID,
                "merchantUserId" => $customerID,
                "amount" => $totalAmount * 100,
                "redirectUrl" => "https://dorfee.com/success",
                "redirectMode" => "POST",
                "callbackUrl" => getenv('PHONEPE_CALLBACK_URL') . "/payment/verify-payment",
                "mobileNumber" => $mobile,
                "paymentInstrument" => [
                    "type" => "PAY_PAGE"
                ],
            ];


            // PhonePe Pay API
            $string = json_encode($token);
            $encoded = base64_encode($string);

            $curl = curl_init();
            $str = $encoded . "/pg/v1/pay" . getenv('SALT_KEY');
            $sha256 = hash("sha256", $str);
            $toPass = $sha256 . "###" . 1;


            curl_setopt_array(
                $curl,
                array(
                    CURLOPT_URL => getenv('PHONEPE_HOST') . '/pg/v1/pay',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => json_encode(["request" => $encoded]),
                    CURLOPT_HTTPHEADER => array(
                        'Content-Type: application/json',
                        "X-VERIFY: $toPass",
                        'Accept: application/json'
                    ),
                )
            );

            $responseAPI = curl_exec($curl);

            curl_close($curl);
            $statusCode = 200;
            $response = [
                "Status" => 200,
                "Response" => json_decode($responseAPI, true)
            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? ['validation' => $validation->errors()] : $e->getMessage(),
                "line" => $e->getCode() === 400 ? ['validation' => $validation->errors()] : $e->getLine()
            ];
        }
        // $    response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    function verifyPayment()
    {
        try {
            $this->response->setHeader('Content-Type', 'application/json');

            $data = $this->request->getVar("response");
            $array = base64_decode($data);

            $jsonObject = json_decode($array);
            $responseData = json_decode(json_encode($jsonObject), true);

            $datatoinsert = [
                "merchantTransactionId" => $responseData["data"]["merchantTransactionId"],
                "transactionId" => $responseData["data"]["transactionId"],
                "payment_status" => $responseData["data"]["state"],
                "transation_status" => $responseData["code"],
                "amount" => $responseData["data"]["amount"],
                "form_json" => serialize($responseData["data"]["paymentInstrument"]),
            ];

            $PhonePeTransactionModel = new PhonePeTransactionModel();
            $rest = $PhonePeTransactionModel->insert($datatoinsert);
            if ($rest) {


                $transactionId = $responseData["data"]["transactionId"];
                $merchanttransactionId = $responseData["data"]["merchantTransactionId"];
                $amount = $responseData["data"]["amount"];
                $Code = $responseData["code"];


                $emailController = new EmailController();
                $subject = 'New Transaction' . $responseData["data"]["transactionId"];
                $message = "Hey! Admin,\n There is new transaction in Dorfee.\ntransaction Details:\nTransaction ID:$transactionId \nMerchantTransactionID: $merchanttransactionId \nAmount:$amount \n Transaction Status:$Code";
                $emailController->sendMail('transactions@dorfee.com', $subject, $message);

                $response = [
                    "Status" => 200,
                    "Msg" => "Data Added Successfully"
                ];
            } else {
                $response = [
                    "Status" => 400,
                    "Msg" => "Data Not Added Successfully"
                ];
            }


            return $this->respond($response, 200);
        } catch (Exception $e) {
            return $this->respond(["error" => $e->getMessage()], 200);
        }
    }
}
