<?php

namespace App\Controllers;

use App\Models\StyleModel;
use CodeIgniter\RESTful\ResourceController;

class StyleController extends ResourceController
{
    protected $modelName = 'App\Models\StyleModel';
    protected $format    = 'json';

    // Fetch all styles
    public function index()
    {
        try {
            $styles = $this->model->findAll();
            return $this->respond($styles);
        } catch (\Exception $e) {
            return $this->failServerError("Error fetching styles: " . $e->getMessage());
        }
    }

    // Fetch a single style by ID
    public function show($id = null)
    {
        try {
            $style = $this->model->find($id);
            if (!$style) {
                return $this->failNotFound("Style not found.");
            }
            return $this->respond($style);
        } catch (\Exception $e) {
            return $this->failServerError("Error fetching style: " . $e->getMessage());
        }
    }

    // Create a new style
    public function create()
    {
        try {
            $data = $this->request->getPost();

            if (!$this->validate([
                'name' => 'required|string|max_length[255]',
            ])) {
                return $this->failValidationErrors($this->validator->getErrors());
            }

            $this->model->insert($data);
            return $this->respondCreated(['message' => 'Style created successfully']);
        } catch (\Exception $e) {
            return $this->failServerError("Error creating style: " . $e->getMessage());
        }
    }

    // Update an existing style
    public function update($id = null)
    {
        try {
            $data = $this->request->getRawInput();

            if (!$this->model->find($id)) {
                return $this->failNotFound("Style not found.");
            }

            $this->model->update($id, $data);
            return $this->respond(['message' => 'Style updated successfully']);
        } catch (\Exception $e) {
            return $this->failServerError("Error updating style: " . $e->getMessage());
        }
    }

    // Delete a style
    public function delete($id = null)
    {
        try {
            if (!$this->model->find($id)) {
                return $this->failNotFound("Style not found.");
            }

            $this->model->delete($id);
            return $this->respondDeleted(['message' => 'Style deleted successfully']);
        } catch (\Exception $e) {
            return $this->failServerError("Error deleting style: " . $e->getMessage());
        }
    }
}
