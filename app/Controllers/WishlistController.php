<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ProductImageModel;
use App\Models\WishlistModel;
use CodeIgniter\API\ResponseTrait;
use Exception;

class WishlistController extends BaseController
{
    use ResponseTrait;
    public function create()
    {
        try {
            $WishlistModel = new WishlistModel();
            $validation = \Config\Services::validation();
            $statusCode = 200;

            $productData = [
                'product_id' => $this->request->getVar('product_id'),
                'customer_id' => $this->request->getVar('customer_id'),
            ];

            $WishlistModel->insert($productData);

            if (!empty($WishlistModel->errors())) {
                throw new Exception('Validation', 400);
            }

            if ($WishlistModel->db->error()['code']) {
                throw new Exception($WishlistModel->db->error()['message'], 500);
            }

            if ($WishlistModel->db->affectedRows() == 1) {
                $response = [
                    'message' => 'Wishlist created successfully.',
                    'whishlist_id' => $WishlistModel->db->insertID(),
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

    public function getwishlistById($customerId)
    {
        $WishlistModel = new WishlistModel();
        // $productModel = new ProductModel();
        $productImageModel = new ProductImageModel();

        try {
            $products = $WishlistModel->select([
                'af_wishlist.id AS id',
                'af_products.id AS product_id',
                'af_products.name AS name',
                'af_products.actual_price AS actual_price',
                'af_products.properties AS properties',
                'af_products.increase_percent AS increase_percent',
                'af_products.discounted_percent AS discounted_percent',
            ])->join('af_products', 'af_products.id = af_wishlist.product_id')->where('af_wishlist.customer_id', $customerId)->findAll();
            // die(var_dump($products));
            helper('products');
            $products = array_map('get_discounted_price', $products);
            
            foreach ($products as $key => $product) {
                $imageRecord = $productImageModel->where('product_id', $product['product_id'])->findColumn('path_360x360');
                if (!empty($imageRecord)) {
                    $products[$key]['image'] = $imageRecord[0];
                }
                /* if ($product['increase_percent'] != '') {
                    $products[$key]['discounted_price'] = $product['actual_price'] + ($product['actual_price'] / 100 * $product['increase_percent']);
                }
                if ($product['discounted_percent'] != '') {
                    $products[$key]['discounted_price'] = $product['actual_price'] - ($product['actual_price'] / 100 * $product['discounted_percent']);
                } */
            }

            if (!empty($WishlistModel->errors())) {
                throw new Exception('Validation', 400);
            }

            $statusCode = 200;
            $response = [
                'data' => $products
            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? $WishlistModel->errors() : $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    public function deletewishlist($id)
    {
        try {
            $wishlistModel = new WishlistModel();
            $wishlist = $wishlistModel->find($id);

            if (!$wishlist) {
                throw new \Exception('Wishlist item not found', 404);
            }

            $wishlistModel->delete($id);

            $response = [
                'status' => 200,
                'message' => 'Wishlist item deleted successfully.'
            ];
        } catch (\Exception $e) {
            $statusCode = $e->getCode() === 404 ? 404 : 500;
            $response = [
                'status' => $statusCode,
                'error' => $e->getCode() === 404 ? 'Wishlist item not found' : $e->getMessage()
            ];
        }

        return $this->respond($response, $response['status']);
    }
}
