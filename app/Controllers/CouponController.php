<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\BookingsModel;
use App\Models\CartModel;
use App\Models\CouponModel;
use App\Models\OrdersModel;
use CodeIgniter\API\ResponseTrait;
use DateTime;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class CouponController extends BaseController
{
    use ResponseTrait;
    protected $couponModel;
    protected $bookingsModel;

    public function __construct()
    {
        $this->couponModel = new CouponModel(); // ✅ Load the model manually
        $this->bookingsModel = new BookingsModel();
    }

    public function create()
    {
        try {
            $couponModel = new CouponModel();
            $validation = \Config\Services::validation();

            $couponData = [
                'coupon_category' => $this->request->getVar('coupon_category'),
                'shop_keeper' => $this->request->getVar('shop_keeper'),
                'channel_partner' => json_encode($this->request->getVar('channel_partner')),
                'area' => $this->request->getVar('area'),
                'universal' => $this->request->getVar('universal'),
                'coupon_type' => $this->request->getVar('coupon_type'),
                'coupon_type_name' => $this->request->getVar('coupon_type_name'),
                'coupon_name' => $this->request->getVar('coupon_name'),
                'coupon_expiry' => $this->request->getVar('coupon_expiry'),
                'cart_minimum_amount' => $this->request->getVar('cart_minimum_amount'),
                'coupon_use_limit' => $this->request->getVar('coupon_use_limit'),
                'coupon_per_user_limit' => $this->request->getVar('coupon_per_user_limit'),
                'coupon_code' => $this->request->getVar('coupon_code'),
                'terms_and_conditions' => json_encode($this->request->getVar('terms_and_conditions')),
                'description' => $this->request->getVar('description')
            ];

            $couponModel->insert($couponData);

            if (!empty($validation->getErrors())) {
                throw new \Exception('Validation', 400);
            }

            if ($couponModel->db->error()['code']) {
                throw new \Exception($couponModel->db->error()['message'], 500);
            }

            if ($couponModel->db->affectedRows() == 1) {
                $response = [
                    'status' => 200,
                    'message' => 'Coupon created successfully.',
                    'coupon_id' => $couponModel->db->insertID()
                ];
            }
        } catch (\Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'status' => $statusCode,
                'error' => $e->getCode() === 400 ? ['validation' => $validation->getErrors()] : $e->getMessage()
            ];
        }

        return $this->respond($response, $response['status']);
    }


    public function getById($id)
    {
        try {
            $couponModel = new CouponModel();
            $coupon = $couponModel->find($id);

            if (!$coupon) {
                throw new Exception('Coupon not found', 404);
            }
            $coupon['coupon_expiry'] = json_decode($coupon['coupon_expiry'], true);
            $response = [
                'status' => 200,
                'coupon' => $coupon
            ];
        } catch (Exception $e) {
            $response = [
                'status' => $e->getCode() === 404 ? 404 : 500,
                'error' => $e->getCode() === 404 ? 'Coupon not found' : $e->getMessage()
            ];
        }

        return $this->respond($response, $response['status']);
    }

    public function update($id)
    {
        try {
            $couponModel = new CouponModel();
            $validation = \Config\Services::validation();
            $coupon = $couponModel->find($id);

            if (!$coupon) {
                throw new \Exception('Coupon not found', 404);
            }

            $couponData = [
                'coupon_category' => $this->request->getVar('coupon_category'),
                'shop_keeper' => $this->request->getVar('shop_keeper'),
                'channel_partner' => json_encode($this->request->getVar('channel_partner')),
                'area' => $this->request->getVar('area'),
                'universal' => $this->request->getVar('universal'),
                'coupon_type' => $this->request->getVar('coupon_type'),
                'coupon_type_name' => $this->request->getVar('coupon_type_name'),
                'coupon_name' => $this->request->getVar('coupon_name'),
                'coupon_expiry' => $this->request->getVar('coupon_expiry'),
                'cart_minimum_amount' => $this->request->getVar('cart_minimum_amount'),
                'coupon_use_limit' => $this->request->getVar('coupon_use_limit'),
                'coupon_per_user_limit' => $this->request->getVar('coupon_per_user_limit'),
                'coupon_code' => $this->request->getVar('coupon_code'),
                'terms_and_conditions' => json_encode($this->request->getVar('terms_and_conditions')),
                'description' => $this->request->getVar('description')
            ];
            $couponModel->update($id, $couponData);

            if (!empty($validation->getErrors())) {
                throw new Exception('Validation', 400);
            }

            if ($couponModel->db->error()['code']) {
                throw new Exception($couponModel->db->error()['message'], 500);
            }

            $response = [
                'status' => 200,
                'message' => 'Coupon updated successfully.'
            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : ($e->getCode() === 404 ? 404 : 500);
            $response = [
                'status' => $statusCode,
                'error' => $e->getCode() === 400 ? ['validation' => $validation->getErrors()] : $e->getMessage()
            ];
        }

        return $this->respond($response, $response['status']);
    }


    public function delete($id)
    {
        try {
            $couponModel = new CouponModel();
            $coupon = $couponModel->find($id);

            if (!$coupon) {
                throw new \Exception('Coupon not found', 404);
            }

            $couponModel->delete($id);

            $response = [
                'status' => 200,
                'message' => 'Coupon deleted successfully.'
            ];
        } catch (\Exception $e) {
            $response = [
                'status' => $e->getCode() === 404 ? 404 : 500,
                'error' => $e->getCode() === 404 ? 'Coupon not found' : $e->getMessage()
            ];
        }

        return $this->respond($response, $response['status']);
    }

    public function getAllCoupon()
    {
        try {
            $couponModel = new CouponModel();
            $coupons = $couponModel->findAll();


            $response = [
                'status' => 200,
                'message' => 'Success',
                'coupons' => $coupons
            ];
        } catch (\Exception $e) {
            $response = [
                'status' => 500,
                'error' => $e->getMessage()
            ];
        }

        return $this->respond($response, $response['status']);
    }

    public function applyCoupon()
    {
        try {
            $couponModel = new CouponModel();
            $couponCode = $this->request->getVar('coupon_code');
            $cartAmount = $this->request->getVar('cart_amount');


            if (!$this->request->hasHeader('token')) {
                $statusCode = 403;
                $response = [
                    'message' => 'Access Denied',
                ];
                $response['status'] = $statusCode;
                return $this->respond($response, $statusCode);
            }

            $token = $this->request->header('token');
            $token = $token->getValue();
            $key = getenv('JWT_SECRET');
            $tokenData = JWT::decode($token, new Key($key, 'HS256'));
            $customerID = $tokenData->customer_id;

            $cartModel = new CartModel();
            $cartProducts = $cartModel->select([
                'af_cart.product_id AS product_id',
                'af_cart.quantity AS quantity',
                'af_products.actual_price AS actual_price',
                // 'af_products.actual_price AS base_price',
                'af_products.increase_percent AS increase_percent',
                'af_products.discounted_percent AS discounted_percent',
            ])->join('af_products', 'af_products.id = af_cart.product_id')->where('af_cart.customer_id', $customerID)->findAll();

            helper('products');
            $cartProducts = array_map('get_discounted_price', $cartProducts);
            // print_r($cartProducts);
            $cartAmount = array_sum(array_column($cartProducts, 'discounted_price'));
            // echo $cartAmount;die;
            $coupon = $couponModel->where('coupon_code', $couponCode)->first();
            if (empty($coupon)) {
                throw new \Exception('Invalid coupon code', 409);
            }
            $exp_date = explode(',', $coupon['coupon_expiry']);
            $exp_date = $exp_date[1];
            $exp_date = substr($exp_date, 2, strlen($exp_date) - 6);
            // echo $exp_date;die;
            $exp_date = new DateTime($exp_date);
            $today = new DateTime('now');
            // print_r($coupon['coupon_expiry']);die;



            if ($cartAmount < $coupon['cart_minimum_amount']) {
                throw new \Exception('Cart minimum amount shuld be ' . $coupon['cart_minimum_amount'] . ' more!', 409);
            }

            if ($exp_date < $today) {
                throw new \Exception('The coupon has expired!', 409);
            }

            if ($coupon['coupon_used_count'] >= $coupon['coupon_use_limit']) {
                throw new \Exception('The coupon used limit has exceeded!', 409);
            }

            // Get total coupon used count for the user.
            $ordersModel = new OrdersModel();
            $couponUsedCount = $ordersModel->where('coupon', $coupon['id'])->where('customer_id', $customerID)->countAllResults();
            if ($coupon['coupon_per_user_limit'] <= $couponUsedCount) {
                throw new Exception('The maximum individual limit is exceeded!', 409);
            }
            $response = [
                'status' => 200,
                'message' => 'Coupon applied successfully.',
                'coupon' => $coupon,
            ];
        } catch (\Exception $e) {
            $response = [
                'status' => $e->getCode(),
                'error' => $e->getMessage(),
            ];
        }

        return $this->respond($response, $response['status']);
    }
    public function getActiveCoupons()
    {
        try {
            $couponModel = new CouponModel();
            $currentDate = new DateTime(); // Get today's date

            $coupons = $couponModel->findAll();
            $activeCoupons = [];

            foreach ($coupons as $coupon) {
                // Extract coupon expiry date
                $exp_date_parts = explode(',', $coupon['coupon_expiry']);
                if (isset($exp_date_parts[1])) {
                    $exp_date_str = substr($exp_date_parts[1], 2, strlen($exp_date_parts[1]) - 6);
                    $exp_date = new DateTime($exp_date_str);

                    // Check if coupon is active
                    if ($exp_date >= $currentDate) {
                        $activeCoupons[] = $coupon;
                    }
                }
            }

            if (empty($activeCoupons)) {
                return $this->failNotFound('No active coupons found');
            }

            return $this->respond([
                'status'  => 200,
                'message' => 'Active coupons retrieved successfully',
                'data'    => $activeCoupons
            ], 200);
        } catch (\Exception $e) {
            return $this->failServerError('Failed to retrieve active coupons.');
        }
    }
    public function applyCouponSeeb()
    {
        $bookingsModel = new BookingsModel();

        try {
            $data = $this->request->getJSON(true) ?? $this->request->getVar();

            if (empty($data['user_id']) || empty($data['coupon_code']) || empty($data['cart_total'])) {
                return $this->respond([
                    'status'  => 400,
                    'message' => 'User ID, Coupon Code, and Cart Total are required.'
                ], 400);
            }

            $coupon = $this->couponModel->where('coupon_code', $data['coupon_code'])
                ->where('is_active', 1)
                ->first();

            if (!$coupon) {
                return $this->respond([
                    'status'  => 404,
                    'message' => 'Invalid or expired coupon code.'
                ], 404);
            }

            // Check if coupon has expired
            $currentDate = date('Y-m-d');
            if ($coupon['coupon_expiry'] < $currentDate) {
                return $this->respond([
                    'status'  => 400,
                    'message' => 'This coupon has expired.'
                ], 400);
            }

            // Check if cart meets the minimum amount
            if ($data['cart_total'] < $coupon['cart_minimum_amount']) {
                return $this->respond([
                    'status'  => 400,
                    'message' => 'Cart total must be at least ₹' . $coupon['cart_minimum_amount'] . ' to apply this coupon.'
                ], 400);
            }

            // Check overall coupon usage limit
            if ($coupon['coupon_use_limit'] > 0) {
                $usedCount = $bookingsModel->where('applied_coupon', $coupon['coupon_code'])->countAllResults();
                if ($usedCount >= $coupon['coupon_use_limit']) {
                    return $this->respond([
                        'status'  => 400,
                        'message' => 'This coupon has reached its maximum usage limit.'
                    ], 400);
                }
            }

            // Check per-user usage limit
            if ($coupon['coupon_per_user_limit'] > 0) {
                $userUsedCount = $bookingsModel
                    ->where('applied_coupon', $coupon['coupon_code'])
                    ->where('user_id', $data['user_id'])
                    ->countAllResults();

                if ($userUsedCount >= $coupon['coupon_per_user_limit']) {
                    return $this->respond([
                        'status'  => 400,
                        'message' => 'You have already used this coupon.'
                    ], 400);
                }
            }

            // **Apply discount based on coupon type**
            $discountAmount = 0;
            if ($coupon['coupon_type'] == 1) {  // **Percentage discount**
                $discountAmount = ($data['cart_total'] * $coupon['coupon_type_name']) / 100;
            } elseif ($coupon['coupon_type'] == 2) {  // **Fixed amount discount**
                $discountAmount = $coupon['coupon_type_name'];
            }

            // Ensure discount does not exceed cart total
            $discountAmount = min($discountAmount, $data['cart_total']);
            $finalAmount = $data['cart_total'] - $discountAmount;

            return $this->respond([
                'status'       => 200,
                'message'      => 'Coupon applied successfully!',
                'coupon_code'  => $coupon['coupon_code'],
                'discount'     => $discountAmount,
                'final_amount' => $finalAmount
            ], 200);
        } catch (\Exception $e) {
            log_message('error', 'Coupon Application Error: ' . $e->getMessage());
            return $this->respond([
                'status'  => 500,
                'message' => 'Failed to apply coupon. Please try again.'
            ], 500);
        }
    }
}
