<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\CartModel;
use App\Models\ProductImageModel;
use App\Models\ProductModel;
use CodeIgniter\API\ResponseTrait;
use Exception;
use PHPUnit\Framework\Constraint\Count;

class CartController extends BaseController
{
    use ResponseTrait;
    public function createCart()
    {
        try {
            $cartModel = new CartModel();
            $validation = \Config\Services::validation();
            $statusCode = 200;

            $productData = [
                'product_id' => $this->request->getVar('product_id'),
                'customer_id' => $this->request->getVar('customer_id'),
                'quantity' => $this->request->getVar('quantity')
            ];
            $data = $cartModel->where(['product_id' => $productData['product_id'], 'customer_id' => $productData['customer_id']])->first();
            $is_exist = empty($data);
            if ($cartModel->db->error()['code']) {
                throw new Exception($cartModel->db->error()['message'], 500);
            }
            if ($is_exist) {
                $cartModel->insert($productData);
            } else {
                $cartModel->set(['quantity' => $data['quantity'] + $productData['quantity']])->update($data['id']);
            }

            if (!empty($cartModel->errors())) {
                throw new Exception('Validation', 400);
            }

            if ($cartModel->db->error()['code']) {
                throw new Exception($cartModel->db->error()['message'], 500);
            }

            if ($cartModel->db->affectedRows() == 1) {
                $response = [
                    'message' => 'Cart created successfully.',
                    'product_id' => $cartModel->db->insertID(),
                ];
            }
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? ['validation' => $validation->getErrors()] : $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    // Get all products
    public function getCarts()
    {
        $cartModel = new CartModel();
        try {
            $data = $cartModel->findAll();

            if (!empty($cartModel->errors())) {
                throw new Exception('Validation', 400);
            }

            if ($cartModel->db->error()['code']) {
                throw new Exception($cartModel->db->error()['message'], 500);
            }

            $statusCode = 200;
            $response = [
                'data' => $data
            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? $cartModel->errors() : $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    // Update a product by ID
    public function updateCart($id)
    {
        try {
            $cartModel = new CartModel();
            $validation = &$cartModel;
            // $statusCode = 200;

            $userBackendToFrontendAttrs = [
                // 'product_id' => 'product_id',
                // 'customer_id' => 'customer_id',
                'quantity' => 'quantity',
            ];

            $cartData = array();
            foreach ($userBackendToFrontendAttrs as $backendAttr => $frontendAttr) {
                $cartData[$backendAttr] = $this->request->getVar($frontendAttr);
            }
            // $cartData['id'] = $id;

            // var_dump($customerData);
            $cartModel->set($cartData)->update($id); // update the Customer with the given ID
            if (!empty($cartModel->errors())) {
                throw new Exception('Validation', 400);
            }
            if ($cartModel->db->error()['code']) {
                throw new Exception($cartModel->db->error()['message'], 500);
            }
            if ($cartModel->db->affectedRows() == 1) {
                $statusCode = 200;
                $response = [
                    'message' => 'Cart updated successfully.',
                ];
            } else {
                throw new Exception('Nothing to Update', 200);
            }
        } catch (Exception $e) {
            // $db->transRollback();
            $statusCode = $e->getCode() === 400 ? 400 : ($e->getCode());
            $response = [
                'error' => $e->getCode() === 400 ? ['validation' => $validation->errors()] : $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }


    // Delete a product by ID
    public function deleteCart($id)
    {
        try {
            $cartModel = new CartModel();
            $cartModel->delete($id);

            if ($cartModel->db->affectedRows() == 1) {
                $statusCode = 200;
                $response = [
                    'message' => 'Cart deleted successfully.'
                ];
            } else {
                throw new Exception('Nothing to delete', 200);
            }
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : ($e->getCode());
            $response = [
                'error' => $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }
    public function getCartById($customerId)
    {
        $cartModel = new CartModel();
        // $productModel = new ProductModel();
        $productImageModel = new ProductImageModel();

        try {
            $products = $cartModel->select([
                'af_cart.id AS id',
                'af_products.id AS product_id',
                'af_products.name AS name',
                'af_products.actual_price AS actual_price',
                // 'af_products.brand AS brand', 
                'af_products.increase_percent AS increase_percent',
                'af_products.discounted_percent AS discounted_percent',
                'af_cart.quantity AS quantity'
            ])
                ->join('af_products', 'af_products.id = af_cart.product_id')
                ->where('af_cart.customer_id', $customerId)
                ->findAll();
            // die(var_dump($products));
            helper('products');
            $products = array_map('get_discounted_price', $products);
            foreach ($products as $key => &$product) {
                $imageRecord = $productImageModel->where('product_id', $product['product_id'])->findColumn('path_360x360');
                if (!empty($imageRecord)) {
                    $products[$key]['image_path'] = $imageRecord[0];
                }
                // $products[$key]['actual_price'] = $product['actual_price'] + ($product['actual_price'] / 100 * $product['increase_percent']);
                // $products[$key]['discounted_price'] = $product['actual_price'] - ($product['actual_price'] / 100 * $product['discounted_percent']);


            }

            if (!empty($cartModel->errors())) {
                throw new Exception('Validation', 400);
            }

            $statusCode = 200;
            $response = [
                'data' => $products
            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? $cartModel->errors() : $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }
}
