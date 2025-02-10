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

            // Base query
            $query = $model->select('freepik_api_history.*, af_customers.name AS customer_name') // Use alias to avoid conflicts
                ->join('af_customers', 'af_customers.id = freepik_api_history.user_id', 'left')
                ->orderBy('freepik_api_history.created_at', 'DESC'); // Ensure latest records are at the top

            // Apply search filter
            if (!empty($search)) {
                $query->groupStart()
                    ->like('freepik_api_history.prompt', $search)
                    ->orLike('af_customers.name', $search)
                    ->groupEnd();
            }

            // Apply date range filter
            if (!empty($startDate) && !empty($endDate)) {
                $query->where('freepik_api_history.created_at >=', date('Y-m-d', strtotime($startDate)))
                    ->where('freepik_api_history.created_at <=', date('Y-m-d', strtotime($endDate)));
            }

            // Clone query for total records count
            $totalRecordsQuery = clone $query;
            $totalRecords = $totalRecordsQuery->countAllResults(false); // Get total records before pagination

            // Apply pagination (after counting total records)
            $data = $query->limit($perPage, $offset)->get()->getResultArray();

            // Check if data exists
            if (empty($data)) {
                return $this->failNotFound('No records found.');
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
