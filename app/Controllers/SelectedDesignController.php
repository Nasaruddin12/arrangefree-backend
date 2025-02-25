<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\SelectedDesignModel;

class SelectedDesignController extends ResourceController
{
    public function saveSelectedDesign()
    {
        try {
            $userId = $this->request->getVar('user_id');
            $imagePath = $this->request->getVar('image_path'); // JSON string

            if (empty($userId) || empty($imagePath)) {
                return $this->fail('User ID and image path are required', 400);
            }

            $selectedDesignModel = new SelectedDesignModel();

            // Check if the user_id already exists
            $existingData = $selectedDesignModel->where('user_id', $userId)->first();

            if ($existingData) {
                // Update image_path for existing user_id
                $selectedDesignModel->update($existingData['id'], ['image_path' => $imagePath]);
                return $this->respond(['message' => 'Design updated successfully'], 200);
            } else {
                // Insert new record
                $selectedDesignModel->insert(['user_id' => $userId, 'image_path' => $imagePath]);
                return $this->respond(['message' => 'Design saved successfully'], 200);
            }
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }
    public function getSelectedDesign($userId)
    {
        try {
            $selectedDesignModel = new SelectedDesignModel();
            $designData = $selectedDesignModel->where('user_id', $userId)->first();

            if ($designData) {
                return $this->respond([
                    'status' => 200,
                    'data' => $designData
                ], 200);
            } else {
                return $this->failNotFound('No design found for this user.');
            }
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }
}
