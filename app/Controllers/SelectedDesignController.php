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
            $designText = $this->request->getVar('design_text'); // New text field

            if (empty($userId) || empty($imagePath)) {
                return $this->fail('User ID and image path are required', 400);
            }

            $selectedDesignModel = new SelectedDesignModel();

            // Fetch the last record for this user
            $existingData = $selectedDesignModel->where('user_id', $userId)
                ->orderBy('id', 'DESC')
                ->first();

            if ($existingData) {
                // If the image_path is the same, update the design_text
                if ($existingData['image_path'] === $imagePath) {
                    $selectedDesignModel->update($existingData['id'], ['text' => $designText]);
                    return $this->respond([
                        'status' => 200,
                        'message' => 'Design text updated successfully'
                    ], 200);
                }
            }

            // Insert a new record if image_path is different
            $selectedDesignModel->insert([
                'user_id' => $userId,
                'image_path' => $imagePath,
                'text' => $designText
            ]);

            return $this->respond([
                'status' => 200,
                'message' => 'New design saved successfully'
            ], 200);
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
