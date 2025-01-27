<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\QuotationModel;
use CodeIgniter\API\ResponseTrait;
use Exception;

class DashboardController extends BaseController
{
    use ResponseTrait;

    public function getDashboardData()
    {
        try {
            $quotationModel = new QuotationModel();

            // Get today's date and yesterday's date
            $today = date('Y-m-d');
            $yesterday = date('Y-m-d', strtotime('-1 day'));

            // Today's quotation count
            $todayQuotations = $quotationModel
                ->where('DATE(created_at)', $today)
                ->countAllResults();

            // Yesterday's quotation count
            $yesterdayQuotations = $quotationModel
                ->where('DATE(created_at)', $yesterday)
                ->countAllResults();

            // Calculate percentage comparison with yesterday
            $percentageComparison = $yesterdayQuotations > 0
                ? round((($todayQuotations - $yesterdayQuotations) / $yesterdayQuotations) * 100, 2)
                : ($todayQuotations > 0 ? 100 : 0);

            // Total quotations
            $totalQuotations = $quotationModel->countAllResults();

            // Status breakdown
            $statusBreakdown = $quotationModel->select('status, COUNT(*) as count')
                ->groupBy('status')
                ->findAll();

            // Today's sales (assuming "sale" is a status in your table)
            $todaySales = $quotationModel
                ->where('DATE(created_at)', $today)
                ->where('status', 'sale')
                ->countAllResults();

            // Today's canceled quotations (assuming "cancelled" is a status in your table)
            $todayCancelled = $quotationModel
                ->where('DATE(created_at)', $today)
                ->where('status', 'cancelled')
                ->countAllResults();

            // Prepare response data
            $response = [
                'today_quotations' => $todayQuotations,
                'percentage_comparison' => $percentageComparison,
                'total_quotations' => $totalQuotations,
                'status_breakdown' => $statusBreakdown,
                'today_sales' => $todaySales,
                'today_cancelled' => $todayCancelled,
            ];

            return $this->respond($response);
        } catch (Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }
}
