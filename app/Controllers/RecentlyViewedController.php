<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\RatingReviewModel;
use App\Models\RecentlyViewedModel;
use Exception;
use CodeIgniter\API\ResponseTrait;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class RecentlyViewedController extends BaseController
{
    use ResponseTrait;

    function get_discounted_price($productRecord)
    {
        // var_dump($productRecord);
        $productRecord['actual_price'] = $productRecord['actual_price'] + ($productRecord['actual_price'] / 100 * $productRecord['increase_percent']);
        $singleProductPrice = ($productRecord['actual_price'] - ($productRecord['actual_price'] / 100 * $productRecord['discounted_percent']));
        $productRecord['discounted_price'] = $singleProductPrice;
        return $productRecord;
    }

    function get_rating($productRecord)
    {
        $ratingReviewModel = new RatingReviewModel();
        $ratingData = $ratingReviewModel->where('product_id', $productRecord['id'])->findColumn('rating');
        if (is_array($ratingData)) {
            $totalRatingCount = count($ratingData);
            $totalRatingSum = array_sum($ratingData);
            $productRecord['rating'] = $totalRatingSum / $totalRatingCount;
        } else {
            $productRecord['rating'] = 0;
        }
        return $productRecord;
    }

    public function getRecentView()
    {
        $recentlyViewedModel = new RecentlyViewedModel();
        // $sessionID = $this->request->getVar('session_id');
        // $session = session();
        // $session->regenerate();
        // $session->session_id = $sessionID;
        // $customerID = $session->get('customer_id');
        if (!$this->request->hasHeader('token')) {
            $statusCode = 403;
            $response = [
                'message' => 'Access Denied',
            ];
            $response['status'] = $statusCode;
            return $this->respond($response, $statusCode);
        }
        try {
            $token = $this->request->header('token');
            $token = $token->getValue();
            $key = getenv('JWT_SECRET');
            $tokenData = JWT::decode($token, new Key($key, 'HS256'));
            $customerID = $tokenData->customer_id;
            $data = $recentlyViewedModel->select([
                'af_products.id AS id',
                'af_products.name AS name',
                'af_products.actual_price AS actual_price',
                'af_products.increase_percent AS increase_percent',
                'af_products.discounted_percent AS discounted_percent',
                'af_brands.id AS brand_id',
                'af_brands.name AS brand_name',
                'af_products.properties AS properties',
                'af_product_images.path_360x360 AS image'
            ])
                ->join('af_products', 'af_products.id = af_recently_viewed.product_id')
                ->join('af_product_images', 'af_product_images.product_id = af_products.id')
                ->join('af_brands', 'af_brands.id = af_products.brand_id')
                ->where('af_recently_viewed.customer_id', $customerID)
                ->where('af_product_images.image_index', 0)
                ->orderBy('af_recently_viewed.id', 'DESC')
                ->findAll(10);


            $validation = &$recentlyViewedModel;
            if ($validation->db->error()['code']) {
                throw new Exception(
                    $validation->db->error()['message'],
                    500
                );
            }
            if (!empty($validation->errors())) {
                throw new Exception(
                    'Validation',
                    400
                );
            }

            // $data = array_map([$this, 'get_discounted_price'], $data);
            helper('products');
            $data = array_map('get_discounted_price', $data);
            $data = array_map([$this, 'get_rating'], $data);

            $response = [
                'data' => $data,
            ];
            if (!empty($homezoneData)) {
                $response['features'] = json_decode($homezoneData['features'], true);
                $response['description'] = json_decode($homezoneData['description'], true);
            }
            $statusCode = 200;
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? $validation->errors() : $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }


    public function getRecentViewBySlug($slug)
    {
        $recentlyViewedModel = new RecentlyViewedModel();
        // $sessionID = $this->request->getVar('session_id');
        // $session = session();
        // $session->regenerate();
        // $session->session_id = $sessionID;
        // $customerID = $session->get('customer_id');
        if (!$this->request->hasHeader('token')) {
            $statusCode = 403;
            $response = [
                'message' => 'Access Denied',
            ];
            $response['status'] = $statusCode;
            return $this->respond($response, $statusCode);
        }
        try {
            $token = $this->request->header('token');
            $token = $token->getValue();
            $key = getenv('JWT_SECRET');
            $tokenData = JWT::decode($token, new Key($key, 'HS256'));
            $customerID = $tokenData->customer_id;
            // $slug = $this->request->getVar('slug');
            $data = $recentlyViewedModel->select([
                'af_products.id AS id',
                'af_products.home_zone_appliances_id AS home_zone_appliances_id',
                'af_products.name AS name',
                'af_products.actual_price AS actual_price',
                'af_products.discounted_percent AS discounted_percent',
                'af_products.brand AS brand_name',
                'af_products.properties AS properties',
                'af_product_images.path_360x360 AS image'
            ])
                ->join('af_products', 'af_products.id = af_recently_viewed.product_id')
                ->join('af_home_zone_appliances', 'af_home_zone_appliances.id = af_products.home_zone_appliances_id')
                ->join('af_product_images', 'af_product_images.product_id = af_products.id')
                ->where('af_home_zone_appliances.slug', $slug)
                ->where('af_recently_viewed.customer_id', $customerID)
                ->where('af_product_images.image_index', 0)
                ->orderBy('af_recently_viewed.id', 'DESC')
                ->findAll(10);
            // echo $recentlyViewedModel->db->getLastQuery();

            $validation = &$recentlyViewedModel;
            if ($validation->db->error()['code']) {
                throw new Exception(
                    $validation->db->error()['message'],
                    500
                );
            }
            if (!empty($validation->errors())) {
                throw new Exception(
                    'Validation',
                    400
                );
            }


            $response = [
                'data' => $data,
            ];
            if (!empty($homezoneData)) {
                $response['features'] = json_decode($homezoneData['features'], true);
                $response['description'] = json_decode($homezoneData['description'], true);
            }
            $statusCode = 200;
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? $validation->errors() : $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }
}
