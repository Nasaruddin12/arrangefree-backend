<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\RoomModel;
use CodeIgniter\API\ResponseTrait;
use Exception;

class RoomsController extends BaseController
{
    use ResponseTrait;
    protected $roomModel;

    public function __construct()
    {
        $this->roomModel = new RoomModel();
    }

    // ✅ Create Room
    public function create()
    {
        try {
            // Retrieve input values
            $name = $this->request->getVar('name');
            $image = $this->request->getVar('image');
            $type = $this->request->getVar('type');
    
            // Check if name and image are provided
            if (empty($name) || empty($image))  {
                return $this->respond([
                    'status' => 400,
                    'message' => 'Both name and image are required.'
                ], 400);
            }
    
            // Prepare data for saving
            $data = [
                'name' => $name,
                'image' => $image,
                'type' => $type,
            ];
    
            // Save the data
            if ($this->roomModel->save($data)) {
                return $this->respond([
                    'status' => 201,
                    'message' => 'Room Created Successfully',
                    'data' => $data
                ], 201);
            }
    
            return $this->respond(['status' => 400, 'message' => 'Failed to create Room'], 400);
        } catch (Exception $e) {
            return $this->respond([
                'status' => 500,
                'message' => 'An error occurred while creating Room',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    

    // ✅ Read all Rooms
    public function index()
    {
        try {
            $rooms = $this->roomModel->findAll();
            if (empty($rooms)) {
                return $this->failNotFound('No rooms found.');
            }

            return $this->respond([
                'status' => 200,
                'message' => 'Rooms retrieved successfully',
                'data' => $rooms
            ], 200);
        } catch (Exception $e) {
            return $this->respond(['status' => 500, 'message' => 'Failed to retrieve Rooms', 'error' => $e->getMessage()], 500);
        }
    }

    // ✅ Get a specific Room by ID
    public function show($id)
    {
        try {
            $room = $this->roomModel->find($id);
            if (!$room) {
                return $this->failNotFound('Room not found.');
            }

            return $this->respond([
                'status' => 200,
                'message' => 'Room retrieved successfully',
                'data' => $room
            ], 200);
        } catch (Exception $e) {
            return $this->respond(['status' => 500, 'message' => 'Failed to retrieve Room', 'error' => $e->getMessage()], 500);
        }
    }

    // ✅ Update Room
    public function update($id)
    {
        try {
            // Retrieve the room by ID
            $room = $this->roomModel->find($id);
            if (!$room) {
                return $this->failNotFound('Room not found.');
            }
    
            // Retrieve the input data
            $name = $this->request->getVar('name');
            $image = $this->request->getVar('image');
            $type = $this->request->getVar('type');
    
            // Validate that both name and image are provided
            if (empty($name) || empty($image)) {
                return $this->respond([
                    'status' => 400,
                    'message' => 'Both name and image are required.'
                ], 400);
            }
            // Prepare data for updating
            $data = [
                'name' => $name,
                'image' => $image,
                'type' => $type,
            ];
    
            // Update the room data
            if ($this->roomModel->update($id, $data)) {
                return $this->respond([
                    'status' => 200,
                    'message' => 'Room Updated Successfully',
                    'data' => $data
                ], 200);
            }
    
            return $this->respond(['status' => 400, 'message' => 'Failed to update Room'], 400);
        } catch (Exception $e) {
            return $this->respond([
                'status' => 500,
                'message' => 'Failed to update Room',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    // ✅ Delete Room
    public function delete($id)
    {
        try {
            $room = $this->roomModel->find($id);
            if (!$room) {
                return $this->failNotFound('Room not found.');
            }

            // Delete the image if it exists
            if ($room['image']) {
                $this->deleteImage($room['image']);
            }

            if ($this->roomModel->delete($id)) {
                return $this->respond([
                    'status' => 200,
                    'message' => 'Room Deleted Successfully'
                ], 200);
            }

            return $this->respond(['status' => 400, 'message' => 'Failed to delete Room'], 400);
        } catch (Exception $e) {
            return $this->respond(['status' => 500, 'message' => 'Failed to delete Room', 'error' => $e->getMessage()], 500);
        }
    }

    // ✅ Separate method for handling image upload
    private function uploadImage()
    {
        try {
            $file = $this->request->getFile('image');
            $imagePath = '';

            // Check if the file is valid and if it's an image
            if ($file && $file->isValid()) {
                // Generate a random name for the image
                $imagePath = $file->getRandomName();

                // Move the file to the writable/uploads directory
                $file->move(WRITEPATH . 'uploads/', $imagePath);
            }

            return $imagePath;
        } catch (Exception $e) {
            return null; // Return null if any error occurs
        }
    }

    // ✅ Delete image file by path
    private function deleteImage($imagePath)
    {
        try {
            $fullPath = WRITEPATH . 'uploads/' . $imagePath;
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        } catch (Exception $e) {
            // Handle any errors that occur during image deletion
        }
    }
}
