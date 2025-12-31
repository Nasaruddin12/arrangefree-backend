<?php

namespace App\Services;

class ServiceAmountCalculator
{
    public static function calculate(array $data): array
    {
        $rateType = $data['rate_type'] ?? 'unit';
        $rate     = floatval($data['rate'] ?? 0);
        $valueRaw = trim((string) ($data['value'] ?? 1));

        /** ---------------------------------
         * Area Calculation
         * --------------------------------*/
        $area = 1;

        if ($rateType === 'square_feet') {
            $value = strtoupper(str_replace(' ', '', $valueRaw));

            if (strpos($value, 'X') !== false) {
                [$w, $h] = explode('X', $value);
                $area = floatval($w) * floatval($h);
            } else {
                $area = floatval($value);
            }
        }

        /** ---------------------------------
         * Base Amount
         * --------------------------------*/
        $baseAmount = ($rateType === 'square_feet')
            ? $area * $rate
            : floatval($valueRaw) * $rate;

        /** ---------------------------------
         * Addons Calculation
         * --------------------------------*/
        $addonTotal = 0;

        $addons = is_array($data['addons'] ?? null)
            ? $data['addons']
            : json_decode($data['addons'] ?? '[]', true);

        foreach ($addons as $addon) {
            $qty   = floatval($addon['qty'] ?? 0);
            $price = floatval($addon['price'] ?? 0);
            $addonTotal += $qty * $price;
        }

        /** ---------------------------------
         * Final Amount
         * --------------------------------*/
        $totalAmount = round($baseAmount + $addonTotal, 2);

        return [
            'area'        => round($area, 2),
            'base_amount' => round($baseAmount, 2),
            'addon_total' => round($addonTotal, 2),
            'total'       => $totalAmount,
        ];
    }
}
