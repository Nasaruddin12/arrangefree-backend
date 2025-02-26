<?php

namespace App\Controllers;

use App\Models\SeebCartModel;
use CodeIgniter\RESTful\ResourceController;

class SeebCartController extends ResourceController
{
    protected $modelName = SeebCartModel::class;
    protected $format    = 'json';

    // ✅ Get all cart items (or filter by user_id)
    public function index()
    {
        try {
            $userId = $this->request->getGet('user_id');

            $cartItems = $this->model
                ->select('seeb_cart.*, services.image as service_image')
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
    
            // Insert new record (allowing multiple entries for same user_id & service_id)
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
}
