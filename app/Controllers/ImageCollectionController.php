<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ImageCollectionModel;

use CodeIgniter\API\ResponseTrait;
use Exception;

class ImageCollectionController extends BaseController
{
    use ResponseTrait;
    public function store()
    {
        try {
            $title = $this->request->getVar('title');
            $images = $this->request->getFiles('images'); // Get multiple files
            $uploadDirectory = 'uploads/image-collection/';
            $imagePaths = [];
            // print_r($images);
            // die();
            // Validate title
            if (empty($title)) {
                return $this->respond([
                    'status' => 400,
                    'message' => 'Title is required'
                ], 400);
            }
    
            // Ensure the upload directory exists
            if (!is_dir($uploadDirectory)) {
                mkdir($uploadDirectory, 0777, true);
            }
    
            // Check if images exist and handle single/multiple file uploads
            if (!isset($images['images'])) {
                return $this->respond([
                    'status' => 400,
                    'message' => 'No images uploaded'
                ], 400);
            }
    
            $uploadedFiles = $images['images'];
            if (!is_array($uploadedFiles)) {
                $uploadedFiles = [$uploadedFiles]; // Convert to array if only one file is uploaded
            }
    
            foreach ($uploadedFiles as $file) {
                if ($file->isValid() && !$file->hasMoved()) {
                    $fileName = $file->getRandomName();
                    if ($file->move($uploadDirectory, $fileName)) {
                        $imagePaths[] = $uploadDirectory . $fileName; // Store relative path
                    } else {
                        return $this->respond([
                            'status' => 400,
                            'message' => 'File Not Uploaded Successfully'
                        ], 400);
                    }
                }
            }
    
            // Insert into database
            $model = new ImageCollectionModel();
            $model->insert([
                'title' => $title,
                'images' => json_encode($imagePaths),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
    
            return $this->respond([
                'status' => 200,
                'message' => 'Files Uploaded Successfully',
                'paths' => $imagePaths
            ], 200);
        } catch (\Exception $e) {
            return $this->respond([
                'status' => 500,
                'message' => 'Failed to store image collection',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    


    public function getImages($id)
    {
        try {
            $model = new ImageCollectionModel();
            $collection = $model->find($id);

            if (!$collection) {
                return $this->respond([
                    'status' => 404,
                    'error' => 'Collection not found'
                ], 404);
            }

            $collection['images'] = json_decode($collection['images'], true);

            return $this->respond([
                'status' => 200,
                'data' => $collection
            ], 200);
        } catch (Exception $e) {
            return $this->respond([
                'status' => 500,
                'error' => 'Failed to fetch image collection',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getAll()
    {
        try {
            $model = new ImageCollectionModel();
            $collections = $model->findAll();

            foreach ($collections as &$collection) {
                $collection['images'] = json_decode($collection['images'], true);
            }

            return $this->respond([
                'status' => 200,
                'data' => $collections
            ], 200);
        } catch (Exception $e) {
            return $this->respond([
                'status' => 500,
                'error' => 'Failed to fetch all image collections',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function update($id)
    {
        try {
            $model = new ImageCollectionModel();
            $collection = $model->find($id);

            if (!$collection) {
                return $this->respond([
                    'status' => 404,
                    'error' => 'Collection not found'
                ], 404);
            }

            $title = $this->request->getPost('title');
            $images = $this->request->getPost('images');

            if (empty($title) || !is_array($images)) {
                return $this->respond([
                    'status' => 400,
                    'error' => 'Title is required and images must be an array'
                ], 400);
            }

            $model->update($id, [
                'title' => $title,
                'images' => json_encode($images),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            return $this->respond([
                'status' => 200,
                'message' => 'Image collection updated successfully'
            ], 200);
        } catch (Exception $e) {
            return $this->respond([
                'status' => 500,
                'error' => 'Failed to update image collection',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
