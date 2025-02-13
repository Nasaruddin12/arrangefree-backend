<?php

namespace App\Controllers;

use App\Models\FreepikApiHistoryModel;
use CodeIgniter\RESTful\ResourceController;
use Exception;

class FreepikApiHistoryController extends ResourceController
{
    public function store()
    {
        try {
            $user_id = $this->request->getVar('user_id');
            $prompt  = $this->request->getVar('prompt');
            $images  = $this->request->getFiles(); // Multiple images
            $uploadDirectory = 'uploads/freepik-api-history/';
            $imagePaths = [];

            if (empty($user_id) || empty($prompt)) {
                return $this->respond(['status' => 400, 'message' => 'User ID and prompt are required'], 400);
            }

            if (!isset($images['images'])) {
                return $this->respond(['status' => 400, 'message' => 'No images uploaded'], 400);
            }

            foreach ($images['images'] as $file) {
                if ($file->isValid() && !$file->hasMoved()) {
                    $fileName = $file->getRandomName();
                    if ($file->move($uploadDirectory, $fileName)) {
                        $imagePaths[] = $uploadDirectory . $fileName;
                    }
                }
            }

            $model = new FreepikApiHistoryModel();
            $model->insert([
                'user_id'    => $user_id,
                'prompt'     => $prompt,
                'images'     => json_encode($imagePaths),
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            return $this->respond([
                'status'  => 201,
                'message' => 'Data stored successfully',
                'data'    => ['images' => $imagePaths]
            ], 201);
        } catch (Exception $e) {
            return $this->respond(['status' => 500, 'message' => 'Failed to store data', 'error' => $e->getMessage()], 500);
        }
    }

    public function getAll()
    {
        try {
            $model = new FreepikApiHistoryModel();

            // Get query parameters
            $page = (int) ($this->request->getVar('page') ?? 1);
            $perPage = (int) ($this->request->getVar('perPage') ?? 10);
            $search = $this->request->getVar('search');
            $startDate = $this->request->getVar('start_date');
            $endDate = $this->request->getVar('end_date');
            $offset = max(0, ($page - 1) * $perPage);

            // Base query: Get unique customers who have used the Freepik API, sorted by most recent usage
            $query = $model->select('af_customers.id, af_customers.name, MAX(freepik_api_history.created_at) AS created_at, COUNT(freepik_api_history.id) AS usage_count')
                ->join('af_customers', 'af_customers.id = freepik_api_history.user_id', 'inner') // Inner join ensures only customers who used the API
                ->groupBy('af_customers.id') // Get unique customers
                ->orderBy('created_at', 'DESC'); // Order by most recent usage

            // Apply search filter (search by customer name)
            if (!empty($search)) {
                $query->like('af_customers.name', $search);
            }

            // Apply date range filter based on Freepik API usage date
            if (!empty($startDate) && !empty($endDate)) {
                $query->where('freepik_api_history.created_at >=', date('Y-m-d', strtotime($startDate)))
                    ->where('freepik_api_history.created_at <=', date('Y-m-d', strtotime($endDate)));
            }

            // Clone query for total unique customers count
            $totalRecordsQuery = clone $query;
            $totalRecords = count($totalRecordsQuery->findAll()); // Get total distinct customers

            // Apply pagination (after counting total records)
            $data = $query->limit($perPage, $offset)->get()->getResultArray();

            // Check if data exists
            if (empty($data)) {
                return $this->failNotFound('No customers found who used the Freepik API.');
            }

            return $this->respond([
                'status' => 200,
                'message' => 'Data retrieved successfully',
                'data' => $data,
                'pagination' => [
                    'currentPage' => $page,
                    'perPage' => $perPage,
                    'totalPages' => ceil($totalRecords / $perPage),
                    'totalRecords' => $totalRecords
                ]
            ], 200);
        } catch (\Exception $e) {
            return $this->failServerError('An unexpected error occurred: ' . $e->getMessage());
        }
    }



    public function getByUser($user_id)
    {
        $model = new FreepikApiHistoryModel();
        $data = $model->where('user_id', $user_id)
            ->orderBy('created_at', 'DESC')
            ->findAll();

        return $this->respond(['status' => 200, 'data' => $data], 200);
    }
    public function checkUserLimit($user_id)
    {
        $model = new FreepikApiHistoryModel();
        $requestCount = $model->where('user_id', $user_id)->countAllResults();

        if ($requestCount <= 250) {
            return $this->respond(['status' => 200, 'allowed' => true, 'count' => $requestCount,], 200);
        } else {
            return $this->respond([
                'status' => 403,
                'allowed' => false,
                'message' => 'You have exceeded the limit. Reach out to Arrange Free.'
            ], 403);
        }
    }
}
