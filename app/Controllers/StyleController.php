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

            if (empty($styles)) {
                return $this->failNotFound('No styles found.');
            }

            return $this->respond([
                'status'  => 200,
                'message' => 'Styles retrieved successfully',
                'data'    => $styles
            ], 200);
        } catch (\Exception $e) {
            return $this->failServerError('Failed to retrieve styles: ' . $e->getMessage());
        }
    }

    public function getStylesByCategory($categoryId = null)
    {
        try {
            if ($categoryId) {
                $styles = $this->model->where('styles_category_id', $categoryId)->findAll();
            } else {
                $styles = $this->model->findAll();
            }

            if (empty($styles)) {
                return $this->failNotFound('No styles found.');
            }

            return $this->respond([
                'status'  => 200,
                'message' => $categoryId ? 'Styles for category retrieved successfully' : 'All styles retrieved successfully',
                'data'    => $styles
            ], 200);
        } catch (\Exception $e) {
            return $this->failServerError('Failed to retrieve styles: ' . $e->getMessage());
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

            return $this->respond([
                'status'  => 200,
                'message' => 'Style retrieved successfully',
                'data'    => $style
            ], 200);
        } catch (\Exception $e) {
            return $this->failServerError('Error fetching style: ' . $e->getMessage());
        }
    }

    // Create a new style
    public function create()
    {
        try {
            $request = service('request');
            $data = $request->getVar();

            // Validation rules
            $rules = [
                'name'            => 'required|string|max_length[255]',
                'styles_category_id' => 'required|integer',
                'image'           => 'if_exist|is_image[image]|max_size[image,2048]',
            ];

            if (!$this->validate($rules)) {
                return $this->failValidationErrors($this->validator->getErrors());
            }

            // Handle image upload if present
            $imagePath = null;
            $imageFile = $request->getFile('image');

            if ($imageFile && $imageFile->isValid() && !$imageFile->hasMoved()) {
                $newName  = $imageFile->getRandomName();
                $savePath = 'public/uploads/styles/';
                $imageFile->move(FCPATH . $savePath, $newName);
                $imagePath = $savePath . $newName;
            }

            $this->model->insert([
                'name'            => $request->getVar('name'),
                'styles_category_id' => $request->getVar('styles_category_id'),
                'image'           => $imagePath,
                'status'          => 'active',
            ]);

            return $this->respondCreated([
                'status'  => 201,
                'message' => 'Style created successfully',
            ]);
        } catch (\Exception $e) {
            return $this->failServerError('Error creating style: ' . $e->getMessage());
        }
    }


    // Update an existing style
    public function update($id = null)
    {
        try {
            $request = service('request');
            $data = $request->getVar();

            if (!$id || !$this->model->find($id)) {
                return $this->failNotFound('Style not found.');
            }

            // Validation rules
            $rules = [
                'name'            => 'required|string|max_length[255]',
                'styles_category_id' => 'required|integer',
                'image'           => 'permit_empty|string', // allow direct path
            ];

            // Run validation
            if (!$this->validate($rules)) {
                return $this->failValidationErrors($this->validator->getErrors());
            }

            // Initialize imagePath
            $imagePath = null;

            // Case 1: Check for uploaded file
            $imageFile = $request->getFile('image');
            if ($imageFile && $imageFile->isValid() && !$imageFile->hasMoved()) {
                $newName = $imageFile->getRandomName();
                $savePath = 'public/uploads/styles/';
                $imageFile->move(FCPATH . $savePath, $newName);
                $imagePath = $savePath . $newName;
            }

            // Case 2: Check if image path is passed directly as string
            $directPath = $request->getVar('image');
            if (!$imagePath && $directPath && is_string($directPath)) {
                $imagePath = $directPath;
            }

            // Prepare update data
            $updateData = [
                'name'            => $data['name'],
                'styles_category_id' => $data['styles_category_id'],
            ];

            if ($imagePath) {
                $updateData['image'] = $imagePath;
            }

            $this->model->update($id, $updateData);

            return $this->respond([
                'status'  => 200,
                'message' => 'Style updated successfully'
            ]);
        } catch (\Exception $e) {
            return $this->failServerError('Error updating style: ' . $e->getMessage());
        }
    }


    // Delete a style
    public function delete($id = null)
    {
        try {
            if (!$this->model->find($id)) {
                return $this->failNotFound('Style not found.');
            }

            $this->model->delete($id);
            return $this->respondDeleted([
                'status'  => 200,
                'message' => 'Style deleted successfully'
            ]);
        } catch (\Exception $e) {
            return $this->failServerError('Error deleting style: ' . $e->getMessage());
        }
    }
}
