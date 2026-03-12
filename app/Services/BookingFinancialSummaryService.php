<?php

namespace App\Services;

use App\Models\BookingAdditionalServicesModel;
use App\Models\BookingAdjustmentModel;
use App\Models\BookingPaymentsModel;

class BookingFinancialSummaryService
{
    private BookingPaymentsModel $bookingPaymentsModel;
    private BookingAdditionalServicesModel $bookingAdditionalServicesModel;
    private BookingAdjustmentModel $bookingAdjustmentModel;

    /** @var callable */
    private $getActiveBookingServiceOfferDiscount;
    /** @var callable */
    private $getBookingDiscountAmount;

    public function __construct(
        BookingPaymentsModel $bookingPaymentsModel,
        BookingAdditionalServicesModel $bookingAdditionalServicesModel,
        BookingAdjustmentModel $bookingAdjustmentModel,
        callable $getActiveBookingServiceOfferDiscount,
        callable $getBookingDiscountAmount
    ) {
        $this->bookingPaymentsModel = $bookingPaymentsModel;
        $this->bookingAdditionalServicesModel = $bookingAdditionalServicesModel;
        $this->bookingAdjustmentModel = $bookingAdjustmentModel;
        $this->getActiveBookingServiceOfferDiscount = $getActiveBookingServiceOfferDiscount;
        $this->getBookingDiscountAmount = $getBookingDiscountAmount;
    }

    public function summarize(array $booking, float $paidNow = 0.0): array
    {
        $bookingId = (int) ($booking['id'] ?? 0);
        $totals = $this->calculateBookingFinalWithExtras($bookingId, (float) ($booking['final_amount'] ?? 0));
        $paidSoFar = $this->getTotalPaidAmount($bookingId);
        $newPaid = min($paidSoFar + $paidNow, (float) ($totals['final_amount'] ?? 0));
        $finalAmount = (float) ($totals['final_amount'] ?? 0);

        return [
            'paid_amount' => $newPaid,
            'amount_due' => max($finalAmount - $newPaid, 0),
            'due_amount' => max($finalAmount - $newPaid, 0),
            'payment_status' => $this->determinePaymentStatusForAmount($newPaid, $finalAmount),
            'calculated_final_amount' => $finalAmount,
            'final_amount' => $finalAmount,
            'additional_approved_total' => (float) ($totals['additional_approved_total'] ?? 0),
            'adjustments_total' => (float) ($totals['adjustments_total'] ?? 0),
            'original_final_amount' => (float) ($booking['final_amount'] ?? 0),
            'offer_discount_amount' => ($this->getActiveBookingServiceOfferDiscount)($bookingId),
            'coupon_discount_amount' => ($this->getBookingDiscountAmount)($booking),
            'discount_amount' => ($this->getBookingDiscountAmount)($booking),
            'subtotal_amount' => (float) ($booking['subtotal_amount'] ?? 0),
            'cgst_amount' => (float) ($booking['cgst'] ?? 0),
            'sgst_amount' => (float) ($booking['sgst'] ?? 0),
        ];
    }

    public function calculateBookingFinalWithExtras(int $bookingId, float $baseFinalAmount): array
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

        return [
            'final_amount' => max($baseFinalAmount + $additionalApprovedTotal + $adjustmentsTotal, 0),
            'additional_approved_total' => $additionalApprovedTotal,
            'adjustments_total' => $adjustmentsTotal,
        ];
    }

    public function determinePaymentStatusForAmount(float $paidSoFar, float $finalAmount): string
    {
        if ($paidSoFar <= 0) {
            return 'pending';
        }

        if ($paidSoFar < $finalAmount) {
            return 'partial';
        }

        return 'completed';
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

}
