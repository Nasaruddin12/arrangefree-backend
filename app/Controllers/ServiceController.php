<?php

namespace App\Controllers;

use App\Models\ServiceModel;
use App\Models\ServiceRoomModel;
use CodeIgniter\RESTful\ResourceController;
use Exception;

class ServiceController extends ResourceController
{
    protected $serviceModel;
    protected $serviceRoomModel;

    public function __construct()
    {
        $this->serviceModel = new ServiceModel();
        $this->serviceRoomModel = new ServiceRoomModel();
    }

    // ✅ Create Service
    public function create()
    {
        try {
            $db = \Config\Database::connect(); // Get database instance

            $name = $this->request->getVar('name');
            $image = $this->request->getVar('image'); // Image path
            $roomIds = $this->request->getVar('room_ids'); // Expected as an array [1, 2, 3]

            if (empty($name) || empty($image)) {
                return $this->respond(['status' => 400, 'message' => 'Name and Image are required'], 400);
            }

            $data = [
                'name' => $name,
                'image' => $image,
            ];

            // Start Transaction
            $db->transStart();

            // Insert into services table
            $serviceId = $this->serviceModel->insert($data, true); // `true` returns the inserted ID

            if (!$serviceId) {
                return $this->respond(['status' => 400, 'message' => 'Failed to add service'], 400);
            }

            // Insert into service_rooms table
            if (!empty($roomIds) && is_array($roomIds)) {
                $serviceRoomModel = new ServiceRoomModel();

                $dataRooms = [];
                foreach ($roomIds as $roomId) {
                    $dataRooms[] = [
                        'service_id' => $serviceId,
                        'room_id' => $roomId
                    ];
                }

                $serviceRoomModel->insertBatch($dataRooms);
            }

            // Commit Transaction
            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->respond(['status' => 400, 'message' => 'Failed to add service'], 400);
            }

            return $this->respond([
                'status' => 201,
                'message' => 'Service added successfully',
                'data' => [
                    'id' => $serviceId,
                    'name' => $name,
                    'image' => $image,
                    'room_ids' => $roomIds
                ]
            ], 201);
        } catch (Exception $e) {
            return $this->respond(['status' => 500, 'message' => 'An error occurred while adding service', 'error' => $e->getMessage()], 500);
        }
    }


    // ✅ Read all Services
    public function index()
    {
        try {
            $services = $this->serviceModel
                ->select('services.*, GROUP_CONCAT(rooms.name) as room_names') // Fetch room names
                ->join('service_rooms', 'service_rooms.service_id = services.id', 'left')
                ->join('rooms', 'rooms.id = service_rooms.room_id', 'left')
                ->groupBy('services.id') // Group by service ID to avoid duplicate rows
                ->findAll();

            if (empty($services)) {
                return $this->failNotFound('No services found.');
            }

            // Format response to return room names as an array
            foreach ($services as &$service) {
                $service['room_names'] = $service['room_names'] ? explode(',', $service['room_names']) : [];
            }

            return $this->respond([
                "status" => 200,
                "message" => "Data retrieved successfully",
                "data" => $services
            ], 200);
        } catch (Exception $e) {
            return $this->respond([
                'status' => 500,
                'message' => 'Failed to retrieve services',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ✅ Read a single service by ID
    public function show($id = null)
    {
        try {
            $db = \Config\Database::connect();
            if (!$id) {
                return $this->failValidationErrors('ID is required');
            }

            // Fetch service details
            $service = $this->serviceModel->find($id);
            if (!$service) {
                return $this->failNotFound('Service not found.');
            }

            // Fetch room_ids associated with this service
            $roomIds = $db->table('service_rooms')
                ->select('room_id')
                ->where('service_id', $id)
                ->get()
                ->getResultArray();

            // Extract room IDs into a simple array
            $service['room_ids'] = array_column($roomIds, 'room_id');

            return $this->respond([
                "status" => 200,
                "message" => "Data retrieved successfully",
                "data" => $service
            ], 200);
        } catch (Exception $e) {
            return $this->respond([
                'status' => 500,
                'message' => 'Failed to retrieve service',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    // ✅ Update Service
    public function update($id = null)
    {
        try {
            $db = \Config\Database::connect();

            // Check if the service exists
            $service = $this->serviceModel->find($id);
            if (!$service) {
                return $this->failNotFound('Service not found.');
            }

            $name = $this->request->getVar('name');
            $image = $this->request->getVar('image'); // Image path
            $roomIds = $this->request->getVar('room_ids'); // Expected as an array

            if (empty($name) && empty($image) && empty($roomIds)) {
                return $this->respond(['status' => 400, 'message' => 'At least one field (Name, Image, or Room IDs) is required to update'], 400);
            }

            $data = [
                'name' => $name ?: $service['name'],
                'image' => $image ?: $service['image'],
            ];

            // Start transaction
            $db->transStart();

            // Update the service data
            $this->serviceModel->update($id, $data);

            // Update room associations if room_ids is provided
            if (is_array($roomIds)) {
                $this->serviceRoomModel->updateServiceRooms($id, $roomIds);
            }

            // Complete transaction
            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->respond(['status' => 400, 'message' => 'Failed to update service'], 400);
            }

            return $this->respond([
                'status' => 200,
                'message' => 'Service updated successfully',
                'data' => $data
            ], 200);
        } catch (Exception $e) {
            return $this->respond([
                'status' => 500,
                'message' => 'Failed to update service',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    // ✅ Delete Service
    public function delete($id = null)
    {
        try {
            $db = \Config\Database::connect();

            // Check if the service exists
            $service = $this->serviceModel->find($id);
            if (!$service) {
                return $this->failNotFound('Service not found.');
            }

            // Start transaction
            $db->transStart();

            // Delete associated room mappings
            $this->serviceRoomModel->where('service_id', $id)->delete();

            // Unlink (Delete) the Image File if it exists
            if (!empty($service['image']) && file_exists(FCPATH . $service['image'])) {
                unlink(FCPATH . $service['image']);
            }

            // Delete the service
            $this->serviceModel->delete($id);

            // Complete transaction
            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->respond(['status' => 400, 'message' => 'Failed to delete service'], 400);
            }

            return $this->respond([
                'status' => 200,
                'message' => 'Service deleted successfully'
            ], 200);
        } catch (Exception $e) {
            return $this->respond([
                'status' => 500,
                'message' => 'Failed to delete service',
                'error' => $e->getMessage()
            ], 500);
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
    public function getRoomsByService($serviceId = null)
    {
        try {
            if (!$serviceId) {
                return $this->failValidationErrors('Service ID is required');
            }

            // Fetch all rooms related to the given service ID
            $rooms = $this->serviceRoomModel
                ->select('rooms.*')  // Select all columns from rooms
                ->join('rooms', 'rooms.id = service_rooms.room_id')
                ->where('service_rooms.service_id', $serviceId)
                ->findAll();

            if (empty($rooms)) {
                return $this->failNotFound('No rooms found for this service.');
            }

            return $this->respond([
                "status" => 200,
                "message" => "Rooms retrieved successfully",
                "data" => $rooms
            ], 200);
        } catch (Exception $e) {
            return $this->respond([
                'status' => 500,
                'message' => 'Failed to retrieve rooms',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
