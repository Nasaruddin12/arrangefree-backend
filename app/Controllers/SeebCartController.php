<?php

namespace App\Controllers;

use App\Models\SeebCartModel;
use CodeIgniter\RESTful\ResourceController;

class SeebCartController extends ResourceController
{
    protected $modelName = SeebCartModel::class;
    protected $format    = 'json';

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
                    'status' => 404,
                    'message' => 'No cart items found'
                ], 404);
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
            $cartItems = $this->model
                ->select("
                seeb_cart.user_id, 
                af_customers.name as user_name, 
                af_customers.email as user_email, 
                af_customers.mobile_no as user_phone, 
                COUNT(seeb_cart.id) as total_items, 
                SUM(seeb_cart.amount) as total_amount, 
                MAX(seeb_cart.created_at) as latest_cart_date
            ")
                ->join('af_customers', 'af_customers.id = seeb_cart.user_id', 'left') // Join to get user details
                ->groupBy("seeb_cart.user_id") // Group by user ID
                ->orderBy("latest_cart_date", "DESC") // Order by latest cart first
                ->findAll();

            if (empty($cartItems)) {
                return $this->respond([
                    'status' => 404,
                    'message' => 'No cart items found'
                ], 404);
            }

            return $this->respond([
                'status' => 200,
                'message' => 'Cart items grouped by user retrieved successfully',
                'data' => $cartItems
            ], 200);
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
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
    public function save()
    {
        try {
            $data = $this->request->getJSON(true);

            if (empty($data['user_id']) || empty($data['service_id'])) {
                return $this->fail('User ID and Service ID are required', 400);
            }

            // Check if rate_type and value fields exist
            if (!isset($data['rate_type']) || !isset($data['value'])) {
                return $this->fail('Rate Type and Value are required', 400);
            }

            // Insert new record (allowing multiple entries for the same user_id & service_id)
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
