<?php

namespace App\Controllers;

use App\Models\ServiceTypeModel;
use App\Models\ServiceTypeRoomModel;
use CodeIgniter\RESTful\ResourceController;
use Exception;

class ServiceTypeController extends ResourceController
{
    protected $serviceTypeModel;
    protected $serviceTypeRoomModel;

    public function __construct()
    {
        $this->serviceTypeModel = new ServiceTypeModel();
        $this->serviceTypeRoomModel = new ServiceTypeRoomModel();
    }

    // ✅ Create Service Type
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

            // Insert into service_types table
            $serviceTypeId = $this->serviceTypeModel->insert($data, true); // `true` returns the inserted ID

            if (!$serviceTypeId) {
                return $this->respond(['status' => 400, 'message' => 'Failed to add serviceType'], 400);
            }

            // Insert into service_type_rooms table
            if (!empty($roomIds) && is_array($roomIds)) {
                $serviceTypeRoomModel = new ServiceTypeRoomModel();

                $dataRooms = [];
                foreach ($roomIds as $roomId) {
                    $dataRooms[] = [
                        'service_type_id' => $serviceTypeId,
                        'room_id' => $roomId
                    ];
                }

                $serviceTypeRoomModel->insertBatch($dataRooms);
            }

            // Commit Transaction
            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->respond(['status' => 400, 'message' => 'Failed to add serviceType'], 400);
            }

            return $this->respond([
                'status' => 201,
                'message' => 'Service Type added successfully',
                'data' => [
                    'id' => $serviceTypeId,
                    'name' => $name,
                    'image' => $image,
                    'room_ids' => $roomIds
                ]
            ], 201);
        } catch (Exception $e) {
            return $this->respond(['status' => 500, 'message' => 'An error occurred while adding serviceType', 'error' => $e->getMessage()], 500);
        }
    }


    // ✅ Read all Services
    public function index()
    {
        try {
            $service_types = $this->serviceTypeModel
                ->select('service_types.*, GROUP_CONCAT(DISTINCT rooms.name) as room_names, GROUP_CONCAT(DISTINCT service_type_rooms.room_id) as room_ids')
                ->join('service_type_rooms', 'service_type_rooms.service_type_id = service_types.id', 'left')
                ->join('rooms', 'rooms.id = service_type_rooms.room_id', 'left')
                ->groupBy('service_types.id') // Group by serviceType ID to avoid duplicate rows
                ->findAll();

            if (empty($service_types)) {
                return $this->failNotFound('No service_types found.');
            }

            // Format response to return room names as an array
            foreach ($service_types as &$serviceType) {
                $serviceType['room_names'] = $serviceType['room_names'] ? explode(',', $serviceType['room_names']) : [];
                $serviceType['room_ids'] = $serviceType['room_ids'] ? array_map('intval', explode(',', $serviceType['room_ids'])) : [];
            }

            return $this->respond([
                "status" => 200,
                "message" => "Data retrieved successfully",
                "data" => $service_types
            ], 200);
        } catch (Exception $e) {
            return $this->respond([
                'status' => 500,
                'message' => 'Failed to retrieve service_types',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ✅ Read a single serviceType by ID
    public function show($id = null)
    {
        try {
            $db = \Config\Database::connect();
            if (!$id) {
                return $this->failValidationErrors('ID is required');
            }

            // Fetch serviceType details
            $serviceType = $this->serviceTypeModel->find($id);
            if (!$serviceType) {
                return $this->failNotFound('Service Type not found.');
            }

            // Fetch room_ids associated with this serviceType
            $roomIds = $db->table('service_type_rooms')
                ->select('room_id')
                ->where('service_type_id', $id)
                ->get()
                ->getResultArray();

            // Extract room IDs into a simple array
            $serviceType['room_ids'] = array_column($roomIds, 'room_id');

            return $this->respond([
                "status" => 200,
                "message" => "Data retrieved successfully",
                "data" => $serviceType
            ], 200);
        } catch (Exception $e) {
            return $this->respond([
                'status' => 500,
                'message' => 'Failed to retrieve serviceType',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    // ✅ Update Service Type
    public function update($id = null)
    {
        try {
            $db = \Config\Database::connect();

            // Check if the serviceType exists
            $serviceType = $this->serviceTypeModel->find($id);
            if (!$serviceType) {
                return $this->failNotFound('Service Type not found.');
            }

            $name = $this->request->getVar('name');
            $image = $this->request->getVar('image'); // Image path
            $roomIds = $this->request->getVar('room_ids'); // Expected as an array

            if (empty($name) && empty($image) && empty($roomIds)) {
                return $this->respond(['status' => 400, 'message' => 'At least one field (Name, Image, or Room IDs) is required to update'], 400);
            }

            $data = [
                'name' => $name ?: $serviceType['name'],
                'image' => $image ?: $serviceType['image'],
            ];

            // Start transaction
            $db->transStart();

            // Update the serviceType data
            $this->serviceTypeModel->update($id, $data);

            // Update room associations if room_ids is provided
            if (is_array($roomIds)) {
                $this->serviceTypeRoomModel->updateServiceTypeRooms($id, $roomIds);
            }

            // Complete transaction
            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->respond(['status' => 400, 'message' => 'Failed to update serviceType'], 400);
            }

            return $this->respond([
                'status' => 200,
                'message' => 'Service Type updated successfully',
                'data' => $data
            ], 200);
        } catch (Exception $e) {
            return $this->respond([
                'status' => 500,
                'message' => 'Failed to update Service Type',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    // ✅ Delete Service Type
    public function delete($id = null)
    {
        try {
            $db = \Config\Database::connect();

            // Check if the Service Type exists
            $serviceType = $this->serviceTypeModel->find($id);
            if (!$serviceType) {
                return $this->failNotFound('Service Type not found.');
            }

            // Start transaction
            $db->transStart();

            // Delete associated room mappings
            $this->serviceTypeRoomModel->where('service_type_id', $id)->delete();

            // Unlink (Delete) the Image File if it exists
            if (!empty($serviceType['image']) && file_exists(FCPATH . $serviceType['image'])) {
                unlink(FCPATH . $serviceType['image']);
            }

            // Delete the serviceType
            $this->serviceTypeModel->delete($id);

            // Complete transaction
            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->respond(['status' => 400, 'message' => 'Failed to delete serviceType'], 400);
            }

            return $this->respond([
                'status' => 200,
                'message' => 'Service Type deleted successfully'
            ], 200);
        } catch (Exception $e) {
            return $this->respond([
                'status' => 500,
                'message' => 'Failed to delete serviceType',
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
    public function getRoomsByServiceType($serviceTypeId = null)
    {
        try {
            if (!$serviceTypeId) {
                return $this->failValidationErrors('Service Type ID is required');
            }

            // Fetch all rooms related to the given serviceType ID
            $rooms = $this->serviceTypeRoomModel
                ->select('rooms.*')  // Select all columns from rooms
                ->join('rooms', 'rooms.id = service_type_rooms.room_id')
                ->where('service_type_rooms.service_type_id', $serviceTypeId)
                ->findAll();

            if (empty($rooms)) {
                return $this->failNotFound('No rooms found for this serviceType.');
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
    public function changeStatus($id)
    {
        $status = $this->request->getVar('status'); // Get status from request

        if (!in_array($status, ['0', '1'])) {
            return $this->failValidationErrors('Invalid status value. Use 1 (active) or 0 (inactive).');
        }

        if (!$this->serviceTypeModel->find($id)) {
            return $this->failNotFound('Services type not found.');
        }

        $this->serviceTypeModel->update($id, ['status' => $status]);

        return $this->respond([
            'status' => 200,
            'message' => 'Status updated successfully',
            'new_status' => (int)$status
        ], 200);
    }
}
