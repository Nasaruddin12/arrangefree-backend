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
        $data = $model->findAll();

        return $this->respond(['status' => 200, 'data' => $data], 200);
    }

    public function getByUser($user_id)
    {
        $model = new FreepikApiHistoryModel();
        $data = $model->where('user_id', $user_id)->findAll();

        return $this->respond(['status' => 200, 'data' => $data], 200);
    }
}
