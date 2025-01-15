<?php
if (!function_exists('get_discounted_price')) {
    function get_discounted_price($productRecord)
    {
        // var_dump($productRecord);
        $increasePrice = ($productRecord['actual_price'] / 100 * $productRecord['increase_percent']);
        $discount = $increasePrice / 100 * $productRecord['discounted_percent'];
        $actualPrice = $productRecord['actual_price'] + $increasePrice;
        $discountedPercent = $discount / $actualPrice * 100;

        // $singleProductPrice = ($productRecord['actual_price'] - ($productRecord['actual_price'] / 100 * $productRecord['discounted_percent']));
        // $productRecord['discounted_price'] = $singleProductPrice;
        $productRecord['actual_price'] = ceil($actualPrice);
        $productRecord['discounted_percent'] = ceil($discountedPercent);
        $productRecord['discounted_price'] = ceil($actualPrice - $discount);
        return $productRecord;
    }
}
