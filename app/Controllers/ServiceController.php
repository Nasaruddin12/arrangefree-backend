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

    public function create()
    {
        try {
            // Validate input
            $validation = \Config\Services::validation();
            $validation->setRules([
                'name' => 'required|string|max_length[255]',
                'service_type_id' => 'required|integer',
                'rate' => 'required|numeric',
                'rate_type' => 'required|in_list[unit, square_feet, running_feet, running_meter, points, sqft]',
                'partner_price' => 'permit_empty|numeric',
                'status' => 'permit_empty|in_list[active, inactive]',
                'slug' => 'permit_empty|string|max_length[255]',
            ]);

            if (!$validation->withRequest($this->request)->run()) {
                return $this->respond([
                    'status' => 400,
                    'message' => 'Validation failed',
                    'errors' => $validation->getErrors()
                ], 400);
            }

            $db = \Config\Database::connect();
            $db->transStart();

            // Step 1: Create Service
            $data = [
                'name'                => $this->request->getVar('name'),
                'service_type_id'     => $this->request->getVar('service_type_id'),
                'rate'                => $this->request->getVar('rate'),
                'rate_type'           => $this->request->getVar('rate_type'),
                'description'         => $this->request->getVar('description'),
                'materials'           => $this->request->getVar('materials'),
                'features'            => $this->request->getVar('features'),
                'care_instructions'   => $this->request->getVar('care_instructions'),
                'warranty_details'    => $this->request->getVar('warranty_details'),
                'quality_promise'     => $this->request->getVar('quality_promise'),
                'status'              => $this->request->getVar('status'),
                'image'               => $this->request->getVar('image'),
                'primary_key'         => $this->request->getVar('primary_key'),
                'secondary_key'       => $this->request->getVar('secondary_key'),
                'partner_price'       => $this->request->getVar('partner_price'),
                'with_material'       => $this->request->getVar('with_material') ?? false,
                'slug'                => $this->request->getVar('slug'),
                'status'              => $this->request->getVar('status') ?? 'inactive', // Default to inactive if not provided
            ];

            if (!$this->serviceModel->save($data)) {
                return $this->respond(['status' => 400, 'message' => 'Failed to create service'], 400);
            }

            $serviceId = $this->serviceModel->getInsertID();

            // Step 2: Insert Service-Room Relations
            $roomIds = $this->request->getVar('room_ids'); // array
            if (!empty($roomIds) && is_array($roomIds)) {
                $roomData = [];
                foreach ($roomIds as $roomId) {
                    $roomData[] = [
                        'service_id' => $serviceId,
                        'room_id'    => $roomId
                    ];
                }
                $this->serviceRoomModel->insertBatch($roomData);
            }

            // Step 3: Insert Add-ons
            $addons = $this->request->getJSON(true)['addons'] ?? []; // Expecting array of objects

            if (!empty($addons) && is_array($addons)) {
                $addonModel = new \App\Models\ServiceAddonModel();
                $addonBatch = [];

                foreach ($addons as $addon) {
                    $addonBatch[] = [
                        'service_id'   => $serviceId,
                        'group_name'   => $addon['group_name'] ?? null,
                        'is_required'  => $addon['is_required'] ?? false,
                        'name'         => $addon['name'],
                        'price_type'   => $addon['price_type'], // 'unit' or 'square_feet'
                        'qty'          => $addon['qty'],
                        'price'        => $addon['price'],
                        'partner_price' => $addon['partner_price'] ?? null,
                        'description'  => $addon['description'] ?? null,
                        'image'        => $addon['image'] ?? null,
                        'status'       => $addon['status'] ?? '1', // Default to inactive if not provided
                    ];
                }

                if (!empty($addonBatch)) {
                    $addonModel->insertBatch($addonBatch);
                }
            }

            $db->transComplete();

            return $this->respond([
                'status' => 201,
                'message' => 'Service created with add-ons and room mappings',
                'data' => [
                    'service_id' => $serviceId,
                    'rooms' => $roomIds ?? [],
                    'addons' => $addons ?? []
                ]
            ], 201);
        } catch (\Exception $e) {
            $db->transRollback();
            return $this->respond([
                'status' => 500,
                'message' => 'Error occurred during creation',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    // ✅ Upload Image (Returns only path)
    public function uploadImages()
    {
        try {
            $imageFiles = $this->request->getFiles();
            $imagePaths = [];

            if (!empty($imageFiles['images'])) {
                foreach ($imageFiles['images'] as $imageFile) {
                    if ($imageFile->isValid() && !$imageFile->hasMoved()) {
                        // Validate mime type
                        $allowedTypes = [
                            'image/png',
                            'image/jpeg',
                            'image/jpg',
                            'video/mp4',
                            'video/avi',
                            'video/mov',
                            'video/quicktime',
                            'video/x-msvideo',
                        ];

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
            // If a search query is provided on the /services endpoint, delegate to search()
            if ($this->request->getGet('search') !== null) {
                return $this->search();
            }
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
            $service = $this->serviceModel->find($id);
            if (!$service) {
                return $this->failNotFound('Service not found.');
            }

            // Validate input
            $validation = \Config\Services::validation();
            $validation->setRules([
                'name' => 'permit_empty|string|max_length[255]',
                'service_type_id' => 'permit_empty|integer',
                'rate' => 'permit_empty|numeric',
                'rate_type' => 'permit_empty|in_list[unit,square_feet]',
                'partner_price' => 'permit_empty|numeric',
                'status' => 'permit_empty|in_list[0,1]',
                'slug' => 'permit_empty|string|max_length[255]',
            ]);

            if (!$validation->withRequest($this->request)->run()) {
                return $this->respond([
                    'status' => 400,
                    'message' => 'Validation failed',
                    'errors' => $validation->getErrors()
                ], 400);
            }

            $db = \Config\Database::connect();

            $data = [
                'name'               => $this->request->getVar('name'),
                'service_type_id'    => $this->request->getVar('service_type_id'),
                'rate'               => $this->request->getVar('rate'),
                'rate_type'          => $this->request->getVar('rate_type'),
                'description'        => $this->request->getVar('description'),
                'materials'          => $this->request->getVar('materials'),
                'features'           => $this->request->getVar('features'),
                'care_instructions'  => $this->request->getVar('care_instructions'),
                'warranty_details'   => $this->request->getVar('warranty_details'),
                'quality_promise'    => $this->request->getVar('quality_promise'),
                'status'             => $this->request->getVar('status'),
                'image'              => $this->request->getVar('image') ?? $service['image'],
                'primary_key'        => $this->request->getVar('primary_key'),
                'secondary_key'      => $this->request->getVar('secondary_key'),
                'partner_price'      => $this->request->getVar('partner_price'),
                'with_material'      => $this->request->getVar('with_material') ?? false,
                'slug'               => $this->request->getVar('slug'),
            ];

            $db->transStart();

            // Update service
            $this->serviceModel->update($id, $data);

            // ✅ Update rooms
            $roomIds = $this->request->getVar('room_ids');
            if (!empty($roomIds) && is_array($roomIds)) {
                if (!$this->serviceRoomModel->updateServiceRooms($id, $roomIds)) {
                    $db->transRollback();
                    return $this->respond(['status' => 400, 'message' => 'Failed to update Services Rooms'], 400);
                }
            }

            // ✅ Addon handling
            $addons = $this->request->getJSON(true)['addons'] ?? [];

            $addonModel = new \App\Models\ServiceAddonModel();

            $existingAddons = $addonModel->where('service_id', $id)->findAll();
            $existingAddonIds = array_column($existingAddons, 'id');
            $incomingAddonIds = [];

            if (!empty($addons) && is_array($addons)) {
                foreach ($addons as $addon) {
                    $addonData = [
                        'service_id'  => $id,
                        'group_name'  => $addon['group_name'] ?? null,
                        'is_required' => $addon['is_required'] ?? false,
                        'name'        => $addon['name'],
                        'price_type'  => $addon['price_type'],
                        'qty'         => $addon['qty'],
                        'price'       => $addon['price'],
                        'partner_price' => $addon['partner_price'] ?? null,
                        'description' => $addon['description'] ?? null,
                        'image'       => $addon['image'] ?? null,
                    ];

                    if (!empty($addon['id'])) {
                        $incomingAddonIds[] = $addon['id'];
                        $addonModel->update($addon['id'], $addonData);
                    } else {
                        $addonModel->insert($addonData);
                        $incomingAddonIds[] = $addonModel->getInsertID();
                    }
                }
            }

            // ✅ Delete removed addons
            $idsToDelete = array_diff($existingAddonIds, $incomingAddonIds);
            if (!empty($idsToDelete)) {
                $addonModel->whereIn('id', $idsToDelete)->delete();
            }

            $db->transComplete();

            if (!$db->transStatus()) {
                return $this->respond(['status' => 400, 'message' => 'Failed to update Services', 'error' => $db->error()], 400);
            }

            return $this->respond([
                'status' => 200,
                'message' => 'Service updated successfully',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return $this->respond([
                'status' => 500,
                'message' => 'Failed to update Service',
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

            // Fetch service details
            $service = $this->serviceModel
                ->select('services.*, service_types.name as service_name')
                ->join('service_types', 'service_types.id = services.service_type_id', 'left')
                ->where('services.id', $id)
                ->first();

            if (!$service) {
                return $this->failNotFound('Service not found.');
            }

            $originalPrice = (float) ($service['price'] ?? $service['rate']) ?? 0;

            // 2️⃣ Room IDs
            $roomIds = $this->serviceRoomModel
                ->where('service_id', $service['id'])
                ->select('room_id')
                ->findAll();

            $service['room_ids'] = array_column($roomIds, 'room_id');

            // 3️⃣ Get Best Active Offer
            $offerModel = new \App\Models\ServiceOfferModel();

            $bestOffer = $offerModel->getActiveOffer(
                $service['id'],
                $service['service_type_id']
            );

            $discountedServicePrice = $offerModel->applyDiscount(
                $originalPrice,
                $bestOffer
            );

            $service['original_price'] = $originalPrice;
            $service['discounted_rate'] = round($discountedServicePrice, 2);
            $service['offer'] = $bestOffer ?? null;

            // 4️⃣ Fetch Addons
            $addonModel = new \App\Models\ServiceAddonModel();

            $addons = $addonModel
                ->where('service_id', $service['id'])
                ->orderBy('group_name', 'asc')
                ->findAll();

            foreach ($addons as &$addon) {

                $addonOriginal = floatval($addon['price']);

                // Apply SAME service offer to addons
                $addonDiscounted = $offerModel->applyDiscount(
                    $addonOriginal,
                    $bestOffer
                );

                $addon['original_price'] = $addonOriginal;
                $addon['discounted_price'] = round($addonDiscounted, 2);
                $addon['offer'] = $bestOffer ?? null;
            }

            $service['addons'] = $addons;

            return $this->respond([
                'status' => 200,
                'message' => 'Service details retrieved successfully',
                'data' => $service
            ]);
        } catch (\Exception $e) {

            return $this->respond([
                'status' => 500,
                'message' => 'Failed to retrieve service details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get service details by slug
     * @param string $slug Service slug
     */
    // public function showBySlug($slug = null)
    // {
    //     try {
    //         if (!$slug) {
    //             return $this->failValidationErrors('Slug is required');
    //         }

    //         // Fetch service details by slug
    //         $service = $this->serviceModel
    //             ->select('services.*, service_types.name as service_name')
    //             ->join('service_types', 'service_types.id = services.service_type_id', 'left')
    //             ->where('services.slug', $slug)
    //             ->first();

    //         if (!$service) {
    //             return $this->failNotFound('Service not found.');
    //         }

    //         // Fetch associated room IDs
    //         $roomIds = $this->serviceRoomModel
    //             ->where('service_id', $service['id'])
    //             ->select('room_id')
    //             ->findAll();

    //         $service['room_ids'] = array_column($roomIds, 'room_id');

    //         // Fetch all add-ons (flat list)
    //         $addonModel = new \App\Models\ServiceAddonModel();
    //         $addons = $addonModel
    //             ->where('service_id', $service['id'])
    //             ->orderBy('group_name', 'asc')
    //             ->findAll();

    //         $service['addons'] = $addons;

    //         return $this->respond([
    //             'status' => 200,
    //             'message' => 'Service details retrieved successfully',
    //             'data' => $service
    //         ], 200);
    //     } catch (\Exception $e) {
    //         return $this->respond([
    //             'status' => 500,
    //             'message' => 'Failed to retrieve service details',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function showBySlug($slug = null)
    {
        try {

            if (!$slug) {
                return $this->failValidationErrors('Slug is required');
            }

            // 1️⃣ Fetch Service
            $service = $this->serviceModel
                ->select('services.*, service_types.name as service_name')
                ->join('service_types', 'service_types.id = services.service_type_id', 'left')
                ->where('services.slug', $slug)
                ->first();

            if (!$service) {
                return $this->failNotFound('Service not found.');
            }

            $originalPrice = (float) ($service['price'] ?? $service['rate']) ?? 0;

            // 2️⃣ Room IDs
            $roomIds = $this->serviceRoomModel
                ->where('service_id', $service['id'])
                ->select('room_id')
                ->findAll();

            $service['room_ids'] = array_column($roomIds, 'room_id');

            // 3️⃣ Get Best Active Offer
            $offerModel = new \App\Models\ServiceOfferModel();

            $bestOffer = $offerModel->getActiveOffer(
                $service['id'],
                $service['service_type_id']
            );

            $discountedServicePrice = $offerModel->applyDiscount(
                $originalPrice,
                $bestOffer
            );

            $service['original_price'] = $originalPrice;
            $service['discounted_rate'] = round($discountedServicePrice, 2);
            $service['offer'] = $bestOffer ?? null;

            // 4️⃣ Fetch Addons
            $addonModel = new \App\Models\ServiceAddonModel();

            $addons = $addonModel
                ->where('service_id', $service['id'])
                ->orderBy('group_name', 'asc')
                ->findAll();

            foreach ($addons as &$addon) {

                $addonOriginal = floatval($addon['price']);

                // Apply SAME service offer to addons
                $addonDiscounted = $offerModel->applyDiscount(
                    $addonOriginal,
                    $bestOffer
                );

                $addon['original_price'] = $addonOriginal;
                $addon['discounted_price'] = round($addonDiscounted, 2);
                $addon['offer'] = $bestOffer ?? null;
            }

            $service['addons'] = $addons;

            return $this->respond([
                'status' => 200,
                'message' => 'Service details retrieved successfully',
                'data' => $service
            ]);
        } catch (\Exception $e) {

            return $this->respond([
                'status' => 500,
                'message' => 'Failed to retrieve service details',
                'error' => $e->getMessage()
            ], 500);
        }
    }




    /**
     * Find services by numeric service_type_id and room_id (backward compatible with iOS/Android apps)
     */
    public function findByServiceTypeAndRoom($serviceTypeId = null, $roomId = null)
    {
        try {
            if (!$serviceTypeId || !$roomId) {
                return $this->failValidationErrors('Service Type ID and Room ID are required.');
            }

            $services = $this->serviceModel->findByServiceTypeAndRoom($serviceTypeId, $roomId);

            if (empty($services)) {
                return $this->respond([
                    'data' => [],
                    'status' => 404,
                    'message' => 'No Services found for the given Service Type and Room.'
                ], 200);
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

    /**
     * Find services by service_type_slug and room_slug (new slug-based endpoint)
     */
    public function findByServiceTypeAndRoomSlug($serviceTypeSlug = null, $roomSlug = null)
    {
        try {
            if (!$serviceTypeSlug || !$roomSlug) {
                return $this->failValidationErrors('Service Type Slug and Room Slug are required.');
            }

            $services = $this->serviceModel->findByServiceTypeAndRoomSlug($serviceTypeSlug, $roomSlug);

            if (empty($services)) {
                return $this->respond([
                    'data' => [],
                    'status' => 404,
                    'message' => 'No Services found for the given Service Type and Room.'
                ], 200);
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

    /**
     * Search services by name or description
     */
    public function search()
    {
        try {
            // Ensure we read from GET explicitly and trim input
            $keyword = $this->request->getGet('search');
            $keyword = is_string($keyword) ? trim($keyword) : '';

            if ($keyword === '' || strlen($keyword) < 2) {
                return $this->failValidationErrors('Search keyword must be at least 2 characters long.');
            }

            // 1) Try name only
            $services = $this->serviceModel
                ->where('status', 1)
                ->like('name', $keyword)
                ->orderBy('name', 'ASC')
                ->findAll();

            if (!empty($services)) {
                return $this->respond([
                    'status' => 200,
                    'message' => 'Services found (matched name)',
                    'data' => $services
                ], 200);
            }

            // 2) Try description only
            $services = $this->serviceModel
                ->where('status', 1)
                ->like('description', $keyword)
                ->orderBy('name', 'ASC')
                ->findAll();

            if (!empty($services)) {
                return $this->respond([
                    'status' => 200,
                    'message' => 'Services found (matched description)',
                    'data' => $services
                ], 200);
            }

            // 3) Fallback: search both fields (grouped)
            $services = $this->serviceModel
                ->groupStart()
                ->like('name', $keyword)
                ->orLike('description', $keyword)
                ->groupEnd()
                ->where('status', 1)
                ->orderBy('name', 'ASC')
                ->findAll();

            if (empty($services)) {
                return $this->respond([
                    'status' => 404,
                    'message' => 'No services found matching your search.',
                    'data' => []
                ], 200);
            }

            return $this->respond([
                'status' => 200,
                'message' => 'Services found (fallback search)',
                'data' => $services
            ], 200);
        } catch (Exception $e) {
            return $this->respond([
                'status' => 500,
                'message' => 'Failed to search services',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    // public function findByServiceTypeAndRoom($serviceTypeId, $roomId)
    // {
    //     // Fetch the service by type and room
    //     $service = $this->serviceModel
    //         ->where('service_type_id', $serviceTypeId)
    //         ->where('room_id', $roomId)
    //         ->first();

    //     if (!$service) {
    //         return $this->response->setJSON([
    //             'status' => false,
    //             'message' => 'Service not found'
    //         ])->setStatusCode(404);
    //     }

    //     // Fetch active offer for this service, category, or global
    //     $offerModel = new \App\Models\ServiceOfferModel();
    //     $targetModel = new \App\Models\ServiceOfferTargetModel();

    //     $today = date('Y-m-d');

    //     $offer = $offerModel
    //         ->where('is_active', 1)
    //         ->where('start_date <=', $today)
    //         ->where('end_date >=', $today)
    //         ->join('service_offer_targets', 'service_offer_targets.offer_id = service_offers.id')
    //         ->groupStart()
    //         ->where('service_offer_targets.service_id', $service['id'])
    //         ->orWhere('service_offer_targets.category_id', $service['service_type_id'])
    //         ->orWhere('service_offer_targets.target_type', 'global')
    //         ->groupEnd()
    //         ->orderBy('service_offers.priority', 'DESC')
    //         ->orderBy('service_offers.id', 'DESC')
    //         ->first();

    //     // Calculate discounted_rate if offer exists
    //     $discountedRate = null;
    //     if ($offer) {
    //         $price = isset($service['price']) ? $service['price'] : (isset($service['rate']) ? $service['rate'] : 0);
    //         if ($offer['discount_type'] === 'percentage') {
    //             $discountedRate = $price - ($price * $offer['discount_value'] / 100);
    //         } elseif ($offer['discount_type'] === 'flat') {
    //             $discountedRate = $price - $offer['discount_value'];
    //         }
    //         // Add more discount types if needed
    //     }

    //     // Add offer and discounted_rate to response
    //     $service['offer'] = $offer;
    //     $service['discounted_rate'] = $discountedRate;

    //     return $this->response->setJSON([
    //         'status' => true,
    //         'data' => $service
    //     ]);
    // }

    // ...existing code...
}
