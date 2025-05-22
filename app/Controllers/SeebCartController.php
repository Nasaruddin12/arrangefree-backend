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
    public function index($userId = null)
    {
        try {
            // $userId = $this->request->getGet('user_id');

            $cartItems = $this->model
                ->select('seeb_cart.*, services.image as service_image, services.name as service_name')
                ->join('services', 'services.id = seeb_cart.service_id', 'left')
                ->orderBy('seeb_cart.created_at', 'DESC'); // Order by latest added first

            if (!empty($userId)) {
                $cartItems->where('seeb_cart.user_id', $userId);
            }

            $cartItems = $cartItems->findAll();

            if (empty($cartItems)) {
                return $this->respond([
                    'status'  => 200,
                    'message' => 'No cart items found',
                    'data'    => []
                ], 200);
            }

            return $this->respond([
                'status' => 200,
                'message' => 'Cart items retrieved successfully',
                'data' => $cartItems
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
                af_customers.name AS user_name, 
                af_customers.email AS user_email, 
                af_customers.mobile_no AS user_phone, 
                COUNT(seeb_cart.id) AS total_items, 
                SUM(seeb_cart.amount) AS total_amount, 
                MAX(seeb_cart.created_at) AS latest_cart_date
            ")
                ->join('af_customers', 'af_customers.id = seeb_cart.user_id', 'left');

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
                    ->like('af_customers.name', $search)
                    ->orLike('af_customers.email', $search)
                    ->orLike('af_customers.mobile_no', $search)
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
                ->select('seeb_cart.*, services.image as service_image')
                ->join('services', 'services.id = seeb_cart.service_id', 'left')
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

    // ✅ Save/Update cart item
    // public function save()
    // {
    //     try {
    //         $data = $this->request->getJSON(true);

    //         if (empty($data['user_id']) || empty($data['service_id'])) {
    //             return $this->fail('User ID and Service ID are required', 400);
    //         }

    //         // Check if rate_type and value fields exist
    //         if (!isset($data['rate_type']) || !isset($data['value'])) {
    //             return $this->fail('Rate Type and Value are required', 400);
    //         }

    //         // Insert new record (allowing multiple entries for the same user_id & service_id)
    //         $this->model->insert($data);

    //         return $this->respondCreated([
    //             'status' => 201,
    //             'message' => 'Cart item added successfully',
    //             'data' => $data
    //         ]);
    //     } catch (\Exception $e) {
    //         return $this->failServerError($e->getMessage());
    //     }
    // }

    public function save()
    {
        try {
            $data = $this->request->getJSON(true);

            if (empty($data['user_id']) || empty($data['service_id'])) {
                return $this->fail('User ID and Service ID are required', 400);
            }

            if (!isset($data['rate_type']) || !isset($data['value'])) {
                return $this->fail('Rate Type and Value are required', 400);
            }

            // Base area calculation (e.g., 10X12 = 120 sqft)
            $area = 1;
            if ($data['rate_type'] === 'square_feet') {
                $value = strtoupper(trim($data['value']));
                if (strpos($value, 'X') !== false) {
                    // Format: 10X12
                    [$w, $h] = explode('X', $value);
                    $area = floatval($w) * floatval($h);
                } else {
                    // Format: 120 (already in sqft)
                    $area = floatval($value);
                }
            }

            // Base service amount
            $rate = floatval($data['rate'] ?? 0);
            $baseAmount = $data['rate_type'] === 'square_feet' ? ($area * $rate) : ($data['value'] * $rate);

            // Addon amount calculation
            $addonTotal = 0;
            $addons = is_array($data['addons']) ? $data['addons'] : json_decode($data['addons'] ?? '[]', true);

            foreach ($addons as $addon) {
                $qty = floatval($addon['qty'] ?? 0);
                $price = floatval($addon['price'] ?? 0);

                // Always treat qty as a raw number (square feet or unit), and multiply directly
                $addonTotal += $qty * $price;
            }

            // Final total amount
            $totalAmount = $baseAmount + $addonTotal;

            $data['amount'] = $totalAmount;
            $data['addons'] = json_encode($addons);
            $data['created_at'] = date('Y-m-d H:i:s');

            $this->model->insert($data);

            return $this->respondCreated([
                'status' => 201,
                'message' => 'Cart item added successfully',
                'data' => $data
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

            // Validate required fields
            if (empty($data['user_id']) || empty($data['service_id'])) {
                return $this->fail('User ID and Service ID are required', 400);
            }

            if (!isset($data['rate_type']) || !isset($data['value'])) {
                return $this->fail('Rate Type and Value are required', 400);
            }

            // Area calculation
            $area = 1;
            if ($data['rate_type'] === 'square_feet') {
                $value = strtoupper(trim($data['value']));
                if (strpos($value, 'X') !== false) {
                    [$w, $h] = explode('X', $value);
                    $area = floatval($w) * floatval($h);
                } else {
                    $area = floatval($value);
                }
            }

            // Base amount
            $rate = floatval($data['rate'] ?? 0);
            $baseAmount = $data['rate_type'] === 'square_feet' ? ($area * $rate) : ($data['value'] * $rate);

            // Addon total
            $addonTotal = 0;
            $addons = is_array($data['addons']) ? $data['addons'] : json_decode($data['addons'] ?? '[]', true);

            foreach ($addons as $addon) {
                $qty = floatval($addon['qty'] ?? 0);
                $price = floatval($addon['price'] ?? 0);
                $addonTotal += $qty * $price;
            }

            // Final amount
            $totalAmount = $baseAmount + $addonTotal;

            $data['amount'] = $totalAmount;
            $data['addons'] = json_encode($addons);
            $data['updated_at'] = date('Y-m-d H:i:s');

            $this->model->update($id, $data);

            return $this->respond([
                'status' => 200,
                'message' => 'Cart item updated successfully',
                'data' => $data
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
    public function uploadImages()
    {
        try {
            $images  = $this->request->getFiles(); // Multiple images
            $uploadDirectory = 'public/uploads/reference-image/'; // Define your upload path
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
