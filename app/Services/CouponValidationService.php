<?php

namespace App\Services;

use App\Models\BookingsModel;
use App\Models\CouponModel;

class CouponValidationService
{
    protected $couponModel;
    protected $bookingsModel;

    public function __construct()
    {
        $this->couponModel = new CouponModel();
        $this->bookingsModel = new BookingsModel();
    }

    /**
     * Validate coupon before applying
     * 
     * @param string $couponCode
     * @param float $cartTotal
     * @param int $userId
     * @return array ['valid' => bool, 'coupon' => array|null, 'message' => string, 'discount' => float]
     */
    public function validateAndCalculate($couponCode, $cartTotal, $userId)
    {
        try {
            // 1️⃣ Check if coupon exists and is active
            $coupon = $this->couponModel
                ->where('coupon_code', $couponCode)
                ->where('status', 1)
                ->first();

            if (!$coupon) {
                return [
                    'valid' => false,
                    'message' => 'Invalid or inactive coupon code.',
                    'coupon' => null,
                    'discount' => 0
                ];
            }

            // 2️⃣ Validate coupon expiry date
            $today = date('Y-m-d');
            $expiry = $coupon['expiry_date'] ?? $coupon['coupon_expiry'];
            
            if ($expiry < $today) {
                return [
                    'valid' => false,
                    'message' => 'This coupon has expired.',
                    'coupon' => null,
                    'discount' => 0
                ];
            }

            // 3️⃣ Validate cart minimum amount
            if ($cartTotal < $coupon['cart_minimum_amount']) {
                return [
                    'valid' => false,
                    'message' => 'Cart total must be at least ₹' . number_format($coupon['cart_minimum_amount'], 2) . ' to apply this coupon.',
                    'coupon' => null,
                    'discount' => 0
                ];
            }

            // 4️⃣ Validate overall coupon usage limit
            if ($coupon['coupon_use_limit'] > 0) {
                $usedCount = $this->bookingsModel
                    ->where('applied_coupon', $couponCode)
                    ->countAllResults();

                if ($usedCount >= $coupon['coupon_use_limit']) {
                    return [
                        'valid' => false,
                        'message' => 'This coupon has reached its maximum usage limit.',
                        'coupon' => null,
                        'discount' => 0
                    ];
                }
            }

            // 5️⃣ Validate per-user usage limit
            if ($coupon['coupon_per_user_limit'] > 0) {
                $userUsedCount = $this->bookingsModel
                    ->where('applied_coupon', $couponCode)
                    ->where('user_id', $userId)
                    ->countAllResults();

                if ($userUsedCount >= $coupon['coupon_per_user_limit']) {
                    return [
                        'valid' => false,
                        'message' => 'You have already used this coupon the maximum number of times.',
                        'coupon' => null,
                        'discount' => 0
                    ];
                }
            }

            // 6️⃣ Calculate discount
            $discount = $this->calculateDiscount($cartTotal, $coupon);

            return [
                'valid' => true,
                'message' => 'Coupon applied successfully!',
                'coupon' => $coupon,
                'discount' => $discount
            ];

        } catch (\Exception $e) {
            return [
                'valid' => false,
                'message' => 'Error validating coupon: ' . $e->getMessage(),
                'coupon' => null,
                'discount' => 0
            ];
        }
    }

    /**
     * Calculate discount amount based on coupon type
     * 
     * @param float $cartTotal
     * @param array $coupon
     * @return float
     */
    public function calculateDiscount($cartTotal, $coupon)
    {
        $discountAmount = 0;

        if ((int) $coupon['coupon_type'] === 1) {
            // Percentage discount
            $discountAmount = round(($cartTotal * $coupon['coupon_type_name']) / 100, 2);
        } elseif ((int) $coupon['coupon_type'] === 2) {
            // Fixed amount discount
            $discountAmount = (float) $coupon['coupon_type_name'];
        }

        // Ensure discount doesn't exceed cart total
        return min($discountAmount, $cartTotal);
    }

    /**
     * Get coupon by code
     * 
     * @param string $couponCode
     * @return array|null
     */
    public function getCoupon($couponCode)
    {
        return $this->couponModel
            ->where('coupon_code', $couponCode)
            ->where('status', 1)
            ->first();
    }
}
