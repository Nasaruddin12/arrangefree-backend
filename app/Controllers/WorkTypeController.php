<?php

namespace App\Controllers;

use App\Models\WorkTypeModel;
use App\Controllers\BaseController;

class WorkTypeController extends BaseController
{
    protected $workTypeModel;

    public function __construct()
    {
        $this->workTypeModel = new WorkTypeModel();
    }

    // Create Work Type
    public function create()
    {
        $data = [
            'name' => $this->request->getVar('name'),
            'service_id' => $this->request->getVar('service_id'),
            'rate' => $this->request->getVar('rate'),
            'rate_type' => $this->request->getVar('rate_type'),
            'description' => $this->request->getVar('description'),
            'materials' => $this->request->getVar('materials'),
            'features' => $this->request->getVar('features'),
            'care_instructions' => $this->request->getVar('care_instructions'),
            'warranty_details' => $this->request->getVar('warranty_details'),
            'quality_promise' => $this->request->getVar('quality_promise'),
            'status' => $this->request->getVar('status'),
            'image' => $this->request->getVar('image'), // Image path passed from uploadImage()
        ];

        $this->workTypeModel->save($data);

        return $this->response->setJSON(['message' => 'Work Type Created Successfully']);
    }

    // Upload Image (Returns only path)
    public function uploadImage()
    {
        $file = $this->request->getFile('image');

        if (!$file || !$file->isValid()) {
            return $this->response->setJSON(['error' => 'Invalid image file'])->setStatusCode(400);
        }

        // Generate a random name and move the image
        $imagePath = $file->getRandomName();
        $file->move(WRITEPATH . 'uploads/', $imagePath);

        return $this->response->setJSON(['image_path' => $imagePath]);
    }

    // Delete Image by Path
    public function deleteImage()
    {
        $imagePath = $this->request->getVar('image_path');

        if (!$imagePath) {
            return $this->response->setJSON(['error' => 'Image path is required'])->setStatusCode(400);
        }

        $fullPath = WRITEPATH . 'uploads/' . $imagePath;

        if (file_exists($fullPath)) {
            unlink($fullPath);
            return $this->response->setJSON(['message' => 'Image deleted successfully']);
        } else {
            return $this->response->setJSON(['error' => 'Image not found'])->setStatusCode(404);
        }
    }

    // Read all Work Types
    public function index()
    {
        $workTypes = $this->workTypeModel->findAll();
        return $this->response->setJSON($workTypes);
    }

    // Update Work Type
    public function update($id)
    {
        $workType = $this->workTypeModel->find($id);
        if (!$workType) {
            return $this->response->setJSON(['error' => 'Work Type not found'])->setStatusCode(404);
        }

        $data = [
            'name' => $this->request->getVar('name'),
            'service_id' => $this->request->getVar('service_id'),
            'rate' => $this->request->getVar('rate'),
            'rate_type' => $this->request->getVar('rate_type'),
            'description' => $this->request->getVar('description'),
            'materials' => $this->request->getVar('materials'),
            'features' => $this->request->getVar('features'),
            'care_instructions' => $this->request->getVar('care_instructions'),
            'warranty_details' => $this->request->getVar('warranty_details'),
            'quality_promise' => $this->request->getVar('quality_promise'),
            'status' => $this->request->getVar('status'),
            'image' => $this->request->getVar('image') ?? $workType['image'], // Keep old image if not provided
        ];

        $this->workTypeModel->update($id, $data);

        return $this->response->setJSON(['message' => 'Work Type Updated Successfully']);
    }

    // Delete Work Type
    public function delete($id)
    {
        $workType = $this->workTypeModel->find($id);
        if (!$workType) {
            return $this->response->setJSON(['error' => 'Work Type not found'])->setStatusCode(404);
        }

        if (!empty($workType['image'])) {
            $this->deleteImageByPath($workType['image']);
        }

        $this->workTypeModel->delete($id);

        return $this->response->setJSON(['message' => 'Work Type Deleted Successfully']);
    }

    // Private function to delete image by path
    private function deleteImageByPath($imagePath)
    {
        $fullPath = WRITEPATH . 'uploads/' . $imagePath;
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }
}
