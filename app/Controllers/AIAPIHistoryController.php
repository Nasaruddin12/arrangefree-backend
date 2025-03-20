<?php

namespace App\Controllers;

use App\Models\AIAPIHistoryModel;
use CodeIgniter\RESTful\ResourceController;
use Config\Database;

class AIAPIHistoryController extends ResourceController
{
    public function getHistoryByUser($userId)
    {
        try {
            // Validate user ID
            if (!is_numeric($userId) || $userId <= 0) {
                return $this->failValidationErrors('Invalid user ID provided.');
            }

            $aiApiHistoryModel = new AIAPIHistoryModel();
            $historyData = $aiApiHistoryModel->where('user_id', $userId)->findAll();

            if (!empty($historyData)) {
                return $this->respond([
                    'status'  => 200,
                    'message' => 'AI API history retrieved successfully.',
                    'data'    => $historyData
                ], 200);
            } else {
                return $this->respond([
                    'status'  => 404,
                    'message' => 'No AI API history found for this user.',
                    'data'    => []
                ], 404);
            }
        } catch (\Throwable $e) {
            return $this->failServerError('An error occurred while fetching AI API history: ' . $e->getMessage());
        }
    }

    public function store()
    {
        try {
            $rules = [
                'user_id'     => 'required|integer',
                'api_name'    => 'required|string|max_length[255]',
                'request_data' => 'permit_empty|string',
                'response_data' => 'permit_empty|string',
                'status_code' => 'required|integer',
            ];

            $input = $this->request->getJSON(true);

            if (!$this->validate($rules)) {
                return $this->failValidationErrors($this->validator->getErrors());
            }

            $aiApiHistoryModel = new AIAPIHistoryModel();
            $aiApiHistoryModel->insert($input);

            return $this->respondCreated([
                'status'  => 201,
                'message' => 'AI API history recorded successfully.',
                'data'    => $input
            ]);
        } catch (\Throwable $e) {
            return $this->failServerError('An error occurred while saving AI API history: ' . $e->getMessage());
        }
    }
    public function getAll()
    {
        try {
            $db = Database::connect();
            $model = new AIAPIHistoryModel();

            // Get query parameters
            $page = (int) ($this->request->getVar('page') ?? 1);
            $perPage = (int) ($this->request->getVar('perPage') ?? 10);
            $search = $this->request->getVar('search');
            $startDate = $this->request->getVar('start_date');
            $endDate = $this->request->getVar('end_date');
            $offset = max(0, ($page - 1) * $perPage);

            // Subquery to get latest usage per user
            $subQuery = $db->table('ai_api_history')
                ->select('user_id, MAX(created_at) as latest_usage')
                ->groupBy('user_id')
                ->getCompiledSelect();

            // Main query: Get customers with their latest API usage
            $query = $db->table('ai_api_history')
                ->select('af_customers.id AS user_id, af_customers.name, recent_usage.latest_usage AS created_at, COUNT(ai_api_history.id) AS usage_count')
                ->join("($subQuery) AS recent_usage", 'recent_usage.user_id = ai_api_history.user_id', 'inner')
                ->join('af_customers', 'af_customers.id = ai_api_history.user_id', 'inner')
                ->groupBy('ai_api_history.user_id')
                ->orderBy('recent_usage.latest_usage', 'DESC');

            // Apply search filter
            if (!empty($search)) {
                $query->like('af_customers.name', $search);
            }

            // Apply date range filter
            if (!empty($startDate) && !empty($endDate)) {
                $query->where('ai_api_history.created_at >=', date('Y-m-d', strtotime($startDate)))
                    ->where('ai_api_history.created_at <=', date('Y-m-d', strtotime($endDate)));
            }

            // Get total unique customer count
            $totalRecordsQuery = clone $query;
            $totalRecords = count($totalRecordsQuery->get()->getResultArray());

            // Apply pagination
            $data = $query->limit($perPage, $offset)->get()->getResultArray();

            // Check if data exists
            if (empty($data)) {
                return $this->failNotFound('No customers found who used the AI API.');
            }

            return $this->respond([
                "status" => 200,
                "message" => "Data retrieved successfully",
                "data" => $data,
                "pagination" => [
                    "currentPage" => $page,
                    "perPage" => $perPage,
                    "totalPages" => ceil($totalRecords / $perPage),
                    "totalRecords" => $totalRecords
                ]
            ], 200);
        } catch (\Exception $e) {
            return $this->failServerError('An unexpected error occurred: ' . $e->getMessage());
        }
    }
}
