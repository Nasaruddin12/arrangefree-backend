<?php

namespace App\Controllers;

use App\Models\GuideVideosModel;
use CodeIgniter\RESTful\ResourceController;

class GuideVideosController extends ResourceController
{
    protected $guideVideosModel;

    public function __construct()
    {
        $this->guideVideosModel = new GuideVideosModel();
    }

    // Get all guide videos
    public function index()
    {
        try {
            $videos = $this->guideVideosModel->findAll();
            return $this->respond([
                'status' => 200,
                'message' => 'Guide videos fetched successfully.',
                'data' => $videos
            ], 200);
        } catch (\Exception $e) {
            return $this->failServerError('Something went wrong: ' . $e->getMessage());
        }
    }

    // Get a single guide video by ID
    public function show($id = null)
    {
        try {
            $video = $this->guideVideosModel->find($id);
            if (!$video) {
                return $this->failNotFound('Guide video not found.');
            }
            return $this->respond([
                'status' => 200,
                'message' => 'Guide video retrieved successfully.',
                'data' => $video
            ], 200);
        } catch (\Exception $e) {
            return $this->failServerError('Something went wrong: ' . $e->getMessage());
        }
    }

    // Add a new guide video
    public function create()
    {
        try {
            $validation = \Config\Services::validation();

            // Define validation rules
            $rules = [
                'title'           => 'required',
                'description'     => 'required',
                'video_link'      => 'required|valid_url',
                'service_type_id' => 'required|integer',
                'room_id'         => 'required|integer'
            ];
    
            // Validate the request
            if (!$this->validate($rules)) {
                return $this->failValidationErrors($validation->getErrors());
            }
            $data = $this->request->getJSON(true);

            $this->guideVideosModel->insert($data);

            return $this->respondCreated([
                'status' => 201,
                'message' => 'Guide video added successfully.'
            ]);
        } catch (\Exception $e) {
            return $this->failServerError('Something went wrong: ' . $e->getMessage());
        }
    }

    // Update guide video details
    public function update($id = null)
    {
        try {
            // Check if the video exists
            $video = $this->guideVideosModel->find($id);
            if (!$video) {
                return $this->failNotFound('Guide video not found.');
            }

            // Get input data correctly
            $data = $this->request->getRawInput();

            // Ensure at least one field is provided
            if (empty($data['title']) && empty($data['video_link']) && empty($data['service_type_id']) && empty($data['room_id'])) {
                return $this->failValidationErrors('At least one field must be provided.');
            }

            // Update the record
            $this->guideVideosModel->update($id, $data);

            return $this->respond([
                'status'  => 200,
                'message' => 'Guide video updated successfully.',
            ], 200);
        } catch (\Exception $e) {
            return $this->failServerError('Something went wrong: ' . $e->getMessage());
        }
    }

    // Delete a guide video
    public function delete($id = null)
    {
        try {
            if (!$this->guideVideosModel->find($id)) {
                return $this->failNotFound('Guide video not found.');
            }

            $this->guideVideosModel->delete($id);

            return $this->respondDeleted([
                'status' => 200,
                'message' => 'Guide video deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return $this->failServerError('Something went wrong: ' . $e->getMessage());
        }
    }
}
