<?php

namespace App\Controllers;

use App\Models\WorkTypeModel;
use App\Controllers\BaseController;
use App\Models\WorkTypeRoomModel;
use CodeIgniter\API\ResponseTrait;
use Exception;

class WorkTypeController extends BaseController
{
    use ResponseTrait;
    protected $workTypeModel;
    protected $workTypeRoomModel;


    public function __construct()
    {
        $this->workTypeModel = new WorkTypeModel();
        $this->workTypeRoomModel  = new WorkTypeRoomModel();
    }

    // ✅ Create Work Type
    public function create()
    {
        try {
            $db = \Config\Database::connect();
            $db->transStart(); // Start Transaction

            // Step 1: Insert Work Type
            $data = [
                'name' => $this->request->getVar('name'),
                'service_id' => $this->request->getVar('service_id'),
                'rate' => $this->request->getVar('rate'),
                'rate_type' => $this->request->getVar('rate_type'),
                'description' => $this->request->getVar('description'),
                'materials' => $this->request->getVar('materials'),
                'features' => $this->request->getVar('features'),
                'care_instructions' => $this->request->getVar('care_instructions'),
                'warranty_details' => $this->request->getVar('warranty_details'),
                'quality_promise' => $this->request->getVar('quality_promise'),
                'status' => $this->request->getVar('status'),
                'image' => $this->request->getVar('image'), // Image path
            ];

            if (!$this->workTypeModel->save($data)) {
                return $this->respond(['status' => 400, 'message' => 'Failed to create Work Type'], 400);
            }

            $workTypeId = $this->workTypeModel->getInsertID(); // Get the last inserted ID

            // Step 2: Insert Work Type Rooms
            $roomIds = $this->request->getVar('room_ids'); // Expecting an array of room IDs

            if (!empty($roomIds) && is_array($roomIds)) {

                $roomData = [];

                foreach ($roomIds as $roomId) {
                    $roomData[] = [
                        'work_type_id' => $workTypeId,
                        'room_id' => $roomId
                    ];
                }

                if (!empty($roomData)) {
                    $this->workTypeRoomModel->insertBatch($roomData);
                }
            }

            $db->transComplete(); // Commit Transaction

            return $this->respond([
                'status' => 201,
                'message' => 'Work Type Created Successfully',
                'data' => [
                    'work_type' => $data,
                    'room_ids' => $roomIds ?? []
                ]
            ], 201);
        } catch (Exception $e) {
            return $this->respond([
                'status' => 500,
                'message' => 'An error occurred while creating Work Type',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    // ✅ Upload Image (Returns only path)
    public function uploadImage()
    {
        try {
            $file = $this->request->getFile('image');
            if (!$file || !$file->isValid()) {
                return $this->respond(['status' => 400, 'message' => 'Invalid image file'], 400);
            }

            // Generate a random name and move the image
            $imagePath = $file->getRandomName();
            $file->move(WRITEPATH . 'uploads/services', $imagePath);

            return $this->respond([
                'status' => 200,
                'message' => 'Image uploaded successfully',
                'image_path' => $imagePath
            ], 200);
        } catch (Exception $e) {
            return $this->respond(['status' => 500, 'message' => 'Failed to upload image', 'error' => $e->getMessage()], 500);
        }
    }

    // ✅ Delete Image by Path
    public function deleteImage()
    {
        try {
            $imagePath = $this->request->getVar('image_path');
            if (!$imagePath) {
                return $this->respond(['status' => 400, 'message' => 'Image path is required'], 400);
            }

            if (file_exists($imagePath)) {
                unlink($imagePath);
                return $this->respond(['status' => 200, 'message' => 'Image deleted successfully'], 200);
            } else {
                return $this->respond(['status' => 404, 'message' => 'Image not found'], 404);
            }
        } catch (Exception $e) {
            return $this->respond(['status' => 500, 'message' => 'Failed to delete image', 'error' => $e->getMessage()], 500);
        }
    }

    // ✅ Read all Work Types
    public function index()
    {
        try {
            $workTypes = $this->workTypeModel
                ->select('work_types.*, services.name as service_name, GROUP_CONCAT(work_type_rooms.room_id) as room_ids') // Fetch room IDs
                ->join('services', 'services.id = work_types.service_id', 'left') // Joining service table
                ->join('work_type_rooms', 'work_type_rooms.work_type_id = work_types.id', 'left') // Joining work_type_rooms
                ->groupBy('work_types.id') // Grouping to prevent duplicate work types
                ->findAll();

            if (empty($workTypes)) {
                return $this->failNotFound('No Work Types found.');
            }

            // Convert room_ids string to an array
            foreach ($workTypes as &$workType) {
                $workType['room_ids'] = $workType['room_ids'] ? explode(',', $workType['room_ids']) : [];
            }

            return $this->respond([
                'status' => 200,
                'message' => 'Data retrieved successfully',
                'data' => $workTypes
            ], 200);
        } catch (Exception $e) {
            return $this->respond([
                'status' => 500,
                'message' => 'Failed to retrieve Work Types',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    // ✅ Update Work Type
    public function update($id)
    {
        try {
            $db = \Config\Database::connect();
            $workType = $this->workTypeModel->find($id);
            if (!$workType) {
                return $this->failNotFound('Work Type not found.');
            }

            $data = [
                'name' => $this->request->getVar('name'),
                'service_id' => $this->request->getVar('service_id'),
                'rate' => $this->request->getVar('rate'),
                'rate_type' => $this->request->getVar('rate_type'),
                'description' => $this->request->getVar('description'),
                'materials' => $this->request->getVar('materials'),
                'features' => $this->request->getVar('features'),
                'care_instructions' => $this->request->getVar('care_instructions'),
                'warranty_details' => $this->request->getVar('warranty_details'),
                'quality_promise' => $this->request->getVar('quality_promise'),
                'status' => $this->request->getVar('status'),
                'image' => $this->request->getVar('image') ?? $workType['image'], // Keep old image if not provided
            ];

            // Start transaction
            $db->transStart();

            // Update the work_type record
            $this->workTypeModel->update($id, $data);

            // Update work_type_rooms if room_ids are provided
            $roomIds = $this->request->getVar('room_ids'); // Expected as an array [1, 2, 3]
            if (!empty($roomIds) && is_array($roomIds)) {
                if (!$this->workTypeRoomModel->updateWorkTypeRooms($id, $roomIds)) {
                    // Rollback if updating work_type_rooms fails
                    $db->transRollback();
                    return $this->respond(['status' => 400, 'message' => 'Failed to update Work Type Rooms'], 400);
                }
            }

            // Commit transaction
            $db->transComplete();

            if (!$db->transStatus()) {
                return $this->respond(['status' => 400, 'message' => 'Failed to update Work Type'], 400);
            }

            return $this->respond([
                'status' => 200,
                'message' => 'Work Type Updated Successfully',
                'data' => $data
            ], 200);
        } catch (Exception $e) {
            return $this->respond([
                'status' => 500,
                'message' => 'Failed to update Work Type',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    // ✅ Delete Work Type
    public function delete($id)
    {
        try {
            $db = \Config\Database::connect();
            $workType = $this->workTypeModel->find($id);

            if (!$workType) {
                return $this->failNotFound('Work Type not found.');
            }

            // Start transaction
            $db->transStart();

            // Step 1: Delete related work_type_rooms
            $this->workTypeRoomModel->where('work_type_id', $id)->delete();

            // Step 2: Delete the work_type image if exists
            if (!empty($workType['image'])) {
                $this->deleteImageByPath($workType['image']);
            }

            // Step 3: Delete the work_type itself
            $this->workTypeModel->delete($id);

            // Commit transaction
            $db->transComplete();

            if (!$db->transStatus()) {
                return $this->respond(['status' => 400, 'message' => 'Failed to delete Work Type'], 400);
            }

            return $this->respond([
                'status' => 200,
                'message' => 'Work Type Deleted Successfully'
            ], 200);
        } catch (Exception $e) {
            return $this->respond([
                'status' => 500,
                'message' => 'Failed to delete Work Type',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    // ✅ Private function to delete image by path
    private function deleteImageByPath($imagePath)
    {
        try {

            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        } catch (Exception $e) {
            // Handle any issues that may arise while deleting the image file
        }
    }
    public function changeStatus($id)
    {
        $status = $this->request->getVar('status'); // Get status from request

        if (!in_array($status, ['0', '1'])) {
            return $this->failValidationErrors('Invalid status value. Use 1 (active) or 0 (inactive).');
        }

        if (!$this->workTypeModel->find($id)) {
            return $this->failNotFound('Work Type not found.');
        }

        $this->workTypeModel->update($id, ['status' => $status]);

        return $this->respond([
            'status' => 200,
            'message' => 'Status updated successfully',
            'new_status' => (int)$status
        ], 200);
    }
    public function show($id = null)
    {
        try {
            if (!$id) {
                return $this->failValidationErrors('ID is required');
            }

            // Fetch work type details
            $workType = $this->workTypeModel
                ->select('work_types.*, services.name as service_name')
                ->join('services', 'services.id = work_types.service_id', 'left')
                ->where('work_types.id', $id)
                ->first();

            if (!$workType) {
                return $this->failNotFound('Work Type not found.');
            }

            // Fetch associated room_ids
            $roomIds = $this->workTypeRoomModel
                ->where('work_type_id', $id)
                ->select('room_id')
                ->findAll();

            // Convert to array of room_ids
            $workType['room_ids'] = array_column($roomIds, 'room_id');

            return $this->respond([
                'status' => 200,
                'message' => 'Data retrieved successfully',
                'data' => $workType
            ], 200);
        } catch (Exception $e) {
            return $this->respond([
                'status' => 500,
                'message' => 'Failed to retrieve Work Type',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function findByServiceAndRoom($serviceId = null, $roomId = null)
    {
        try {
            if (!$serviceId || !$roomId) {
                return $this->failValidationErrors('Service ID and Room ID are required.');
            }

            $workTypes = $this->workTypeModel->findByServiceAndRoom($serviceId, $roomId);

            if (empty($workTypes)) {
                return $this->failNotFound('No Work Types found for the given Service and Room.');
            }

            return $this->respond([
                'status' => 200,
                'message' => 'Data retrieved successfully',
                'data' => $workTypes
            ], 200);
        } catch (Exception $e) {
            return $this->respond([
                'status' => 500,
                'message' => 'Failed to retrieve Work Types',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
