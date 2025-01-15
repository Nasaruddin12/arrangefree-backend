<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Database\Migrations\AfBrands;
use App\Models\BrandModel;
use App\Models\CartModel;
use App\Models\HomeZoneAppliancesModel;
use App\Models\HomeZoneCategoryModel;
use App\Models\ProductModel;
use App\Models\GeneralOptionModel;
use App\Models\ProductImageModel;
use App\Models\RatingReviewModel;
use App\Models\WishlistModel;
use CodeIgniter\API\ResponseTrait;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Stmt\TryCatch;

class FeaturesController extends BaseController
{
    use ResponseTrait;


    public function getBestDeal()
    {
        $productModel = new ProductModel();
        $productImageModel = new ProductImageModel();
        $customerid = $this->request->getVar('customer_id');
        // $homeZoneAppliancesModel = new HomeZoneAppliancesModel();
        try {
            $products = $productModel->select([
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
                ->join('af_home_zone_appliances', 'af_products.home_zone_appliances_id = af_home_zone_appliances.id')
                ->join('af_product_images', 'af_product_images.product_id = af_products.id')
                ->join('af_brands', 'af_brands.id = af_products.brand_id')
                ->where('af_product_images.image_index', 0)
                ->orderBy('af_products.actual_price', 'ASC')->findAll(10);
            // echo count($products);
            $validation = &$productModel;
            if ($productModel->db->error()['code']) {
                throw new Exception($productModel->db->error()['message'], 500);
            }
            if (!empty($productModel->errors())) {
                throw new Exception('Validation', 400);
            }
            // $products = array_slice($products, 0, 10);
            // var_dump($productModel->db->getLastQuery());exit;

            if ($products === null) {
                throw new Exception('Product not found', 404);
            }
            foreach ($products as $key => $product) {
                $ratingReviewModel = new RatingReviewModel();
                $ratingData = $ratingReviewModel->where('product_id', $product['id'])->findColumn('rating');
                if (is_array($ratingData)) {
                    $totalRatingCount = count($ratingData);
                    $totalRatingSum = array_sum($ratingData);
                    $products[$key]['rating'] = $totalRatingSum / $totalRatingCount;
                } else {
                    $products[$key]['rating'] = 0;
                }

                $wishlistmodel = new WishlistModel();
                $wishlistdata = $wishlistmodel->where('customer_id', $customerid)->where('product_id', $product['id'])->first();

                if (!empty($wishlistdata)) {
                    $products[$key]['is_wishlist'] = 1;
                    $products[$key]['whishlist_id'] = $wishlistdata['id'];
                } else {
                    $products[$key]['is_wishlist'] = 0;
                }

                // $products[$key]['description'] = json_decode($products[$key]['description'], true);
                // $products[$key]['features'] = json_decode($products[$key]['features'], true);
            }

            helper('products');
            $products = array_map('get_discounted_price', $products);

            $response = [
                'data' => $products,
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

    public function test()
    {
        // $session = session();
        // $session->set('id', 'Yo');
    }

    public function test2()
    {
        // $session = session();
        // echo $session->get('id');
    }

    public function getCartAndWhishlistCount()
    {
        try {
            if ($this->request->hasHeader('token')) {
                $token = $this->request->header('token');
                $token = $token->getValue();
                $key = getenv('JWT_SECRET');
                $tokenData = JWT::decode($token, new Key($key, 'HS256'));
                $customerID = $tokenData->customer_id;

                $cartModel = new CartModel();
                $validation = &$cartModel;
                $cartCount = $cartModel->where('customer_id', $customerID)->countAllResults();

                $whishlistModel = new WishlistModel();
                $validation = &$whishlistModel;
                $whishCount = $whishlistModel->where('customer_id', $customerID)->countAllResults();
                $statusCode = 200;
                $response = [
                    'cart_count' => $cartCount,
                    'wishlistCount' => $whishCount,
                ];
            } else {
                $statusCode = 403;
                $response = [
                    'message' => 'Invalid token!',
                ];
            }
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? $validation->errors() : $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    public function increasePrice()
    {
        try {
            $generalOptionsModel = new GeneralOptionModel();
        } catch (Exception $e) {
        }
    }

    public function getFiltersParams()
    {
        try {
            $brandModel = new BrandModel();
            $slug = $this->request->getVar('slug');
            $brandsData = $brandModel->findAll();
            $homeZoneCategoryModel = new HomeZoneCategoryModel();
            $homeZoneAppliancesModel = new HomeZoneAppliancesModel();

            $validation = &$homeZoneCategoryModel;
            $homezoneAppliancesID = $homeZoneCategoryModel->where('slug', $slug)->first();
            if(empty($homezoneAppliancesID))
            {
                throw new Exception('Data Not Found!', 500);
            }
            $homezoneAppliancesID = $homezoneAppliancesID['home_zone_appliances_id'];
            // print_r($homezoneAppliancesID);die;

            $categoriesData = $homeZoneCategoryModel->where('home_zone_appliances_id',$homezoneAppliancesID)->findAll();
            
          
            $statusCode = 200;
            $response = [
                'Brands' => $brandsData,
                'Sub_Category' => $categoriesData,
            ];
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

// $minPrice = $dbPriceRange['min_price'];
// $maxPrice = $dbPriceRange['max_price'];
// $rangeStart = pow(10, strlen((int)$minPrice)-1);
// $rangeEnd = pow(10, strlen((int)$maxPrice));
// $rangeGap = (int)(($rangeEnd - $rangeStart) / 5);
// $rangeGapIn10 = pow(10, strlen($rangeGap)-1);
// $rangeGap = (((int)($rangeGap / $rangeGapIn10)) * $rangeGapIn10) + $rangeGapIn10;
// $priceRange = array();
// for($i = 0; $i < 5; $i++)
// {
//     $priceRange[] = array(
//         'from' => $rangeStart,
//         'to' => ($rangeStart += $rangeGap),
//     );
// }

// print_r($priceRange);die;
// // echo $rangeGap;die;

// // echo 'min' . $rangeStart;
// // echo 'max' . $rangeEnd;
// // $rangeGap = (int)(($rangeEnd - $rangeStart) / 5);
// // echo 'gap' . $rangeGap;die;
// // $priceRanges = 