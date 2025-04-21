<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\BookingsModel;
use App\Models\QuotationModel;
use App\Models\TeamModel;
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

    public function overview()
    {
        try {
            $bookingModel = new BookingsModel();
            $teamModel    = new TeamModel();

            // Total projects (all except deleted maybe)
            $totalProjects = $bookingModel->countAll();

            // Specific status counts
            $completedProjects   = $bookingModel->where('status', 'completed')->countAllResults();
            $inProgressProjects  = $bookingModel->where('status', 'in_progress')->countAllResults();

            // Total revenue (sum of paid/valid bookings only if needed)
            $totalRevenue = $bookingModel->selectSum('total_price')->whereIn('status', [
                'confirmed',
                'completed',
                'in_progress'
            ])->first()['total_price'] ?? 0;

            // Total sales (consider only successful ones)
            $totalSales = $bookingModel->whereIn('status', [
                'confirmed',
                'completed'
            ])->countAllResults();

            // Total teams
            $totalTeams = $teamModel->countAll();

            return $this->respond([
                'status' => true,
                'data'   => [
                    'totalSales'         => (int) $totalSales,
                    'totalRevenue'       => (float) $totalRevenue,
                    'totalProjects'      => (int) $totalProjects,
                    'completedProjects'  => (int) $completedProjects,
                    'inProgressProjects' => (int) $inProgressProjects,
                    'totalTeams'         => (int) $totalTeams,
                ]
            ]);
        } catch (Exception $e) {
            return $this->respond([
                'status'  => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function monthlySales()
    {
        try {
            $bookingModel = new BookingsModel();
            $builder = $bookingModel->select("MONTH(created_at) as month, SUM(total_price) as total")
                ->whereIn('status', ['confirmed', 'completed', 'in_progress'])
                ->where('YEAR(created_at)', date('Y'))
                ->groupBy('month')
                ->orderBy('month')
                ->findAll();

            $monthNames = [1 => "Jan", 2 => "Feb", 3 => "Mar", 4 => "Apr", 5 => "May", 6 => "Jun", 7 => "Jul", 8 => "Aug", 9 => "Sep", 10 => "Oct", 11 => "Nov", 12 => "Dec"];
            $labels = [];
            $values = [];

            foreach ($monthNames as $m => $label) {
                $labels[] = $label;
                $monthData = array_filter($builder, fn($row) => (int)$row['month'] === $m);
                $values[] = $monthData ? (float)array_values($monthData)[0]['total'] : 0;
            }

            return $this->respond([
                'status' => true,
                'labels' => $labels,
                'values' => $values,
            ]);
        } catch (\Exception $e) {
            return $this->respond([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function yearlySales()
    {
        try {
            $bookingModel = new BookingsModel();
            $builder = $bookingModel->select("YEAR(created_at) as year, SUM(total_price) as total")
                ->whereIn('status', ['confirmed', 'completed', 'in_progress'])
                ->groupBy('year')
                ->orderBy('year')
                ->findAll();

            $labels = [];
            $values = [];

            foreach ($builder as $row) {
                $labels[] = (string)$row['year'];
                $values[] = (float)$row['total'];
            }

            return $this->respond([
                'status' => true,
                'labels' => $labels,
                'values' => $values,
            ]);
        } catch (\Exception $e) {
            return $this->respond([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
