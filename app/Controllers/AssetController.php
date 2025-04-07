<?php

namespace App\Controllers;

use App\Models\AssetModel;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\Exceptions\HTTPException;

class AssetController extends ResourceController
{
    protected $modelName = 'App\Models\AssetModel';
    protected $format    = 'json';

    // Fetch all assets
    public function index()
    {
        try {
            $assets = $this->model->getAssetsWithRoomName();
            return $this->respond($assets);
        } catch (\Exception $e) {
            return $this->failServerError("An error occurred while fetching assets: " . $e->getMessage());
        }
    }


    // Fetch single asset by ID
    public function show($id = null)
    {
        try {
            $asset = $this->model->find($id);
            if (!$asset) {
                return $this->failNotFound("Asset not found.");
            }
            return $this->respond($asset);
        } catch (\Exception $e) {
            return $this->failServerError("An error occurred while fetching the asset: " . $e->getMessage());
        }
    }

    // Create new asset
    public function create()
    {
        try {
            $data = $this->request->getJSON(true); // Get JSON input as an associative array

            // Validate the request data
            if (!$this->validate([
                'title'    => 'required|string|max_length[255]',
                'file'     => 'required|string|max_length[255]',
                'tags'     => 'required|string|max_length[255]',
                'details'  => 'permit_empty|string',
                'room_id'  => 'permit_empty|string|max_length[255]',
                'style_id' => 'permit_empty|string|max_length[255]',
            ])) {
                return $this->failValidationErrors($this->validator->getErrors());
            }

            // Insert into the database
            $this->model->insert($data);

            return $this->respondCreated([
                'message' => 'Asset created successfully.',
                'data'    => $data
            ]);
        } catch (\Exception $e) {
            return $this->failServerError("An error occurred while creating the asset: " . $e->getMessage());
        }
    }


    // Update asset
    public function update($id = null)
    {
        try {
            $data = $this->request->getJSON(true);

            if (!$this->model->find($id)) {
                return $this->failNotFound("Asset not found.");
            }

            $this->model->update($id, $data);
            return $this->respond(['message' => 'Asset updated successfully.']);
        } catch (\Exception $e) {
            return $this->failServerError("An error occurred while updating the asset: " . $e->getMessage());
        }
    }

    // Delete asset
    public function delete($id = null)
    {
        try {
            if (!$this->model->find($id)) {
                return $this->failNotFound("Asset not found.");
            }

            $this->model->delete($id);
            return $this->respondDeleted(['message' => 'Asset deleted successfully.']);
        } catch (\Exception $e) {
            return $this->failServerError("An error occurred while deleting the asset: " . $e->getMessage());
        }
    }

    // Upload file function
    public function uploadFile()
    {
        try {
            $file = $this->request->getFile('file');

            if (!$file->isValid() || $file->hasMoved()) {
                return $this->failValidationErrors('Invalid file upload or file already moved.');
            }

            // Allowed file types
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp', 'tiff', 'pdf', 'usdz', 'fbx', 'obj', 'gltf', 'glb', 'stl', 'dae', 'zip'];

            // Get file extension
            $extension = $file->getExtension();

            if (!in_array($extension, $allowedExtensions)) {
                return $this->failValidationErrors("Invalid file type. Allowed types: " . implode(', ', $allowedExtensions));
            }

            // Define upload directory
            $uploadPath = 'public/uploads/assets/';

            // Generate new random name and move file
            $newName = $file->getRandomName();
            $file->move($uploadPath, $newName);

            // Generate file URL


            // Response
            return $this->respondCreated([
                'message' => 'File uploaded successfully',
                'file_url' => $uploadPath . $newName
            ]);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            return $this->failServerError("An error occurred while uploading the file: " . $e->getMessage());
        }
    }
}
