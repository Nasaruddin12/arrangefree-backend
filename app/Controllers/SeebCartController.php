<?php

namespace App\Controllers;

use App\Models\SeebCartModel;
use CodeIgniter\RESTful\ResourceController;

class SeebCartController extends ResourceController
{
    protected $modelName = SeebCartModel::class;
    protected $format    = 'json';
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    // ✅ Get all cart items (or filter by user_id)
    // public function index($userId = null)
    // {
    //     try {
    //         $cartItems = $this->model
    //             ->select('
    //                 seeb_cart.*,
    //                 services.image as service_image,
    //                 services.name as service_name,
    //                 services.rate as current_rate,
    //                 rooms.name as room_name,
    //                 service_addons.name as addon_name,
    //                 service_addons.price as addon_price
    //             ')
    //             ->join('services', 'services.id = seeb_cart.service_id', 'left')
    //             ->join('rooms', 'rooms.id = seeb_cart.room_id', 'left')
    //             ->join('service_addons', 'service_addons.id = seeb_cart.addon_id', 'left')
    //             ->orderBy('seeb_cart.created_at', 'DESC');

    //         if (!empty($userId)) {
    //             $cartItems->where('seeb_cart.user_id', $userId);
    //         }

    //         $cartItems = $cartItems->findAll();

    //         if (empty($cartItems)) {
    //             return $this->respond([
    //                 'status'  => 200,
    //                 'message' => 'No cart items found',
    //                 'data'    => []
    //             ], 200);
    //         }

    //         return $this->respond([
    //             'status'  => 200,
    //             'message' => 'Cart items retrieved successfully',
    //             'data'    => $cartItems
    //         ], 200);
    //     } catch (\Exception $e) {
    //         return $this->failServerError($e->getMessage());
    //     }
    // }

    // ✅ Get cart items with services and add-ons hierarchically
    public function index($userId = null)
    {
        try {
            $cartItems = $this->model->getCartItemsHierarchical($userId);

            if (empty($cartItems)) {
                return $this->respond([
                    'status'  => 200,
                    'message' => 'No cart items found',
                    'data'    => []
                ], 200);
            }

            return $this->respond([
                'status'  => 200,
                'message' => 'Cart items retrieved successfully with add-ons',
                'data'    => $cartItems
            ], 200);
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    public function getCartGroupedByUser()
    {
        try {
            // Pagination
            $page  = $this->request->getVar('page') ?? 1;
            $limit = $this->request->getVar('limit') ?? 10;
            $offset = ($page - 1) * $limit;

            // Filters
            $startDate = $this->request->getVar('startDate');
            $endDate   = $this->request->getVar('endDate');
            $filter    = $this->request->getVar('filter'); // today, this_week, this_month
            $search    = $this->request->getVar('search');

            $sortBy  = $this->request->getVar('sort_by') ?? 'created_at';
            $sortDir = $this->request->getVar('sort_dir') ?? 'desc';

            $sortColumn = match ($sortBy) {
                'amount'     => 'SUM(seeb_cart.amount)',
                'created_at' => 'MAX(seeb_cart.created_at)',
                default      => 'MAX(seeb_cart.created_at)'
            };

            $sortDirection = strtolower($sortDir) === 'asc' ? 'ASC' : 'DESC';

            $builder = $this->db->table('seeb_cart')
                ->select("
                    seeb_cart.user_id, 
                    customers.name AS user_name, 
                    customers.email AS user_email, 
                    customers.mobile_no AS user_phone, 
                    COUNT(seeb_cart.id) AS total_items, 
                    SUM(seeb_cart.amount) AS total_amount, 
                    MAX(seeb_cart.created_at) AS latest_cart_date
                ")
                ->join('customers', 'customers.id = seeb_cart.user_id', 'left');

            $builder->orderBy($sortColumn, $sortDirection);

            // Date range
            if ($startDate && $endDate) {
                $builder->where('seeb_cart.created_at >=', $startDate)
                    ->where('seeb_cart.created_at <=', $endDate . ' 23:59:59');
            }

            // Quick filters
            if ($filter === 'today') {
                $builder->where('DATE(seeb_cart.created_at)', date('Y-m-d'));
            } elseif ($filter === 'this_week') {
                $builder->where('YEARWEEK(seeb_cart.created_at, 1) = YEARWEEK(CURDATE(), 1)');
            } elseif ($filter === 'this_month') {
                $builder->where('MONTH(seeb_cart.created_at)', date('m'))
                    ->where('YEAR(seeb_cart.created_at)', date('Y'));
            }

            // Search
            if ($search) {
                $builder->groupStart()
                    ->like('customers.name', $search)
                    ->orLike('customers.email', $search)
                    ->orLike('customers.mobile_no', $search)
                    ->groupEnd();
            }

            // Group by user
            $builder->groupBy("seeb_cart.user_id");

            // Clone for count
            $countQuery = clone $builder;
            $totalRecords = count($countQuery->get()->getResultArray());

            // Apply sort and pagination
            $cartItems = $builder
                ->orderBy($sortColumn, $sortDirection)
                ->limit($limit, $offset)
                ->get()
                ->getResultArray();

            if (empty($cartItems)) {
                return $this->respond([
                    'status'  => 204,
                    'message' => 'No cart items found',
                    'data'    => [],
                    'pagination' => [
                        'current_page'  => (int)$page,
                        'per_page'      => (int)$limit,
                        'total_records' => 0,
                        'total_pages'   => 0
                    ]
                ], 204);
            }

            return $this->respond([
                'status' => 200,
                'message' => 'Cart items grouped by user retrieved successfully',
                'data' => $cartItems,
                'pagination' => [
                    'current_page'  => (int)$page,
                    'per_page'      => (int)$limit,
                    'total_records' => $totalRecords,
                    'total_pages'   => ceil($totalRecords / $limit)
                ]
            ]);
        } catch (\Exception $e) {
            return $this->respond([
                'status' => 500,
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ Get a single cart item by ID
    public function show($id = null)
    {
        try {
            if (!$id) {
                return $this->fail('Cart item ID is required', 400);
            }

            $cartItem = $this->model
                ->select('
                    seeb_cart.*,
                    services.image as service_image,
                    services.rate as current_rate,
                    rooms.name as room_name,
                    service_addons.name as addon_name,
                    service_addons.price as addon_price
                ')
                ->join('services', 'services.id = seeb_cart.service_id', 'left')
                ->join('rooms', 'rooms.id = seeb_cart.room_id', 'left')
                ->join('service_addons', 'service_addons.id = seeb_cart.addon_id', 'left')
                ->where('seeb_cart.id', $id)
                ->first();

            if (!$cartItem) {
                return $this->failNotFound('Cart item not found');
            }

            return $this->respond([
                'status' => 200,
                'message' => 'Cart item retrieved successfully',
                'data' => $cartItem
            ], 200);
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    // ✅ Save cart item
    public function save()
    {
        try {
            $data = $this->request->getJSON(true);

            // Validate required fields
            if (empty($data['user_id']) || empty($data['service_id'])) {
                return $this->fail('User ID and Service ID are required', 400);
            }

            // Validate quantity
            if (empty($data['quantity']) || floatval($data['quantity']) <= 0) {
                return $this->fail('Quantity must be greater than 0', 400);
            }

            // Prepare main service cart data
            $cartData = [
                'user_id' => $data['user_id'],
                'service_id' => $data['service_id'],
                'addon_id' => null, // Main service has no addon
                'service_type_id' => $data['service_type_id'] ?? null,
                'room_id' => $data['room_id'] ?? null,
                'quantity' => floatval($data['quantity']),
                'unit' => $data['unit'] ?? null,
                'rate' => floatval($data['rate'] ?? 0),
                'amount' => floatval($data['amount'] ?? 0),
                'room_length' => $data['room_length'] ?? null,
                'room_width' => $data['room_width'] ?? null,
                'description' => $data['description'] ?? null,
                'reference_image' => $data['reference_image'] ?? null,
                'parent_cart_id' => $data['parent_cart_id'] ?? null
            ];

            // Insert main service cart item
            $parentCartId = $this->model->insert($cartData);
            $addedItems = [$cartData];

            // Handle addons if provided as array
            if (isset($data['addons']) && is_array($data['addons']) && !empty($data['addons'])) {
                foreach ($data['addons'] as $addon) {
                    if (!empty($addon['id']) || !empty($addon['addon_id'])) {
                        $addonCartData = [
                            'user_id' => $data['user_id'],
                            'service_id' => $data['service_id'],
                            'addon_id' => $addon['id'] ?? $addon['addon_id'],
                            'service_type_id' => $data['service_type_id'] ?? null,
                            'room_id' => $data['room_id'] ?? null,
                            'quantity' => floatval($addon['quantity'] ?? $data['quantity']),
                            'unit' => $addon['unit'] ?? $data['unit'] ?? null,
                            'rate' => floatval($addon['rate'] ?? 0),
                            'amount' => floatval($addon['amount'] ?? 0),
                            'room_length' => $data['room_length'] ?? null,
                            'room_width' => $data['room_width'] ?? null,
                            'description' => $addon['description'] ?? null,
                            'reference_image' => $data['reference_image'] ?? null,
                            'parent_cart_id' => $parentCartId
                        ];

                        $this->model->insert($addonCartData);
                        $addedItems[] = $addonCartData;
                    }
                }
            }

            return $this->respondCreated([
                'status' => 201,
                'message' => 'Cart item(s) added successfully',
                'data' => [
                    'parent_cart_id' => $parentCartId,
                    'items_added' => count($addedItems),
                    'items' => $addedItems
                ]
            ]);
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    // ✅ Update a cart item
    public function update($id = null)
    {
        try {
            if (!$id) {
                return $this->fail('Cart item ID is required', 400);
            }

            $data = $this->request->getJSON(true);

            $cartItem = $this->model->find($id);
            if (!$cartItem) {
                return $this->failNotFound('Cart item not found');
            }

            // Prepare data for update
            $updateData = [];
            
            if (isset($data['quantity'])) {
                if (floatval($data['quantity']) <= 0) {
                    return $this->fail('Quantity must be greater than 0', 400);
                }
                $updateData['quantity'] = floatval($data['quantity']);
            }

            if (isset($data['service_id'])) {
                $updateData['service_id'] = $data['service_id'];
            }

            if (isset($data['service_type_id'])) {
                $updateData['service_type_id'] = $data['service_type_id'];
            }

            if (isset($data['room_id'])) {
                $updateData['room_id'] = $data['room_id'];
            }

            if (isset($data['unit'])) {
                $updateData['unit'] = $data['unit'];
            }

            if (isset($data['rate'])) {
                $updateData['rate'] = floatval($data['rate']);
            }

            if (isset($data['amount'])) {
                $updateData['amount'] = floatval($data['amount']);
            }

            if (isset($data['room_length'])) {
                $updateData['room_length'] = $data['room_length'];
            }

            if (isset($data['room_width'])) {
                $updateData['room_width'] = $data['room_width'];
            }

            if (isset($data['description'])) {
                $updateData['description'] = $data['description'];
            }

            if (isset($data['reference_image'])) {
                $updateData['reference_image'] = $data['reference_image'];
            }

            // Update main cart item if there's data to update
            if (!empty($updateData)) {
                $this->model->update($id, $updateData);
            }

            $updatedItems = [$updateData];

            // Handle addons update if provided as array
            if (isset($data['addons']) && is_array($data['addons'])) {
                // Get existing addon cart items for this parent
                $existingAddons = $this->model->where('parent_cart_id', $id)->findAll();
                $existingAddonIds = array_column($existingAddons, 'id');
                $updatedAddonCartIds = [];

                // Update or insert addons
                foreach ($data['addons'] as $addon) {
                    if (!empty($addon['id']) || !empty($addon['addon_id'])) {
                        $addonCartData = [
                            'user_id' => $data['user_id'] ?? $cartItem['user_id'],
                            'service_id' => $data['service_id'] ?? $cartItem['service_id'],
                            'addon_id' => $addon['id'] ?? $addon['addon_id'],
                            'service_type_id' => $data['service_type_id'] ?? $cartItem['service_type_id'],
                            'room_id' => $data['room_id'] ?? $cartItem['room_id'],
                            'quantity' => floatval($addon['quantity'] ?? $data['quantity'] ?? $cartItem['quantity']),
                            'unit' => $addon['unit'] ?? $data['unit'] ?? $cartItem['unit'],
                            'rate' => floatval($addon['rate'] ?? 0),
                            'amount' => floatval($addon['amount'] ?? 0),
                            'room_length' => $data['room_length'] ?? $cartItem['room_length'],
                            'room_width' => $data['room_width'] ?? $cartItem['room_width'],
                            'description' => $addon['description'] ?? null,
                            'reference_image' => $data['reference_image'] ?? $cartItem['reference_image'],
                            'parent_cart_id' => $id
                        ];

                        // Check if this addon already exists for this cart item
                        $existingAddon = null;
                        foreach ($existingAddons as $existing) {
                            if ($existing['addon_id'] == $addonCartData['addon_id']) {
                                $existingAddon = $existing;
                                break;
                            }
                        }

                        if ($existingAddon) {
                            // Update existing addon cart item
                            $this->model->update($existingAddon['id'], $addonCartData);
                            $updatedAddonCartIds[] = $existingAddon['id'];
                            $updatedItems[] = array_merge(['cart_item_id' => $existingAddon['id']], $addonCartData);
                        } else {
                            // Insert new addon cart item
                            $newAddonId = $this->model->insert($addonCartData);
                            $updatedAddonCartIds[] = $newAddonId;
                            $updatedItems[] = array_merge(['cart_item_id' => $newAddonId], $addonCartData);
                        }
                    }
                }

                // Delete addons that are no longer in the updated list
                $addonsToDelete = array_diff($existingAddonIds, $updatedAddonCartIds);
                if (!empty($addonsToDelete)) {
                    $this->model->whereIn('id', $addonsToDelete)->delete();
                }
            }

            return $this->respond([
                'status' => 200,
                'message' => 'Cart item(s) updated successfully',
                'data' => [
                    'cart_id' => $id,
                    'items_updated' => count($updatedItems),
                    'items' => $updatedItems
                ]
            ]);
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    // ✅ Delete a cart item
    public function delete($id = null)
    {
        try {
            $cartItem = $this->model->find($id);

            if (!$cartItem) {
                return $this->failNotFound('Cart item not found');
            }

            $this->model->delete($id);
            return $this->respondDeleted(['message' => 'Cart item deleted successfully']);
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    // ✅ Upload reference images
    public function uploadImages()
    {
        try {
            $images = $this->request->getFiles();
            $uploadDirectory = 'public/uploads/reference-image/';
            $imagePaths = [];

            if (!isset($images['images'])) {
                return $this->respond(['status' => 400, 'message' => 'No images uploaded'], 400);
            }

            foreach ($images['images'] as $file) {
                if ($file->isValid() && !$file->hasMoved()) {
                    $fileName = $file->getRandomName();
                    if ($file->move($uploadDirectory, $fileName)) {
                        $imagePaths[] = $uploadDirectory . $fileName;
                    }
                }
            }

            return $this->respond([
                'status'  => 201,
                'message' => 'Images uploaded successfully',
                'data'    => ['images' => $imagePaths]
            ], 201);
        } catch (\Exception $e) {
            return $this->respond(['status' => 500, 'message' => 'Failed to upload images', 'error' => $e->getMessage()], 500);
        }
    }
}
