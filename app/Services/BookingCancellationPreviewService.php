<?php

namespace App\Services;

use App\Models\CouponModel;

class BookingCancellationPreviewService
{
    private CouponModel $couponsModel;
    private BookingFinancialSummaryService $bookingFinancialSummaryService;

    /** @var callable */
    private $calculateCouponRemovalForSelections;
    /** @var callable */
    private $calculateRefundTaxBreakdown;
    /** @var callable */
    private $buildSingleServiceCancellationDetailsData;

    public function __construct(
        CouponModel $couponsModel,
        BookingFinancialSummaryService $bookingFinancialSummaryService,
        callable $calculateCouponRemovalForSelections,
        callable $calculateRefundTaxBreakdown,
        callable $buildSingleServiceCancellationDetailsData
    ) {
        $this->couponsModel = $couponsModel;
        $this->bookingFinancialSummaryService = $bookingFinancialSummaryService;
        $this->calculateCouponRemovalForSelections = $calculateCouponRemovalForSelections;
        $this->calculateRefundTaxBreakdown = $calculateRefundTaxBreakdown;
        $this->buildSingleServiceCancellationDetailsData = $buildSingleServiceCancellationDetailsData;
    }

    public function build(array $booking, ?array $selection = null, array $selections = []): array
    {
        $bookingId = (int) $booking['id'];
        $currentSummary = $this->bookingFinancialSummaryService->summarize($booking);
        $paidAmount = (float) $currentSummary['paid_amount'];
        $currentOfferDiscount = (float) $currentSummary['offer_discount_amount'];
        $currentCouponDiscount = (float) $currentSummary['coupon_discount_amount'];
        $currentDiscount = (float) $currentSummary['discount_amount'];
        $currentFinalAmount = (float) $currentSummary['final_amount'];
        $currentSubtotal = (float) $currentSummary['subtotal_amount'];
        $currentDueAmount = (float) $currentSummary['due_amount'];
        $couponCode = $booking['applied_coupon'] ?? null;
        $coupon = $couponCode
            ? $this->couponsModel->where('coupon_code', $couponCode)->first()
            : null;
        $currentAdditionalApprovedTotal = (float) $currentSummary['additional_approved_total'];

        $cancelledSubtotal = 0.0;
        $cancelledDiscount = 0.0;
        $cancelledOfferDiscount = 0.0;
        $cancelledAdditionalApprovedTotal = 0.0;
        $cancelledCgst = 0.0;
        $cancelledSgst = 0.0;
        $cancelledTotal = 0.0;
        $selectedServiceDetails = [];
        $selectionSummary = [
            'mode' => 'full_booking',
            'service_source' => null,
            'service_details' => null,
        ];

        if (!empty($selections)) {
            $selectionSummary = [
                'mode' => 'partial_bulk',
                'service_source' => null,
                'service_details' => [],
            ];

            $seen = [];
            foreach ($selections as $selectedItem) {
                $serviceId = (int) ($selectedItem['service_id'] ?? 0);
                $serviceSource = (string) ($selectedItem['service_source'] ?? '');

                if ($serviceId <= 0 || !in_array($serviceSource, ['booking_service', 'additional_service'], true)) {
                    throw new \InvalidArgumentException('Each selected service must contain valid service_id and service_source.');
                }

                $itemKey = $serviceSource . ':' . $serviceId;
                if (isset($seen[$itemKey])) {
                    continue;
                }
                $seen[$itemKey] = true;

                $serviceDetails = ($this->buildSingleServiceCancellationDetailsData)($bookingId, $serviceId, $serviceSource, $booking);
                $selectionKey = $serviceSource . ':' . $serviceId;
                $selectedServiceDetails[$selectionKey] = [
                    'selection_key' => $selectionKey,
                    'service_source' => $serviceSource,
                    'subtotal_before_gst' => (float) ($serviceDetails['subtotal_before_gst'] ?? 0),
                ];

                $selectionSummary['service_details'][] = [
                    'service_id' => $serviceId,
                    'service_source' => $serviceSource,
                    'details' => $serviceDetails,
                ];
            }
        } elseif ($selection !== null) {
            $serviceDetails = ($this->buildSingleServiceCancellationDetailsData)(
                $bookingId,
                (int) $selection['service_id'],
                (string) $selection['service_source'],
                $booking
            );
            $selectionKey = (string) $selection['service_source'] . ':' . (int) $selection['service_id'];
            $selectedServiceDetails[$selectionKey] = [
                'selection_key' => $selectionKey,
                'service_source' => (string) $selection['service_source'],
                'subtotal_before_gst' => (float) ($serviceDetails['subtotal_before_gst'] ?? 0),
            ];
            $selectionSummary = [
                'mode' => 'partial_service',
                'service_source' => $selection['service_source'],
                'service_details' => $serviceDetails,
            ];
        } else {
            $cancelledSubtotal = $currentSubtotal;
            $cancelledDiscount = $currentDiscount;
            $cancelledOfferDiscount = $currentOfferDiscount;
            $cancelledAdditionalApprovedTotal = $currentAdditionalApprovedTotal;
            $cancelledCgst = (float) ($booking['cgst'] ?? 0);
            $cancelledSgst = (float) ($booking['sgst'] ?? 0);
            $cancelledTotal = $currentFinalAmount;
        }

        $couponImpact = ($this->calculateCouponRemovalForSelections)($booking, $selectedServiceDetails);
        $cancelledDiscount = (float) ($couponImpact['removed_coupon_discount'] ?? 0);

        if (($selectionSummary['mode'] ?? '') === 'partial_service' && !empty($selectionSummary['service_details'])) {
            $selectionKey = (string) (($selection['service_source'] ?? '') . ':' . (int) ($selection['service_id'] ?? 0));
            $selectionSummary['service_details'] = $this->applyPreviewRefundBreakdown(
                $selectionSummary['service_details'],
                (float) ($couponImpact['allocations'][$selectionKey] ?? 0)
            );
        }

        if (($selectionSummary['mode'] ?? '') === 'partial_bulk' && !empty($selectionSummary['service_details'])) {
            foreach ($selectionSummary['service_details'] as &$serviceSummary) {
                $selectionKey = (string) (($serviceSummary['service_source'] ?? '') . ':' . (int) ($serviceSummary['service_id'] ?? 0));
                $serviceSummary['details'] = $this->applyPreviewRefundBreakdown(
                    $serviceSummary['details'],
                    (float) ($couponImpact['allocations'][$selectionKey] ?? 0)
                );
            }
            unset($serviceSummary);
        }

        if (($selectionSummary['mode'] ?? '') === 'partial_service' && !empty($selectionSummary['service_details'])) {
            $serviceDetails = $selectionSummary['service_details'];
            if (($selection['service_source'] ?? '') === 'booking_service') {
                $cancelledSubtotal = (float) ($serviceDetails['subtotal_before_gst'] ?? 0);
                $cancelledOfferDiscount = (float) ($serviceDetails['offer_discount_total'] ?? 0);
            } else {
                $cancelledAdditionalApprovedTotal = (float) ($serviceDetails['subtotal_with_gst'] ?? 0);
            }
            $cancelledCgst = (float) ($serviceDetails['cgst_amount'] ?? 0);
            $cancelledSgst = (float) ($serviceDetails['sgst_amount'] ?? 0);
            $cancelledTotal = (float) ($serviceDetails['final_refund_amount'] ?? 0);
        }

        if (($selectionSummary['mode'] ?? '') === 'partial_bulk' && !empty($selectionSummary['service_details'])) {
            foreach ($selectionSummary['service_details'] as $serviceSummary) {
                $serviceDetails = $serviceSummary['details'] ?? [];
                if (($serviceSummary['service_source'] ?? '') === 'booking_service') {
                    $cancelledSubtotal += (float) ($serviceDetails['subtotal_before_gst'] ?? 0);
                    $cancelledOfferDiscount += (float) ($serviceDetails['offer_discount_total'] ?? 0);
                } else {
                    $cancelledAdditionalApprovedTotal += (float) ($serviceDetails['subtotal_with_gst'] ?? 0);
                }
                $cancelledCgst += (float) ($serviceDetails['cgst_amount'] ?? 0);
                $cancelledSgst += (float) ($serviceDetails['sgst_amount'] ?? 0);
                $cancelledTotal += (float) ($serviceDetails['final_refund_amount'] ?? 0);
            }
        }

        $remainingSubtotal = max($currentSubtotal - $cancelledSubtotal, 0);
        $afterOfferDiscount = max($currentOfferDiscount - $cancelledOfferDiscount, 0);
        $couponDiscountAfter = (float) ($couponImpact['coupon_discount_after'] ?? 0);
        $discountAfter = $couponDiscountAfter;
        $couponStillValid = (bool) ($couponImpact['coupon_after_valid'] ?? false);
        $discountRemoved = max($currentDiscount - $discountAfter, 0);

        $bookingCgstRate = (float) ($booking['cgst_rate'] ?? 0);
        $bookingSgstRate = (float) ($booking['sgst_rate'] ?? 0);
        $discountedTotal = max($remainingSubtotal - $discountAfter, 0);
        $newCgst = round($discountedTotal * ($bookingCgstRate / 100), 2);
        $newSgst = round($discountedTotal * ($bookingSgstRate / 100), 2);
        $baseFinalAmount = round($discountedTotal + $newCgst + $newSgst, 2);
        $dynamicTotals = $this->bookingFinancialSummaryService->calculateBookingFinalWithExtras($bookingId, $baseFinalAmount);
        $afterAdditionalApprovedTotal = max($currentAdditionalApprovedTotal - $cancelledAdditionalApprovedTotal, 0);
        $newFinalAmount = max(
            $baseFinalAmount
                + $afterAdditionalApprovedTotal
                + (float) ($dynamicTotals['adjustments_total'] ?? 0),
            0
        );
        $newDueAmount = max($newFinalAmount - $paidAmount, 0);

        $refundAmount = max($paidAmount - $newFinalAmount, 0);
        $extraPayableAmount = max($newFinalAmount - $paidAmount, 0);

        if ($refundAmount > 0) {
            $financialOutcome = 'refund_due';
        } elseif ($extraPayableAmount > 0) {
            $financialOutcome = 'customer_needs_to_pay';
        } else {
            $financialOutcome = 'no_financial_change';
        }

        $resultingStatus = ($remainingSubtotal <= 0 && $afterAdditionalApprovedTotal <= 0)
            ? 'cancelled'
            : ($booking['status'] ?? 'pending');

        return [
            'selection' => $selectionSummary,
            'before' => [
                'booking_status' => $booking['status'] ?? null,
                'payment_status' => $booking['payment_status'] ?? null,
                'subtotal_amount' => $currentSubtotal,
                'offer_discount_amount' => $currentOfferDiscount,
                'discount_amount' => $currentDiscount,
                'coupon_discount_amount' => $currentCouponDiscount,
                'coupon_code' => $couponCode,
                'coupon_valid' => !empty($coupon),
                'cgst_amount' => (float) $currentSummary['cgst_amount'],
                'sgst_amount' => (float) $currentSummary['sgst_amount'],
                'final_amount' => $currentFinalAmount,
                'paid_amount' => $paidAmount,
                'due_amount' => $currentDueAmount,
                'additional_approved_total' => $currentAdditionalApprovedTotal,
                'adjustments_total' => (float) $currentSummary['adjustments_total'],
            ],
            'impact' => [
                'cancelled_subtotal' => $cancelledSubtotal,
                'cancelled_offer_discount' => $cancelledOfferDiscount,
                'cancelled_discount' => $cancelledDiscount,
                'cancelled_additional_approved_total' => $cancelledAdditionalApprovedTotal,
                'cancelled_cgst' => $cancelledCgst,
                'cancelled_sgst' => $cancelledSgst,
                'cancelled_total' => $cancelledTotal,
                'discount_removed' => $discountRemoved,
                'removed_coupon_discount' => $discountRemoved,
                'removed_offer_discount' => $cancelledOfferDiscount,
                'coupon_after_valid' => $couponStillValid,
            ],
            'after' => [
                'booking_status' => $resultingStatus,
                'payment_status' => $this->bookingFinancialSummaryService->determinePaymentStatusForAmount($paidAmount, $newFinalAmount),
                'subtotal_amount' => $remainingSubtotal,
                'offer_discount_amount' => $afterOfferDiscount,
                'discount_amount' => $discountAfter,
                'coupon_discount_amount' => $couponDiscountAfter,
                'coupon_code' => $couponCode,
                'coupon_valid' => $couponStillValid,
                'cgst_amount' => $newCgst,
                'sgst_amount' => $newSgst,
                'final_amount' => $newFinalAmount,
                'paid_amount' => $paidAmount,
                'due_amount' => $newDueAmount,
                'additional_approved_total' => $afterAdditionalApprovedTotal,
                'adjustments_total' => (float) ($dynamicTotals['adjustments_total'] ?? 0),
            ],
            'settlement' => [
                'financial_outcome' => $financialOutcome,
                'refund_amount' => $refundAmount,
                'extra_payable_amount' => $extraPayableAmount,
            ],
        ];
    }

    private function applyPreviewRefundBreakdown(array $serviceDetails, float $removedCouponDiscount): array
    {
        $taxableAmount = max((float) ($serviceDetails['subtotal_before_gst'] ?? 0) - $removedCouponDiscount, 0);
        $refundBreakdown = ($this->calculateRefundTaxBreakdown)(
            $taxableAmount,
            (float) ($serviceDetails['cgst_rate'] ?? 0),
            (float) ($serviceDetails['sgst_rate'] ?? 0)
        );

        $serviceDetails['proportional_discount'] = $removedCouponDiscount;
        $serviceDetails['removed_coupon_discount'] = $removedCouponDiscount;
        $serviceDetails['cgst_amount'] = $refundBreakdown['cgst_amount'];
        $serviceDetails['sgst_amount'] = $refundBreakdown['sgst_amount'];
        $serviceDetails['total_gst'] = $refundBreakdown['cgst_amount'] + $refundBreakdown['sgst_amount'];
        $serviceDetails['subtotal_with_gst'] = $refundBreakdown['total_refund_amount'];
        $serviceDetails['final_refund_amount'] = $refundBreakdown['total_refund_amount'];

        return $serviceDetails;
    }
}
