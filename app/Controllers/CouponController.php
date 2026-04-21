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
                'customer_id' => $this->normalizeCustomerCouponId($this->request->getVar('customer_id')),
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
                'customer_id' => $this->normalizeCustomerCouponId($this->request->getVar('customer_id')),
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

    
    public function getActiveCoupons()
    {
        try {
            $couponModel = new CouponModel();
            $currentDate = new DateTime(); // Get today's date

            $coupons = $couponModel->findAll();
            $activeCoupons = [];

            foreach ($coupons as $coupon) {
                if (!empty($coupon['customer_id'])) {
                    continue;
                }

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
            log_message('error', 'Get Active Coupons Error: ' . $e->getMessage());
            return $this->failServerError('Failed to retrieve active coupons.');
        }
    }
    public function applyCouponSeeb()
    {
        try {
            $data = $this->request->getJSON(true) ?? $this->request->getVar();

            // Validate required fields with specific messages
            if (empty($data['user_id'])) {
                return $this->respond([
                    'status'  => 400,
                    'message' => 'User ID is required.'
                ], 400);
            }

            if (empty($data['coupon_code'])) {
                return $this->respond([
                    'status'  => 400,
                    'message' => 'Please enter a coupon code.'
                ], 400);
            }

            if (empty($data['cart_total'])) {
                return $this->respond([
                    'status'  => 400,
                    'message' => 'Cart Total is required.'
                ], 400);
            }

            // Use coupon validation service
            $couponValidator = new \App\Services\CouponValidationService();
            $result = $couponValidator->validateAndCalculate(
                $data['coupon_code'],
                (float) $data['cart_total'],
                $data['user_id']
            );

            if (!$result['valid']) {
                return $this->respond([
                    'status'  => 400,
                    'message' => $result['message']
                ], 400);
            }

            $finalAmount = (float) $data['cart_total'] - $result['discount'];

            return $this->respond([
                'status'       => 200,
                'message'      => 'Coupon applied successfully!',
                'coupon_code'  => $result['coupon']['coupon_code'],
                'discount'     => $result['discount'],
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

    private function normalizeCustomerCouponId($customerId): ?int
    {
        $customerId = (int) $customerId;

        return $customerId > 0 ? $customerId : null;
    }
}
