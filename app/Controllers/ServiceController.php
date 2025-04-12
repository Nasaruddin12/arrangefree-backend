<?php

namespace App\Controllers;


use App\Controllers\BaseController;
use App\Models\ServiceModel;
use App\Models\ServiceRoomModel;
use CodeIgniter\API\ResponseTrait;
use Exception;

class ServiceController extends BaseController
{
    use ResponseTrait;
    protected $serviceModel;
    protected $serviceRoomModel;


    public function __construct()
    {
        $this->serviceModel = new ServiceModel();
        $this->serviceRoomModel  = new ServiceRoomModel();
    }

    // ✅ Create Services
    public function create()
    {
        try {
            $db = \Config\Database::connect();
            $db->transStart(); // Start Transaction

            // Step 1: Insert Services
            $data = [
                'name' => $this->request->getVar('name'),
                'service_type_id' => $this->request->getVar('service_type_id'),
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

            if (!$this->serviceModel->save($data)) {
                return $this->respond(['status' => 400, 'message' => 'Failed to create Services'], 400);
            }

            $serviceId = $this->serviceModel->getInsertID(); // Get the last inserted ID

            // Step 2: Insert Services Rooms
            $roomIds = $this->request->getVar('room_ids'); // Expecting an array of room IDs

            if (!empty($roomIds) && is_array($roomIds)) {

                $roomData = [];

                foreach ($roomIds as $roomId) {
                    $roomData[] = [
                        'service_id' => $serviceId,
                        'room_id' => $roomId
                    ];
                }

                if (!empty($roomData)) {
                    $this->serviceRoomModel->insertBatch($roomData);
                }
            }

            $db->transComplete(); // Commit Transaction

            return $this->respond([
                'status' => 201,
                'message' => 'Services Created Successfully',
                'data' => [
                    'service' => $data,
                    'room_ids' => $roomIds ?? []
                ]
            ], 201);
        } catch (Exception $e) {
            return $this->respond([
                'status' => 500,
                'message' => 'An error occurred while creating Services',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    // ✅ Upload Image (Returns only path)
    public function uploadImages()
    {
        try {
            // $validation = \Config\Services::validation();
            // $validation->setRules([
            //     'images' => 'uploaded[images]|max_size[images,2048]|mime_in[images,image/png,image/jpeg,image/jpg]',
            // ]);

            // if (!$validation->withRequest($this->request)->run()) {
            //     return $this->respond([
            //         'status' => 400,
            //         'message' => 'Invalid image files',
            //         'errors' => $validation->getErrors()
            //     ], 400);
            // }

            $imageFiles = $this->request->getFiles();
            $imagePaths = [];

            if (!empty($imageFiles['images'])) {
                foreach ($imageFiles['images'] as $imageFile) {
                    if ($imageFile->isValid() && !$imageFile->hasMoved()) {

                        $imageInfo = getimagesize($imageFile->getTempName());
                        $width = $imageInfo[0];
                        $height = $imageInfo[1];

                        if ($width > 770 || $height > 770) {
                            continue; // skip this file
                        }
                        // Validate size (max 2MB)
                        if ($imageFile->getSize() > 2 * 1024 * 1024) {
                            continue; // skip this file
                        }

                        // Validate mime type
                        $allowedTypes = ['image/png', 'image/jpeg', 'image/jpg'];
                        if (!in_array($imageFile->getMimeType(), $allowedTypes)) {
                            continue; // skip this file
                        }

                        // Move valid image
                        $newName = $imageFile->getRandomName();
                        $imageFile->move('public/uploads/services/', $newName);
                        $imagePaths[] = 'public/uploads/services/' . $newName;
                    }
                }
            }

            if (empty($imagePaths)) {
                return $this->respond([
                    'status' => 400,
                    'message' => 'No images were uploaded'
                ], 400);
            }

            return $this->respond([
                'status' => 200,
                'message' => 'Images uploaded successfully',
                'image_urls' => $imagePaths
            ], 200);
        } catch (Exception $e) {
            return $this->respond([
                'status' => 500,
                'message' => 'Image upload failed',
                'error' => $e->getMessage()
            ], 500);
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

    // ✅ Read all Services
    public function index()
    {
        try {
            $services = $this->serviceModel
                ->select('services.*, service_types.name as service_name, GROUP_CONCAT(service_rooms.room_id) as room_ids') // Fetch room IDs
                ->join('service_types', 'service_types.id = services.service_type_id', 'left') // Joining service table
                ->join('service_rooms', 'service_rooms.service_id = services.id', 'left') // Joining service_rooms
                ->groupBy('services.id') // Grouping to prevent duplicate services
                ->findAll();

            if (empty($services)) {
                return $this->failNotFound('No Services found.');
            }

            // Convert room_ids string to an array
            foreach ($services as &$service) {
                $service['room_ids'] = $service['room_ids'] ? explode(',', $service['room_ids']) : [];
            }

            return $this->respond([
                'status' => 200,
                'message' => 'Data retrieved successfully',
                'data' => $services
            ], 200);
        } catch (Exception $e) {
            return $this->respond([
                'status' => 500,
                'message' => 'Failed to retrieve Services',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    // ✅ Update Services
    public function update($id)
    {
        try {
            $db = \Config\Database::connect();
            $service = $this->serviceModel->find($id);
            if (!$service) {
                return $this->failNotFound('Services not found.');
            }

            $data = [
                'name' => $this->request->getVar('name'),
                'service_type_id' => $this->request->getVar('service_type_id'),
                'rate' => $this->request->getVar('rate'),
                'rate_type' => $this->request->getVar('rate_type'),
                'description' => $this->request->getVar('description'),
                'materials' => $this->request->getVar('materials'),
                'features' => $this->request->getVar('features'),
                'care_instructions' => $this->request->getVar('care_instructions'),
                'warranty_details' => $this->request->getVar('warranty_details'),
                'quality_promise' => $this->request->getVar('quality_promise'),
                'status' => $this->request->getVar('status'),
                'image' => $this->request->getVar('image') ?? $service['image'], // Keep old image if not provided
            ];

            // Start transaction
            $db->transStart();

            // Update the work_type record
            $this->serviceModel->update($id, $data);

            // Update service_rooms if room_ids are provided
            $roomIds = $this->request->getVar('room_ids'); // Expected as an array [1, 2, 3]
            if (!empty($roomIds) && is_array($roomIds)) {
                if (!$this->serviceRoomModel->updateServiceRooms($id, $roomIds)) {
                    // Rollback if updating service_rooms fails
                    $db->transRollback();
                    return $this->respond(['status' => 400, 'message' => 'Failed to update Services Rooms'], 400);
                }
            }

            // Commit transaction
            $db->transComplete();

            if (!$db->transStatus()) {
                return $this->respond(['status' => 400, 'message' => 'Failed to update Services'], 400);
            }

            return $this->respond([
                'status' => 200,
                'message' => 'Services Updated Successfully',
                'data' => $data
            ], 200);
        } catch (Exception $e) {
            return $this->respond([
                'status' => 500,
                'message' => 'Failed to update Services',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    // ✅ Delete Services
    public function delete($id)
    {
        try {
            $db = \Config\Database::connect();
            $service = $this->serviceModel->find($id);

            if (!$service) {
                return $this->failNotFound('Services not found.');
            }

            // Start transaction
            $db->transStart();

            // Step 1: Delete related service_rooms
            $this->serviceRoomModel->where('service_id', $id)->delete();

            // Step 2: Delete the work_type image if exists
            if (!empty($service['image'])) {
                $this->deleteImageByPath($service['image']);
            }

            // Step 3: Delete the work_type itself
            $this->serviceModel->delete($id);

            // Commit transaction
            $db->transComplete();

            if (!$db->transStatus()) {
                return $this->respond(['status' => 400, 'message' => 'Failed to delete Services'], 400);
            }

            return $this->respond([
                'status' => 200,
                'message' => 'Services Deleted Successfully'
            ], 200);
        } catch (Exception $e) {
            return $this->respond([
                'status' => 500,
                'message' => 'Failed to delete Services',
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

        if (!$this->serviceModel->find($id)) {
            return $this->failNotFound('Services not found.');
        }

        $this->serviceModel->update($id, ['status' => $status]);

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

            // Fetch services details
            $service = $this->serviceModel
                ->select('services.*, service_types.name as service_name')
                ->join('service_types', 'service_types.id = services.service_type_id', 'left')
                ->where('services.id', $id)
                ->first();

            if (!$service) {
                return $this->failNotFound('Services not found.');
            }

            // Fetch associated room_ids
            $roomIds = $this->serviceRoomModel
                ->where('service_id', $id)
                ->select('room_id')
                ->findAll();

            // Convert to array of room_ids
            $service['room_ids'] = array_column($roomIds, 'room_id');

            return $this->respond([
                'status' => 200,
                'message' => 'Data retrieved successfully',
                'data' => $service
            ], 200);
        } catch (Exception $e) {
            return $this->respond([
                'status' => 500,
                'message' => 'Failed to retrieve Services',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function findByServiceTypeAndRoom($serviceTypeId = null, $roomId = null)
    {
        try {
            if (!$serviceTypeId || !$roomId) {
                return $this->failValidationErrors('Service ID and Room ID are required.');
            }

            $services = $this->serviceModel->findByServiceTypeAndRoom($serviceTypeId, $roomId);

            if (empty($services)) {
                return $this->failNotFound('No Services found for the given Service and Room.');
            }

            return $this->respond([
                'status' => 200,
                'message' => 'Data retrieved successfully',
                'data' => $services
            ], 200);
        } catch (Exception $e) {
            return $this->respond([
                'status' => 500,
                'message' => 'Failed to retrieve Services',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
