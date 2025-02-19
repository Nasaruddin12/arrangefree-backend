<?php

namespace App\Controllers;

use App\Models\WorkTypeModel;
use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use Exception;

class WorkTypeController extends BaseController
{
    use ResponseTrait;
    protected $workTypeModel;

    public function __construct()
    {
        $this->workTypeModel = new WorkTypeModel();
    }

    // ✅ Create Work Type
    public function create()
    {
        try {
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


            if ($this->workTypeModel->save($data)) {
                return $this->respond([
                    'status' => 201,
                    'message' => 'Work Type Created Successfully',
                    'data' => $data
                ], 201);
            }

            return $this->respond(['status' => 400, 'message' => 'Failed to create Work Type'], 400);
        } catch (Exception $e) {
            return $this->respond(['status' => 500, 'message' => 'An error occurred while creating Work Type', 'error' => $e->getMessage()], 500);
        }
    }

    // ✅ Upload Image (Returns only path)
    public function uploadImage()
    {
        try {
            $file = $this->request->getFile('image');
            if (!$file || !$file->isValid()) {
                return $this->respond(['status' => 400, 'message' => 'Invalid image file'], 400);
            }

            // Generate a random name and move the image
            $imagePath = $file->getRandomName();
            $file->move(WRITEPATH . 'uploads/services', $imagePath);

            return $this->respond([
                'status' => 200,
                'message' => 'Image uploaded successfully',
                'image_path' => $imagePath
            ], 200);
        } catch (Exception $e) {
            return $this->respond(['status' => 500, 'message' => 'Failed to upload image', 'error' => $e->getMessage()], 500);
        }
    }

    // ✅ Delete Image by Path
    public function deleteImage()
    {
        try {
            $imagePath = $this->request->getVar('image_path');
            if (!$imagePath) {
                return $this->respond(['status' => 400, 'message' => 'Image path is required'], 400);
            }

            if (file_exists($imagePath)) {
                unlink($imagePath);
                return $this->respond(['status' => 200, 'message' => 'Image deleted successfully'], 200);
            } else {
                return $this->respond(['status' => 404, 'message' => 'Image not found'], 404);
            }
        } catch (Exception $e) {
            return $this->respond(['status' => 500, 'message' => 'Failed to delete image', 'error' => $e->getMessage()], 500);
        }
    }

    // ✅ Read all Work Types
    public function index()
    {
        try {
            $workTypes = $this->workTypeModel
                ->select('work_types.*, services.name as service_name') // Selecting required columns
                ->join('services', 'services.id = work_types.service_id', 'left') // Joining service table
                ->findAll();

            if (empty($workTypes)) {
                return $this->failNotFound('No Work Types found.');
            }

            return $this->respond([
                'status' => 200,
                'message' => 'Data retrieved successfully',
                'data' => $workTypes
            ], 200);
        } catch (Exception $e) {
            return $this->respond([
                'status' => 500,
                'message' => 'Failed to retrieve Work Types',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ✅ Update Work Type
    public function update($id)
    {
        try {
            $workType = $this->workTypeModel->find($id);
            if (!$workType) {
                return $this->failNotFound('Work Type not found.');
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

            if ($this->workTypeModel->update($id, $data)) {
                return $this->respond([
                    'status' => 200,
                    'message' => 'Work Type Updated Successfully',
                    'data' => $data
                ], 200);
            }

            return $this->respond(['status' => 400, 'message' => 'Failed to update Work Type'], 400);
        } catch (Exception $e) {
            return $this->respond(['status' => 500, 'message' => 'Failed to update Work Type', 'error' => $e->getMessage()], 500);
        }
    }

    // ✅ Delete Work Type
    public function delete($id)
    {
        try {
            $workType = $this->workTypeModel->find($id);
            if (!$workType) {
                return $this->failNotFound('Work Type not found.');
            }

            if (!empty($workType['image'])) {
                $this->deleteImageByPath($workType['image']);
            }

            if ($this->workTypeModel->delete($id)) {
                return $this->respond([
                    'status' => 200,
                    'message' => 'Work Type Deleted Successfully'
                ], 200);
            }

            return $this->respond(['status' => 400, 'message' => 'Failed to delete Work Type'], 400);
        } catch (Exception $e) {
            return $this->respond(['status' => 500, 'message' => 'Failed to delete Work Type', 'error' => $e->getMessage()], 500);
        }
    }

    // ✅ Private function to delete image by path
    private function deleteImageByPath($imagePath)
    {
        try {
            $fullPath = WRITEPATH . 'uploads/' . $imagePath;
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        } catch (Exception $e) {
            // Handle any issues that may arise while deleting the image file
        }
    }
    public function changeStatus($id)
    {
        $status = $this->request->getVar('status'); // Get status from request

        if (!in_array($status, ['0', '1'])) {
            return $this->failValidationErrors('Invalid status value. Use 1 (active) or 0 (inactive).');
        }

        if (!$this->workTypeModel->find($id)) {
            return $this->failNotFound('Work Type not found.');
        }

        $this->workTypeModel->update($id, ['status' => $status]);

        return $this->respond([
            'status' => 200,
            'message' => 'Status updated successfully',
            'new_status' => (int)$status
        ], 200);
    }
}
