<?php

namespace App\Controllers;

use App\Models\ServiceModel;
use CodeIgniter\RESTful\ResourceController;
use Exception;

class ServiceController extends ResourceController
{
    protected $serviceModel;

    public function __construct()
    {
        $this->serviceModel = new ServiceModel();
    }

    // ✅ Create Service
    public function create()
    {
        try {
            $name = $this->request->getVar('name');
            $image = $this->request->getVar('image'); // Image path
            if (empty($name) || empty($image)) {
                return $this->respond(['status' => 400, 'message' => 'Name and Image are required'], 400);
            }

            $data = [
                'name' => $name,
                'image' => $image,
            ];

            if ($this->serviceModel->insert($data)) {
                return $this->respond([
                    'status' => 201,
                    'message' => 'Service added successfully',
                    'data' => $data
                ], 201);
            }

            return $this->respond(['status' => 400, 'message' => 'Failed to add service'], 400);
        } catch (Exception $e) {
            return $this->respond(['status' => 500, 'message' => 'An error occurred while adding service', 'error' => $e->getMessage()], 500);
        }
    }

    // ✅ Read all Services
    public function index()
    {
        try {
            $services = $this->serviceModel->findAll();
            if (empty($services)) {
                return $this->failNotFound('No services found.');
            }

            return $this->respond([
                "status" => 200,
                "message" => "Data retrieved successfully",
                "data" => $services
            ], 200);
        } catch (Exception $e) {
            return $this->respond(['status' => 500, 'message' => 'Failed to retrieve services', 'error' => $e->getMessage()], 500);
        }
    }

    // ✅ Read a single service by ID
    public function show($id = null)
    {
        try {
            $service = $this->serviceModel->find($id);
            if (!$service) {
                return $this->failNotFound('Service not found.');
            }

            return $this->respond([
                "status" => 200,
                "message" => "Data retrieved successfully",
                "data" => $service
            ], 200);
        } catch (Exception $e) {
            return $this->respond(['status' => 500, 'message' => 'Failed to retrieve service', 'error' => $e->getMessage()], 500);
        }
    }

    // ✅ Update Service
    public function update($id = null)
    {
        try {
            $service = $this->serviceModel->find($id);
            if (!$service) {
                return $this->failNotFound('Service not found.');
            }

            $name = $this->request->getVar('name');
            $image = $this->request->getVar('image'); // Image path

            if (empty($name) && empty($image)) {
                return $this->respond(['status' => 400, 'message' => 'Name or Image is required to update'], 400);
            }

            $data = [
                'name' => $name ?: $service['name'],
                'image' => $image ?: $service['image'],
            ];

            if ($this->serviceModel->update($id, $data)) {
                return $this->respond([
                    'status' => 200,
                    'message' => 'Service updated successfully',
                    'data' => $data
                ], 200);
            }

            return $this->respond(['status' => 400, 'message' => 'Failed to update service'], 400);
        } catch (Exception $e) {
            return $this->respond(['status' => 500, 'message' => 'Failed to update service', 'error' => $e->getMessage()], 500);
        }
    }

    // ✅ Delete Service
    public function delete($id = null)
    {
        try {
            $service = $this->serviceModel->find($id);
            if (!$service) {
                return $this->failNotFound('Service not found.');
            }

            if ($this->serviceModel->delete($id)) {
                return $this->respond([
                    'status' => 200,
                    'message' => 'Service deleted successfully'
                ], 200);
            }

            return $this->respond(['status' => 400, 'message' => 'Failed to delete service'], 400);
        } catch (Exception $e) {
            return $this->respond(['status' => 500, 'message' => 'Failed to delete service', 'error' => $e->getMessage()], 500);
        }
    }

    // ✅ Upload Image (Separate method for image handling)
    public function uploadImage()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules([
                'image' => 'uploaded[image]|is_image[image]|max_size[image,2048]|mime_in[image,image/png,image/jpeg,image/jpg]',
            ]);

            if (!$validation->withRequest($this->request)->run()) {
                return $this->respond(['status' => 400, 'message' => 'Invalid image file', 'errors' => $validation->getErrors()], 400);
            }

            $imageFile = $this->request->getFile('image');
            if ($imageFile->isValid() && !$imageFile->hasMoved()) {
                $newName = $imageFile->getRandomName();
                $imageFile->move('public/uploads/services/', $newName);
                return $this->respond([
                    'status' => 200,
                    'message' => 'Image uploaded successfully',
                    'image_url' => 'public/uploads/services/' . $newName
                ], 200);
            }

            return $this->respond(['status' => 400, 'message' => 'Image upload failed'], 400);
        } catch (Exception $e) {
            return $this->respond(['status' => 500, 'message' => 'Image upload failed', 'error' => $e->getMessage()], 500);
        }
    }
}
