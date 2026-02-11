<?php

namespace App\Controllers;

use App\Models\CustomerAddressModel;
use CodeIgniter\RESTful\ResourceController;

class AddressController extends ResourceController
{
    protected $modelName = CustomerAddressModel::class;
    protected $format    = 'json';

    // ✅ Get all addresses (or filter by user_id)
    public function index($userId = null)
    {
        try {
            $addresses = $this->model->orderBy('is_default', 'DESC');

            if (!empty($userId)) {
                $addresses->where('user_id', $userId);
            }

            $addresses = $addresses->findAll();

            if (empty($addresses)) {
                return $this->respond([
                    'status'  => 404,
                    'message' => 'No addresses found',
                    'data'    => []
                ], 404);
            }

            return $this->respond([
                'status'  => 200,
                'message' => 'Addresses retrieved successfully',
                'data'    => $addresses
            ], 200);
        } catch (\Exception $e) {
            return $this->failServerError('An error occurred while fetching addresses.');
        }
    }

    // ✅ Get a single address by ID
    public function show($id = null)
    {
        try {
            if (!$id) {
                return $this->failValidationErrors('Address ID is required');
            }

            $address = $this->model->find($id);

            if (!$address) {
                return $this->failNotFound('Address not found');
            }

            return $this->respond([
                'status'  => 200,
                'message' => 'Address retrieved successfully',
                'data'    => $address
            ], 200);
        } catch (\Exception $e) {
            return $this->failServerError('An error occurred while fetching the address.');
        }
    }

    // ✅ Add a new address
    public function create()
    {
        try {
            $data = $this->request->getJSON(true);

            if (empty($data['user_id']) || empty($data['house']) || empty($data['address'])) {
                return $this->failValidationErrors('User ID, House, and Address are required');
            }

            if (!isset($data['address_label'])) {
                $data['address_label'] = 'Home'; // Default to 'Home' if not provided
            }

            // If is_default is set to 1, update other addresses for the user to is_default = 0
            if (isset($data['is_default']) && $data['is_default'] == 1) {
                $this->model->where('user_id', $data['user_id'])->set(['is_default' => 0])->update();
            }

            $this->model->insert($data);

            return $this->respondCreated([
                'status'  => 201,
                'message' => 'Address added successfully',
                'data'    => $data
            ]);
        } catch (\Exception $e) {
            return $this->failServerError('Failed to add address.');
        }
    }

    // ✅ Update an address
    public function update($id = null)
    {
        try {
            if (!$id) {
                return $this->failValidationErrors('Address ID is required');
            }

            $data = $this->request->getJSON(true);
            $address = $this->model->find($id);

            if (!$address) {
                return $this->failNotFound('Address not found');
            }

            // If updating to is_default, reset all other addresses for the user
            if (isset($data['is_default']) && $data['is_default'] == 1) {
                $this->model->where('user_id', $address['user_id'])->set(['is_default' => 0])->update();
            }

            $this->model->update($id, $data);

            return $this->respond([
                'status'  => 200,
                'message' => 'Address updated successfully',
                'data'    => $data
            ], 200);
        } catch (\Exception $e) {
            return $this->failServerError('Failed to update address.');
        }
    }

    // ✅ Delete an address
    public function delete($id = null)
    {
        try {
            $address = $this->model->find($id);

            if (!$address) {
                return $this->failNotFound('Address not found');
            }

            $this->model->delete($id);
            return $this->respondDeleted([
                'status'  => 200,
                'message' => 'Address deleted successfully'
            ]);
        } catch (\Exception $e) {
            return $this->failServerError('Failed to delete address.');
        }
    }

    // ✅ Get the default address for a user
    public function getDefault($user_id)
    {
        try {
            $defaultAddress = $this->model
                ->where('user_id', $user_id)
                ->where('is_default', 1)
                ->first();

            if (!$defaultAddress) {
                return $this->failNotFound('No default address found for this user');
            }

            return $this->respond([
                'status'  => 200,
                'message' => 'Default address retrieved successfully',
                'data'    => $defaultAddress
            ], 200);
        } catch (\Exception $e) {
            return $this->failServerError('An error occurred while fetching the default address.');
        }
    }
    public function changeDefault($id = null)
{
    try {
        if (!$id) {
            return $this->fail('Address ID is required', 400);
        }

        // Find the address
        $address = $this->model->find($id);
        if (!$address) {
            return $this->failNotFound('Address not found');
        }

        $userId = $address['user_id'];

        // Reset all addresses for the user to is_default = 0
        $this->model->where('user_id', $userId)->set(['is_default' => 0])->update();

        // Set the selected address as default
        $this->model->update($id, ['is_default' => 1]);

        return $this->respond([
            'status'  => 200,
            'message' => 'Default address updated successfully',
            'data'    => ['address_id' => $id, 'user_id' => $userId, 'is_default' => 1]
        ], 200);

    } catch (\Exception $e) {
        return $this->failServerError('Failed to update default address.');
    }
}

}
