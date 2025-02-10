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
        $model = new FreepikApiHistoryModel();

        // Get query parameters
        $page = $this->request->getVar('page') ?? 1;
        $limit = $this->request->getVar('limit') ?? 10;
        $search = $this->request->getVar('search');
        $startDate = $this->request->getVar('start_date');
        $endDate = $this->request->getVar('end_date');

        // Initialize query
        $query = $model->select('freepik_api_history.*, af_customers.name')
            ->join('af_customers', 'af_customers.id = freepik_api_history.user_id')
            ->orderBy('freepik_api_history.created_at', 'DESC');

        // Apply search filter
        if (!empty($search)) {
            $query->groupStart()
                ->like('freepik_api_history.prompt', $search)
                ->orLike('af_customers.name', $search)
                ->groupEnd();
        }

        // Apply date range filter
        if (!empty($startDate) && !empty($endDate)) {
            $query->where('freepik_api_history.created_at >=', $startDate)
                ->where('freepik_api_history.created_at <=', $endDate);
        }

        // Get total records count for pagination
        $totalRecords = $query->countAllResults(false);

        // Apply pagination
        $query->limit($limit, ($page - 1) * $limit);
        $data = $query->findAll();

        return $this->respond([
            'status' => 200,
            'data' => $data,
            'pagination' => [
                'total' => $totalRecords,
                'page' => (int) $page,
                'limit' => (int) $limit,
                'total_pages' => ceil($totalRecords / $limit)
            ]
        ], 200);
    }

    public function getByUser($user_id)
    {
        $model = new FreepikApiHistoryModel();
        $data = $model->where('user_id', $user_id)->findAll();

        return $this->respond(['status' => 200, 'data' => $data], 200);
    }
}
