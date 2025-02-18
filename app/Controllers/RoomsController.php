<?php

namespace App\Controllers;

use App\Models\RoomModel;
use App\Controllers\BaseController;

class RoomController extends BaseController
{
    protected $roomModel;

    public function __construct()
    {
        $this->roomModel = new RoomModel();
    }

    // Create Room
    public function create()
    {
        // Get the data from the request
        $data = [
            'name' => $this->request->getVar('name'),
        ];

        // Handle image upload separately
        $imagePath = $this->uploadImage();
        if ($imagePath) {
            $data['image'] = $imagePath;
        }

        // Save the data into the database
        $this->roomModel->save($data);

        return $this->response->setJSON(['message' => 'Room Created Successfully']);
    }

    // Read all Rooms
    public function index()
    {
        $rooms = $this->roomModel->findAll();
        return $this->response->setJSON($rooms);
    }

    // Get a specific Room by ID
    public function show($id)
    {
        $room = $this->roomModel->find($id);
        if ($room) {
            return $this->response->setJSON($room);
        } else {
            return $this->response->setJSON(['message' => 'Room not found'], 404);
        }
    }

    // Update Room
    public function update($id)
    {
        // Get the data from the request
        $data = [
            'name' => $this->request->getVar('name'),
        ];

        // Handle image upload separately
        $imagePath = $this->uploadImage();
        if ($imagePath) {
            $data['image'] = $imagePath;
        } else {
            // Keep existing image if no new image is uploaded
            $room = $this->roomModel->find($id);
            $data['image'] = $room['image'];
        }

        // Update the database record
        $this->roomModel->update($id, $data);

        return $this->response->setJSON(['message' => 'Room Updated Successfully']);
    }

    // Delete Room
    public function delete($id)
    {
        $room = $this->roomModel->find($id);

        if ($room['image']) {
            // Delete the image file from server
            unlink(WRITEPATH . 'uploads/' . $room['image']);
        }

        // Delete the record from the database
        $this->roomModel->delete($id);

        return $this->response->setJSON(['message' => 'Room Deleted Successfully']);
    }

    // Separate method for handling image upload
    private function uploadImage()
    {
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
    }
}
