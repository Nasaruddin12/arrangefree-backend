<?php

namespace App\Controllers;

use App\Models\GuideImagesModel;
use CodeIgniter\RESTful\ResourceController;
use Exception;

class GuideImagesController extends ResourceController
{
    protected $guideImagesModel;

    public function __construct()
    {
        $this->guideImagesModel = new GuideImagesModel();
    }

    // Fetch all guide images
    public function index()
    {
        try {
            $images = $this->guideImagesModel->findAll();
            return $this->respond([
                'status' => 200,
                'data'   => $images
            ]);
        } catch (\Exception $e) {
            return $this->failServerError('Something went wrong: ' . $e->getMessage());
        }
    }

    // Fetch a single guide image by ID
    public function show($id = null)
    {
        try {
            $image = $this->guideImagesModel->find($id);
            if (!$image) {
                return $this->failNotFound('Guide image not found.');
            }

            return $this->respond([
                'status' => 200,
                'data'   => $image
            ]);
        } catch (\Exception $e) {
            return $this->failServerError('Something went wrong: ' . $e->getMessage());
        }
    }

    // Create a new guide image entry
    public function create()
    {
        try {
            $rules = [
                'title'            => 'required|min_length[3]|max_length[255]',
                'image_url'        => 'required|valid_image_path',
                'service_type_id'  => 'required|integer',
                'room_id'          => 'required|integer',
            ];
    
            if (!$this->validate($rules)) {
                return $this->failValidationErrors($this->validator->getErrors());
            }
    
            $data = $this->request->getJSON(true);
            
            // Ensure image_url is a path, not a full URL
            if (filter_var($data['image_url'], FILTER_VALIDATE_URL)) {
                return $this->failValidationErrors('Image URL should be a relative path, not a full URL.');
            }

            $this->guideImagesModel->insert($data);

            return $this->respondCreated([
                'status'  => 201,
                'message' => 'Guide image added successfully.',
                'data'    => $data
            ]);
        } catch (\Exception $e) {
            return $this->failServerError('Something went wrong: ' . $e->getMessage());
        }
    }

    // Update an existing guide image entry
    public function update($id = null)
    {
        try {
            $image = $this->guideImagesModel->find($id);
            if (!$image) {
                return $this->failNotFound('Guide image not found.');
            }

            $data = $this->request->getRawInput();

            if (empty($data['title']) && empty($data['image_url']) && empty($data['service_type_id']) && empty($data['room_id'])) {
                return $this->failValidationErrors('At least one field must be provided.');
            }

            $this->guideImagesModel->update($id, $data);

            return $this->respond([
                'status'  => 200,
                'message' => 'Guide image updated successfully.',
                'data'    => $data
            ]);
        } catch (\Exception $e) {
            return $this->failServerError('Something went wrong: ' . $e->getMessage());
        }
    }

    // Delete a guide image
    public function delete($id = null)
    {
        try {
            $image = $this->guideImagesModel->find($id);
            if (!$image) {
                return $this->failNotFound('Guide image not found.');
            }

            $this->guideImagesModel->delete($id);

            return $this->respondDeleted([
                'status'  => 200,
                'message' => 'Guide image deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return $this->failServerError('Something went wrong: ' . $e->getMessage());
        }
    }
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
                $imageFile->move('public/uploads/guide/', $newName);
                return $this->respond([
                    'status' => 200,
                    'message' => 'Image uploaded successfully',
                    'image_url' => 'public/uploads/guide/' . $newName
                ], 200);
            }

            return $this->respond(['status' => 400, 'message' => 'Image upload failed'], 400);
        } catch (Exception $e) {
            return $this->respond(['status' => 500, 'message' => 'Image upload failed', 'error' => $e->getMessage()], 500);
        }
    }
}
