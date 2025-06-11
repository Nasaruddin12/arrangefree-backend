<?php

namespace App\Controllers;

use App\Models\StylesCategoryModel;
use CodeIgniter\RESTful\ResourceController;

class StylesCategoryController extends ResourceController
{
    protected $model;

    public function __construct()
    {
        $this->model = new StylesCategoryModel();
        helper(['form', 'url']);
    }

    public function index()
    {
        try {
            $categories = $this->model->findAll();

            if (empty($categories)) {
                return $this->failNotFound('No style categories found.');
            }

            return $this->respond([
                'status'  => 200,
                'message' => 'Style categories retrieved successfully',
                'data'    => $categories
            ], 200);
        } catch (\Exception $e) {
            return $this->failServerError('Failed to retrieve categories: ' . $e->getMessage());
        }
    }

    public function create()
    {
        try {
            $rules = [
                'name'  => 'required',
                'image' => 'if_exist|is_image[image]|max_size[image,2048]',
            ];

            if (!$this->validate($rules)) {
                return $this->failValidationErrors($this->validator->getErrors());
            }

            $imagePath = null;
            $imageFile = $this->request->getFile('image');

            if ($imageFile && $imageFile->isValid() && !$imageFile->hasMoved()) {
                $newName  = $imageFile->getRandomName();
                $savePath = 'public/uploads/styles-category/';
                $imageFile->move(FCPATH . $savePath, $newName);
                $imagePath = $savePath . $newName;
            }

            $this->model->save([
                'name'   => $this->request->getVar('name'),
                'image'  => $imagePath,
                'status' => 'active',
            ]);

            return $this->respondCreated([
                'status'  => 201,
                'message' => 'Style category created successfully',
            ]);
        } catch (\Exception $e) {
            return $this->failServerError('Error creating style category: ' . $e->getMessage());
        }
    }

    public function show($id = null)
    {
        try {
            $data = $this->model->find($id);
            if (!$data) {
                return $this->failNotFound('Style category not found.');
            }

            return $this->respond([
                'status'  => 200,
                'message' => 'Style category retrieved successfully',
                'data'    => $data
            ], 200);
        } catch (\Exception $e) {
            return $this->failServerError('Error retrieving style category: ' . $e->getMessage());
        }
    }

    public function update($id = null)
    {
        try {
            if (!$this->model->find($id)) {
                return $this->failNotFound('Style category not found.');
            }

            $update = [];
            $name   = $this->request->getVar('name');
            $status = $this->request->getVar('status');

            if (!empty($name)) {
                $update['name'] = $name;
            }

            if (!empty($status)) {
                $update['status'] = $status;
            }

            // Handle image upload if present
            $imageFile = $this->request->getFile('image');
            if ($imageFile && $imageFile->isValid() && !$imageFile->hasMoved()) {
                $newName  = $imageFile->getRandomName();
                $savePath = 'public/uploads/styles-category/';
                $imageFile->move(FCPATH . $savePath, $newName);
                $update['image'] = $savePath . $newName;
            }

            $this->model->update($id, $update);

            return $this->respond([
                'status'  => 200,
                'message' => 'Style category updated successfully'
            ]);
        } catch (\Exception $e) {
            return $this->failServerError('Error updating style category: ' . $e->getMessage());
        }
    }


    public function delete($id = null)
    {
        try {
            if (!$this->model->find($id)) {
                return $this->failNotFound('Style category not found.');
            }

            $this->model->delete($id);

            return $this->respond([
                'status'  => 200,
                'message' => 'Style category deleted successfully'
            ]);
        } catch (\Exception $e) {
            return $this->failServerError('Error deleting style category: ' . $e->getMessage());
        }
    }
}
