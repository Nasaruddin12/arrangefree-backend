<?php

namespace App\Controllers;

use App\Models\RoomElementModel;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\Database\Exceptions\DatabaseException;

class RoomElementController extends ResourceController
{
    protected $modelName = 'App\Models\RoomElementModel';
    protected $format    = 'json';

    // Get all room elements
    public function index()
    {
        try {
            $data = $this->model->findAll();

            return $this->respond([
                'status'  => 200, // HTTP OK
                'message' => 'Room elements retrieved successfully',
                'data'    => $data,
            ], 200);
        } catch (\Exception $e) {
            return $this->failServerError('An error occurred while fetching room elements');
        }
    }

    // Create a new room element
    public function create()
    {
        try {
            $data = $this->request->getJSON(true);

            if (!$this->validate(['title' => 'required|string|max_length[255]'])) {
                return $this->failValidationErrors($this->validator->getErrors());
            }

            $this->model->insert($data);

            return $this->respondCreated([
                'status'  => 201, // HTTP Created
                'message' => 'Room element created successfully',
                'data'    => $data,
            ], 201);
        } catch (DatabaseException $e) {
            return $this->failServerError('Database error: ' . $e->getMessage());
        } catch (\Exception $e) {
            return $this->failServerError('An unexpected error occurred: ' . $e->getMessage());
        }
    }

    // Get a single room element
    public function show($id = null)
    {
        try {
            $data = $this->model->find($id);
            if (!$data) {
                return $this->failNotFound('Room element not found');
            }

            return $this->respond([
                'status'  => 200, // HTTP OK
                'message' => 'Room element retrieved successfully',
                'data'    => $data,
            ], 200);
        } catch (\Exception $e) {
            return $this->failServerError('An error occurred while fetching the room element');
        }
    }

    // Update a room element
    public function update($id = null)
    {
        try {
            $data = $this->request->getJSON(true);

            if (!$this->validate(['title' => 'required|string|max_length[255]'])) {
                return $this->failValidationErrors($this->validator->getErrors());
            }

            if (!$this->model->find($id)) {
                return $this->failNotFound('Room element not found');
            }

            $this->model->update($id, $data);

            return $this->respond([
                'status'  => 200, // HTTP OK
                'message' => 'Room element updated successfully',
                'data'    => $data,
            ], 200);
        } catch (DatabaseException $e) {
            return $this->failServerError('Database error: ' . $e->getMessage());
        } catch (\Exception $e) {
            return $this->failServerError('An unexpected error occurred: ' . $e->getMessage());
        }
    }

    // Delete a room element
    public function delete($id = null)
    {
        try {
            if (!$this->model->find($id)) {
                return $this->failNotFound('Room element not found');
            }

            $this->model->delete($id);

            return $this->respondDeleted([
                'status'  => 200, // HTTP OK
                'message' => 'Room element deleted successfully',
                'data'    => ['id' => $id],
            ], 200);
        } catch (DatabaseException $e) {
            return $this->failServerError('Database error: ' . $e->getMessage());
        } catch (\Exception $e) {
            return $this->failServerError('An unexpected error occurred: ' . $e->getMessage());
        }
    }
}
