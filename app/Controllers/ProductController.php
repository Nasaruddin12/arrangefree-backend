<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Database\Migrations\AfHomeZoneAppliances;
use App\Database\Migrations\AfHomeZoneCategory;
use App\Models\BrandModel;
use App\Models\CategoryModel;
use App\Models\DesignerAssignProductModel;
use App\Models\DesignerModel;
use App\Models\HomeZoneCategoryModel;
use App\Models\ProductAtrributesModel;
use App\Models\ProductAtrributesValueModel;
use App\Models\ProductModel;
use App\Models\ProductImageModel;
use App\Models\HomeZoneAppliancesModel;
use App\Models\RatingReviewModel;
use App\Models\RecentlyViewedModel;
use App\Models\VendorsModel;
use App\Models\WishlistModel;
use App\Models\ProductEmiPlansModel;
use CodeIgniter\API\ResponseTrait;
use Exception;
use CodeIgniter\Database\Exceptions\DataException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use PhpParser\JsonDecoder;
use PhpParser\Node\Stmt\TryCatch;

class ProductController extends BaseController
{
    use ResponseTrait;

    /* function get_discounted_price($productRecord)
    {
        // var_dump($productRecord);
        $increasePrice = ($productRecord['actual_price'] / 100 * $productRecord['increase_percent']);
        $discount = $increasePrice / 100 * $productRecord['discounted_percent'];
        $actualPrice = $productRecord['actual_price'] + $increasePrice;
        $discountedPercent = $discount / $actualPrice * 100;

        // $singleProductPrice = ($productRecord['actual_price'] - ($productRecord['actual_price'] / 100 * $productRecord['discounted_percent']));
        // $productRecord['discounted_price'] = $singleProductPrice;
        $productRecord['actual_price'] = $actualPrice;
        $productRecord['discounted_percent'] = $discountedPercent;
        $productRecord['discounted_price'] = $actualPrice - $discount;
        return $productRecord;
    } */
    // Create a new product
    public function createProduct()
    {
        // $temporary = $this->request->getVar();
        // $temporary = json_decode(json_encode($temporary), true);
        // return $this->respond($temporary);exit;


        try {
            $db = db_connect();
            $db->transException(true)->transStart();
            $productModel = new ProductModel();
            $validation = &$productModel;
            $statusCode = 200;

            $userBackendToFrontendAttrs = [
                'home_zone_appliances_id' => 'home_zone_appliances_id',
                'home_zone_category_id' => 'home_zone_category_id',
                'brand_id' => 'brand_id',
                'name' => 'name',
                'actual_price' => 'actual_price',
                'height' => 'height',
                'size' => 'size',
                'warranty' => 'warranty',
                'product_code' => 'product_code',
                'features' => 'features',
                'properties' => 'properties',
                'quantity' => 'quantity',
                'care_n_instructions' => 'care_n_instructions',
                'warranty_details' => 'warranty_details',
                'quality_promise' => 'quality_promise',
                'vendor_name' => 'vender_name',
            ];

            $productData = [];
            foreach ($userBackendToFrontendAttrs as $backendAttr => $frontendAttr) {
                $productData[$backendAttr] = $this->request->getVar($frontendAttr);
            }
            // $productData['discounted_percent'] = $productData['discounted_price'] / $productData['actual_price'] * 100;

            $productModel->insert($productData);

            if ($productModel->db->error()['code']) {
                // echo 'pro';exit;
                throw new Exception($productModel->db->error()['message'], 500);
            }
            if (!empty($productModel->errors())) {
                // echo 'pro2';exit;
                throw new Exception('Validation', 400);
            }
            // echo $productModel->db->getLastQuery();
            // die;
            $productID = $productModel->db->insertID();


            // Custom Fields Value
            $productAttributesModel = new ProductAtrributesModel();
            $productAttributesValueModel = new ProductAtrributesValueModel();
            $customFields = $this->request->getVar('custom_fields');
            $customFields = json_decode($customFields, true);
            if (!empty($customFields)) {

                foreach ($customFields as $key => $row) {
                    // Insert Attribute
                    $attributeData = [
                        'product_id' => $productID,
                        'attribute_title' => $row['title'],
                    ];
                    $productAttributesModel->insert($attributeData);
                    $validation = &$productAttributesModel;
                    if ($productAttributesModel->db->error()['code']) {
                        // echo 'custom field';
                        // exit;
                        throw new Exception($productAttributesModel->db->error()['message'], 500);
                    }
                    if (!empty($productAttributesModel->errors())) {
                        // echo 'custom field2';
                        // exit;
                        throw new Exception('Validation', 400);
                    }


                    $attributeID = $productAttributesModel->db->insertID();

                    // Insert Value
                    $attributeValueData = [
                        'attribute_id' => $attributeID,
                        'attribute_value' => $row['value'],
                    ];
                    $productAttributesValueModel->insert($attributeValueData);
                    $validation = &$productAttributesValueModel;
                    if ($productAttributesValueModel->db->error()['code']) {
                        // echo 'value';
                        // exit;
                        throw new Exception($productAttributesValueModel->db->error()['message'], 500);
                    }
                    if (!empty($productAttributesValueModel->errors())) {
                        // echo 'value2';
                        // exit;
                        throw new Exception('Validation', 400);
                    }
                }
            }

            // Image
            $productImagesData = $this->request->getVar('images_path');
            $productImagesData = json_decode($productImagesData, true);
            // return $this->respond(var_dump($productImagesData));exit;
            if (!empty($productImagesData)) {
                // echo $productImagesData[0];
                // die;
                $addProductID = function ($item) use ($productID) {
                    $item['product_id'] = $productID;
                    return $item;
                };
                $productImagesData = array_map($addProductID, $productImagesData);

                // var_dump($productImagesData);
                // die;

                $productImageModel = new ProductImageModel();
                $validation = &$productImageModel;
                $productImageModel->insertBatch($productImagesData);
                if ($productImageModel->db->error()['code']) {
                    // echo 'images';
                    // exit;
                    throw new Exception($productImageModel->db->error()['message'], 500);
                }
                if (!empty($productImageModel->errors())) {
                    // echo 'images2';
                    // exit;
                    throw new Exception('Validation', 400);
                }
            }


            $db->transComplete();
        } catch (Exception $e) {
            // echo 'asdfasf';exit;
            $db->transRollback();
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? ['validation' => $validation->errors()] : $e->getMessage()
            ];


            $imagePaths = [
                'path_1620x1620',
                'path_580x580',
                'path_360x360',
            ];
            if (!empty($productImagesData)) {

                foreach ($productImagesData as $key => $image) {
                    foreach ($imagePaths as $path) {
                        if (file_exists($image[$path])) {
                            unlink($image[$path]);
                        }
                    }
                }
            }
            return $this->respond($response, 500);
        } catch (DataException $e) {
            $response = [
                'Exception' => $e->getMessage(),
            ];
        }

        if ($db->transStatus() == true) {
            $statusCode = 200;
            $response = [
                'message' => 'Product created successfully.',
                'product_id' => $productID,
            ];
        } else {
            if (empty($response)) {
                $statusCode = 500;
                $response = [
                    'error' => 'Something went wrong',
                ];
            }
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    // Update a product by ID
    public function updateProduct($id)
    {
        // $data = $this->request->getVar();
        // die(var_dump(json_decode(json_encode($data),true)));

        try {
            // $db = db_connect();
            $productModel = new ProductModel();
            $productModel->db->transException(true)->transStart();
            $validation = &$productModel;
            $statusCode = 200;

            $data = $this->request->getVar();
            $data = json_decode(json_encode($data), true);

            $userBackendToFrontendAttrs = [
                'home_zone_appliances_id' => 'home_zone_appliances_id',
                'home_zone_category_id' => 'home_zone_category_id',
                'brand_id' => 'brand_id',
                'name' => 'name',
                'actual_price' => 'actual_price',
                'height' => 'height',
                'size' => 'size',
                'warranty' => 'warranty',
                'product_code' => 'product_code',
                'features' => 'features',
                'properties' => 'properties',
                'quantity' => 'quantity',
                'care_n_instructions' => 'care_n_instructions',
                'warranty_details' => 'warranty_details',
                'quality_promise' => 'quality_promise',
                'vendor_name' => 'vender_name',
            ];

            $productData = [];
            foreach ($userBackendToFrontendAttrs as $backendAttr => $frontendAttr) {
                // $productData[$backendAttr] = $this->request->getVar($frontendAttr);
                $productData[$backendAttr] = $data[$frontendAttr];
            }

            $productModel->update($id, $productData);

            if ($productModel->db->error()['code']) {
                throw new Exception($productModel->db->error()['message'], 500);
            }

            if (!empty($productModel->errors())) {
                throw new Exception('Validation', 400);
            }

            // Custom Fields Value
            $productAttributesModel = new ProductAtrributesModel();
            $productAttributesValueModel = new ProductAtrributesValueModel();
            // $customFields = $this->request->getVar('custom_fields');
            $customFields = $data['custom_fields'];
            // $customFields = json_decode($customFields, true);

            if (!empty($customFields)) {

                $customFieldsAttributesIDs = array_column($customFields, 'attribute_id');
                // delete attributes
                $productAttributesModel->where('product_id', $id)->whereNotIn('id', $customFieldsAttributesIDs)->delete();
                // print_r($data);die

                $customFields_Update = array_filter($customFields, function ($field) {
                    return ($field['attribute_id'] != null);
                });

                if (!empty($customFields_Update)) {
                    $attributeData = array();
                    foreach ($customFields_Update as $key => $row) {
                        $attributeData[] = [
                            'id' => $row['attribute_id'],
                            'attribute_title' => $row['title'],
                        ];
                        // $productAttributesModel->update($attributeData);
                        // $validation = &$productAttributesModel;
                        // if ($productAttributesModel->db->error()['code']) {
                        //     throw new Exception($productAttributesModel->db->error()['message'], 500);
                        // }
                        // if (!empty($productAttributesModel->errors())) {
                        //     throw new Exception('Validation', 400);
                        // }

                        // $attributeID = $productAttributesModel->db->insertID();

                        $attributeValueData[] = [
                            'attribute_id' => $row['attribute_id'],
                            'attribute_value' => $row['value'],
                        ];
                        // $productAttributesValueModel->insert($attributeValueData);
                        // $validation = &$productAttributesValueModel;
                        // if ($productAttributesValueModel->db->error()['code']) {
                        //     throw new Exception($productAttributesValueModel->db->error()['message'], 500);
                        // }
                        // if (!empty($productAttributesValueModel->errors())) {
                        //     throw new Exception('Validation', 400);
                        // }
                    }
                    // die(print_r($attributeData));
                    $validation = &$productAttributesModel;
                    $productAttributesModel->updateBatch($attributeData, 'id');
                    // echo $productAttributesModel->db->getLastQuery();
                    if ($productAttributesModel->db->error()['code']) {
                        throw new Exception($productAttributesModel->db->error()['message'], 500);
                    }
                    if (!empty($productAttributesModel->errors())) {
                        throw new Exception('Validation', 400);
                    }
                    $validation = &$productAttributesValueModel;
                    $productAttributesValueModel->updateBatch($attributeValueData, 'attribute_id');
                    if ($productAttributesValueModel->db->error()['code']) {
                        throw new Exception($productAttributesValueModel->db->error()['message'], 500);
                    }
                    if (!empty($productAttributesValueModel->errors())) {
                        throw new Exception('Validation', 400);
                    }
                }
                // New Attributes
                $customFields_Insert = array_filter($customFields, function ($field) {
                    return ($field['attribute_id'] == null);
                });

                if (!empty($customFields_Insert)) {

                    foreach ($customFields_Insert as $key => $row) {
                        // Insert Attribute
                        $attributeData = [
                            'product_id' => $id,
                            'attribute_title' => $row['title'],
                        ];
                        $validation = &$productAttributesModel;
                        $productAttributesModel->insert($attributeData);
                        if ($productAttributesModel->db->error()['code']) {
                            // echo 'custom field';
                            // exit;
                            throw new Exception($productAttributesModel->db->error()['message'], 500);
                        }
                        if (!empty($productAttributesModel->errors())) {
                            // echo 'custom field2';
                            // exit;
                            throw new Exception('Validation', 400);
                        }


                        $attributeID = $productAttributesModel->db->insertID();

                        // Insert Value
                        $attributeValueData = [
                            'attribute_id' => $attributeID,
                            'attribute_value' => $row['value'],
                        ];
                        $validation = &$productAttributesValueModel;
                        $productAttributesValueModel->insert($attributeValueData);
                        if ($productAttributesValueModel->db->error()['code']) {
                            // echo 'value';
                            // exit;
                            throw new Exception($productAttributesValueModel->db->error()['message'], 500);
                        }
                        if (!empty($productAttributesValueModel->errors())) {
                            // echo 'value2';
                            // exit;
                            throw new Exception('Validation', 400);
                        }
                    }
                }
            }

            // Image
            // $productImagesData = $this->request->getVar('images_path');
            // $productImagesData = json_decode($productImagesData, true);
            $productImagesData = $data['images_path'];

            if (!empty($productImagesData)) {
                foreach ($productImagesData as $key => &$productImage) {
                    $productImage['image_index'] = $key;
                }
                $productImageModel = new ProductImageModel();
                $productImagesData_Insert = array_filter($productImagesData, function ($imageData) {
                    return ($imageData['id'] == null);
                });
                $productImagesData_Update = array_filter($productImagesData, function ($imageData) {
                    return ($imageData['id'] != null);
                });
                if (!empty($productImagesData_Insert)) {


                    $addProductID = function ($item) use ($id) {
                        $newItem = array();
                        $newItem = [
                            'product_id' => $id,
                            'path_1620x1620' => $item['path_1620x1620'],
                            'path_580x580' => $item['path_580x580'],
                            'path_360x360' => $item['path_360x360'],
                            'image_index' => $item['image_index'],
                        ];

                        return $newItem;
                    };
                    $productImagesData_Insert = array_map($addProductID, $productImagesData_Insert);

                    $validation = &$productImageModel;
                    if (!empty($productImagesData_Insert)) {
                        $productImageModel->insertBatch($productImagesData_Insert);
                        if ($productImageModel->db->error()['code']) {
                            throw new Exception($productImageModel->db->error()['message'], 500);
                        }
                        if (!empty($productImageModel->errors())) {
                            throw new Exception('Validation', 400);
                        }
                    }
                }
                // Update Image
                if (!empty($productImagesData_Update)) {
                    /* $getUpdateIndex = function ($item) {
                        $updateData = [
                            'id' => $item['id'],
                            'image_index' => $item['image_index'],
                        ];
                        return $updateData;
                    };
                    $productImagesData_Update = array_map($getUpdateIndex, $productImagesData_Update); */
                    $productImageModel->updateBatch($productImagesData_Update, 'id');
                    if ($productImageModel->db->error()['code']) {
                        throw new Exception($productImageModel->db->error()['message'], 500);
                    }
                    if (!empty($productImageModel->errors())) {
                        throw new Exception('Validation', 400);
                    }
                }
            }

            $productModel->db->transComplete();
        } catch (Exception $e) {
            $productModel->db->transRollback();
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? ['validation' => $validation->errors()] : $e->getMessage()
            ];

            $imagePaths = [
                'path_1620x1620',
                'path_580x580',
                'path_360x360',
            ];
            if (!empty($productImagesData_Insert)) {
                foreach ($productImagesData_Insert as $key => $image) {
                    foreach ($imagePaths as $path) {
                        if (file_exists($image[$path])) {
                            unlink($image[$path]);
                        }
                    }
                }
            }
            return $this->respond($response, 500);
        } catch (DataException $e) {
            $response = [
                'Exception' => $e->getMessage(),
            ];
        }

        if ($productModel->db->transStatus() == true) {
            $statusCode = 200;
            $response = [
                'message' => 'Product updated successfully.',
                'product_id' => $id,
            ];
        } else {
            if (empty($response)) {
                $statusCode = 500;
                $response = [
                    'error' => 'Something went wrong',
                ];
            }
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }



    public function deleteProduct($id)
    {
        try {
            $productModel = new ProductModel();
            $productModel->delete($id);

            if ($productModel->db->affectedRows() == 1) {
                $statusCode = 200;
                $response = [
                    'message' => 'Product deleted successfully.'
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

    public function getProductByHomeAppliancesId($slug = null)
    {
        $productModel = new ProductModel();
        $customerid = $this->request->getVar('customer_id');
        $currentPage = $this->request->getVar('page');
        $perPage = $this->request->getVar('per_page_count');

        // $homeZoneAppliancesModel = new HomeZoneAppliancesModel();
        try {
            $products = $productModel->select([
                'af_products.id AS id',
                'af_products.name AS name',
                'af_products.actual_price AS actual_price',
                'af_products.discounted_percent AS discounted_percent',
                'af_products.increase_percent AS increase_percent',
                'af_products.properties AS properties',
                'af_product_images.path_360x360 AS image',
                'af_brands.id AS brand_id',
                'af_brands.name AS brand_name'
            ])
                ->join('af_home_zone_appliances', 'af_products.home_zone_appliances_id = af_home_zone_appliances.id')
                ->join('af_product_images', 'af_product_images.product_id = af_products.id')
                ->join('af_brands', 'af_brands.id = af_products.brand_id')
                ->where('af_home_zone_appliances.slug', $slug)
                ->where('af_product_images.image_index', 0)
                ->paginate($perPage, 'products', $currentPage);
            // ->findAll();
            // print_r($productModel->db->getLastQuery());exit;
            $validation = &$productModel;
            if ($productModel->db->error()['code']) {
                throw new Exception($productModel->db->error()['message'], 500);
            }
            if (!empty($productModel->errors())) {
                throw new Exception('Validation', 400);
            }

            $pageCount = $productModel->pager->getPageCount('products');
            // var_dump($productModel->db->getLastQuery());exit;
            helper('products');
            $products = array_map('get_discounted_price', $products);

            if ($products === null) {
                throw new Exception('Product not found', 404);
            }
            foreach ($products as $key => $product) {

                $wishlistmodel = new WishlistModel();
                $wishlistdata = $wishlistmodel->where('customer_id', $customerid)->where('product_id', $product['id'])->first();

                if (!empty($wishlistdata)) {
                    $products[$key]['is_wishlist'] = 1;
                    $products[$key]['whishlist_id'] = $wishlistdata['id'];
                } else {
                    $products[$key]['is_wishlist'] = 0;
                }


                /* if ($product['increase_price'] != '') {
                    $products[$key]['actual_price'] = $product['actual_price'] + ($product['actual_price'] / 100 * $product['increase_price']);
                }
                if ($product['discounted_percent'] != '') {
                    $products[$key]['discounted_price'] = $product['actual_price'] - ($product['actual_price'] / 100 * $product['discounted_percent']);
                } */

                // $products[$key]['description'] = json_decode($products[$key]['description'], true);
                // $products[$key]['features'] = json_decode($products[$key]['features'], true);
            }
            $homezoneAppliancesModel = new HomeZoneAppliancesModel();
            $homezoneData = $homezoneAppliancesModel->select(['features', 'description'])->where('slug', $slug)->first();
            $validation = &$homezoneAppliancesModel;
            if ($validation->db->error()['code']) {
                throw new Exception($validation->db->error()['message'], 500);
            }
            if (!empty($validation->errors())) {
                throw new Exception('Validation', 400);
            }

            $response = [
                'data' => $products,
                'total_pages' => $pageCount,
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

    public function getProductById($id)
    {
        $productModel = new ProductModel();
        $productImageModel = new ProductImageModel();
        $productAttributesModel = new ProductAtrributesModel();
        $recentlyViewedModel = new RecentlyViewedModel();

        // $sessionID = $this->request->getVar('session_id');
        // $session = session();
        // $session->regenerate();
        // $session->session_id = $sessionID;
        // $tokenData = json_decode($tokenData, true);
        // print_r($tokenData);die;
        // $token = $this->request->getHeader
        // $token = $this->request->getVar('token');
        try {
            // Add in recent Product
            $token = $this->request->header('token');
            if ($this->request->hasHeader('token')) {
                $token = $token->getValue();
                $key = getenv('JWT_SECRET');
                $tokenData = JWT::decode($token, new Key($key, 'HS256'));
                $customerID = $tokenData->customer_id;
                $recentView = $recentlyViewedModel->where(['customer_id' => $customerID, 'product_id' => $id])->first();
                if (!$recentView) {
                    $recentlyViewedModel->insert([
                        'customer_id' => $customerID,
                        'product_id' => $id,
                    ]);
                }
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
            }

            $products = $productModel->where('id', $id)->findAll();
            $validation = &$productModel;
            foreach ($products as $key => $product) {
                $homezoneCategoryModel = new HomeZoneCategoryModel();
                $slug = $homezoneCategoryModel->find($product['home_zone_category_id']);
                $slug = $slug['slug'];
                $products[$key]['category_slug'] = $slug;
                $ratingReviewModel = new RatingReviewModel();
                $ratingData = $ratingReviewModel->where('product_id', $product['id'])->findColumn('rating');
                if (is_array($ratingData)) {
                    $totalRatingCount = count($ratingData);
                    $totalRatingSum = array_sum($ratingData);
                    $products[$key]['rating'] = $totalRatingSum / $totalRatingCount;
                } else {
                    $products[$key]['rating'] = 0;
                }
            }
            // print_r($products);die;
            foreach ($products as $key => $product) {
                $brandModel = new BrandModel();
                $brandData = $brandModel->where('id', $product['brand_id'])->first();
                $products[$key]['brand_slug'] = $brandData['slug'];
                $products[$key]['brand_name'] = $brandData['name'];
            }
            // echo '1';
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
            helper('products');
            $products = array_map('get_discounted_price', $products);

            foreach ($products as &$product) {
                // $product['actual_price'] = $product['actual_price'] + ($product['actual_price'] / 100 * $product['increase_percent']);
                // $product['discounted_price'] = $product['actual_price'] - ($product['actual_price'] / 100 * $product['discounted_percent']);
                $productsAttributes = $productAttributesModel->select([
                    'af_products_attributes.attribute_title AS title',
                    'af_products_attributes.id AS attribute_id',
                    'af_products_attribute_value.attribute_value AS value'
                ])
                    ->join('af_products_attribute_value', 'af_products_attribute_value.attribute_id = af_products_attributes.id')
                    ->where('af_products_attributes.product_id', $product['id'])
                    ->findAll();
                $validation = &$productAttributesModel;
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
                $product['custom_fields'] = $productsAttributes;
                $images = $productImageModel->where('product_id', $product['id'])->findAll();
                $product['images'] = $images;
            }

            $discounted_price=$products[0]['discounted_price'];
            $ProductEmiPlansModel=new ProductEmiPlansModel();
            $product_emi_plans_data=$ProductEmiPlansModel->findAll();
            $final_emi=array();
            foreach($product_emi_plans_data as $index => $row){
                $final_emi[$index]['months']=$row['months'];
                $final_emi[$index]['advance_payment_percent']=$row['advance_payment_percent'];
                $final_emi[$index]['advance_amount_to_pay']=($discounted_price/100)*$row['advance_payment_percent'];
                $remaining_amount=  $discounted_price-$final_emi[$index]['advance_amount_to_pay'];
                $final_emi[$index]['amount_to_pay_per_month']=$remaining_amount/$row['months'];
                $final_emi[$index]['amount_to_pay_per_month']=round($final_emi[$index]['amount_to_pay_per_month']);
            }
            $products[0]['product_emi_details']=$final_emi;
            $statusCode = 200;
            $response = [
                'data' => $products
            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? $validation->getErrors() : $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    // admin getProduct
    public function getProduct($id)
    {
        $productModel = new ProductModel();
        $productImageModel = new ProductImageModel();
        $productAttributesModel = new ProductAtrributesModel();
        $recentlyViewedModel = new RecentlyViewedModel();

        // $sessionID = $this->request->getVar('session_id');
        // $session = session();
        // $session->regenerate();
        // $session->session_id = $sessionID;
        // $tokenData = json_decode($tokenData, true);
        // print_r($tokenData);die;
        // $token = $this->request->getHeader
        // $token = $this->request->getVar('token');
        try {
            // Add in recent Product
            $token = $this->request->header('token');
            if ($this->request->hasHeader('token')) {
                $token = $token->getValue();
                $key = getenv('JWT_SECRET');
                $tokenData = JWT::decode($token, new Key($key, 'HS256'));
                $customerID = $tokenData->customer_id;
                $recentView = $recentlyViewedModel->where(['customer_id' => $customerID, 'product_id' => $id])->first();
                if (!$recentView) {
                    $recentlyViewedModel->insert([
                        'customer_id' => $customerID,
                        'product_id' => $id,
                    ]);
                }
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
            }

            $products = $productModel->where('id', $id)->findAll();
            $validation = &$productModel;
            foreach ($products as $key => $product) {
                $homezoneCategoryModel = new HomeZoneCategoryModel();
                $slug = $homezoneCategoryModel->find($product['home_zone_category_id']);
                $slug = $slug['slug'];
                $products[$key]['category_slug'] = $slug;
                $ratingReviewModel = new RatingReviewModel();
                $ratingData = $ratingReviewModel->where('product_id', $product['id'])->findColumn('rating');
                if (is_array($ratingData)) {
                    $totalRatingCount = count($ratingData);
                    $totalRatingSum = array_sum($ratingData);
                    $products[$key]['rating'] = $totalRatingSum / $totalRatingCount;
                } else {
                    $products[$key]['rating'] = 0;
                }
            }
            // print_r($products);die;
            foreach ($products as $key => $product) {
                $brandModel = new BrandModel();
                $brandData = $brandModel->where('id', $product['brand_id'])->first();
                $products[$key]['brand_slug'] = $brandData['slug'];
                $products[$key]['brand_name'] = $brandData['name'];
                $products[$key]['base_price'] = $product['actual_price'];
            }
            // echo '1';
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
            helper('products');
            $products = array_map('get_discounted_price', $products);

            foreach ($products as &$product) {
                // $product['actual_price'] = $product['actual_price'] + ($product['actual_price'] / 100 * $product['increase_percent']);
                // $product['discounted_price'] = $product['actual_price'] - ($product['actual_price'] / 100 * $product['discounted_percent']);
                $productsAttributes = $productAttributesModel->select([
                    'af_products_attributes.attribute_title AS title',
                    'af_products_attributes.id AS attribute_id',
                    'af_products_attribute_value.attribute_value AS value'
                ])
                    ->join('af_products_attribute_value', 'af_products_attribute_value.attribute_id = af_products_attributes.id')
                    ->where('af_products_attributes.product_id', $product['id'])
                    ->findAll();
                $validation = &$productAttributesModel;
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
                $product['custom_fields'] = $productsAttributes;
                $images = $productImageModel->where('product_id', $product['id'])->findAll();
                $product['images'] = $images;
                $product['increased_price'] = $product['actual_price'];
                $product['actual_price'] = $product['base_price'];
                unset($product['base_price']);
            }

            $statusCode = 200;
            $response = [
                'data' => $products
            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? $validation->getErrors() : $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    public function getAllProducts()
    {
        try {
            $productsModel = new ProductModel();

            $page = $this->request->getVar('page');
            $latest = $this->request->getVar('latest');
            $home_zone_appliances_id = $this->request->getVar('home_zone_appliances_id');
            $home_zone_category_id = $this->request->getVar('home_zone_category_id');
            $brand_id = $this->request->getVar('brand_id');
            $status = $this->request->getVar('status');
            $searchAll = $this->request->getVar('searchAll');

            $validation = &$productsModel;
            $pageCountQuery = new ProductModel();
            $products = $productsModel->select(['id', 'name', 'actual_price', 'home_zone_appliances_id', 'product_code', 'quantity', 'status', 'created_at', 'vendor_name']);
            // var_dump($products->findAll());die;
            if (!($home_zone_appliances_id == 'null' || $home_zone_appliances_id == '')) {
                // echo var_dump($home_zone_appliances_id);
                $pageCountQuery->where('home_zone_appliances_id', $home_zone_appliances_id);
                $products->where('home_zone_appliances_id', $home_zone_appliances_id);
            }
            if (!($home_zone_category_id == 'null' || $home_zone_category_id == '')) {
                // echo 2;
                $pageCountQuery->where('home_zone_category_id', $home_zone_category_id);
                $products->where('home_zone_category_id', $home_zone_category_id);
            }
            if (!($brand_id == 'null' || $brand_id == '')) {
                $pageCountQuery->where('brand_id', $brand_id);
                $products->where('brand_id', $brand_id);
            }
            if (!($status == 'null' || $status == '')) {
                $pageCountQuery->where('status', $status);
                $products->where('status', $status);
            }
            if ($latest == true) {
                $products->orderBy('created_at', 'DESC');
            }
            if (!($searchAll == null || $searchAll == '')) {
                // echo var_dump($order_id);
                $products = $products->like('name', $searchAll)->orLike('product_code', $searchAll)->orLike('vendor_name', $searchAll);
            }
            $products = $products->paginate(50, 'all_products', $page);
            // echo $productsModel->db->getLastQuery();
            // die;
            if ($validation->db->error()['code']) {
                throw new Exception($validation->db->error()['message'], 500);
            }
            if (!empty($validation->errors())) {
                throw new Exception('Validation', 400);
            }

            $pageCount = $productsModel->pager->getPageCount('all_products');
            $ProductCount = $productsModel->pager->getTotal('all_products');
            $totalProductCount = $productsModel->countAllResults();
            // echo $productsModel->db->getLastQuery();

            // var_dump($products);
            if (!empty($products)) {
                foreach ($products as &$product) {
                    $productImageModel = new ProductImageModel();
                    $validation = &$productImageModel;
                    $image = $productImageModel->where(['product_id' => $product['id'], 'image_index' => 0])->findColumn('path_360x360');
                    if ($validation->db->error()['code']) {
                        throw new Exception($validation->db->error()['message'], 500);
                    }
                    if (!empty($validation->errors())) {
                        throw new Exception('Validation', 400);
                    }
                    if (!empty($image)) {
                        $product['thumbnail'] = $image[0];
                    }
                }
            }

            $statusCode = 200;
            $response = [
                'data' => [
                    'products' => $products,
                    'page_count' => $pageCount,
                    'products_count' => $ProductCount,
                    'total_products_count' => $totalProductCount,
                ],
            ];
            // die(print_r($response));
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? $validation->errors() : $e->getMessage()
            ];
        }
        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }
    function rawProductsList()
    {
        try {
            $productsModel = new ProductModel();

            $page = $this->request->getVar('page');
            $latest = $this->request->getVar('latest');
            $home_zone_appliances_id = $this->request->getVar('home_zone_appliances_id');
            $home_zone_category_id = $this->request->getVar('home_zone_category_id');
            $brand_id = $this->request->getVar('brand_id');
            $status = $this->request->getVar('status');
            $validation = &$productsModel;
            $pageCountQuery = new ProductModel();
            $products = $productsModel->select(['id', 'name', 'actual_price', 'home_zone_appliances_id', 'home_zone_category_id', 'product_code', 'quantity', 'status', 'created_at', 'vendor_name']);
            // var_dump($products->findAll());die;
            if (!($home_zone_appliances_id == 'null' || $home_zone_appliances_id == '')) {
                // echo var_dump($home_zone_appliances_id);
                $pageCountQuery->where('home_zone_appliances_id', $home_zone_appliances_id);
                $products->where('home_zone_appliances_id', $home_zone_appliances_id);
            }
            if (!($home_zone_category_id == 'null' || $home_zone_category_id == '')) {
                // echo 2;
                $pageCountQuery->where('home_zone_category_id', $home_zone_category_id);
                $products->where('home_zone_category_id', $home_zone_category_id);
            }
            if (!($brand_id == 'null' || $brand_id == '')) {
                $pageCountQuery->where('brand_id', $brand_id);
                $products->where('brand_id', $brand_id);
            }
            if (!($status == 'null' || $status == '')) {
                $pageCountQuery->where('status', $status);
                $products->where('status', $status);
            }
            if ($latest == true) {
                $products->orderBy('created_at', 'DESC');
            }
            $products = $products->paginate(50, 'all_products', $page);
            // echo $productsModel->db->getLastQuery();
            // die;
            if ($validation->db->error()['code']) {
                throw new Exception($validation->db->error()['message'], 500);
            }
            if (!empty($validation->errors())) {
                throw new Exception('Validation', 400);
            }

            $pageCount = $pageCountQuery->countAllResults();
            $productCount = $productsModel->countAllResults();
            // echo $productsModel->db->getLastQuery();

            $HomeZoneCategoryModel = new HomeZoneCategoryModel();
            $DesignerAssignProductModel = new DesignerAssignProductModel();
            $DesignerModel = new DesignerModel();
            // var_dump($products);
            if (!empty($products)) {
                foreach ($products as &$product) {
                    $productImageModel = new ProductImageModel();
                    $validation = &$productImageModel;
                    $image = $productImageModel->where('product_id', $product['id'])->findColumn('path_360x360');

                    $categoryData = $HomeZoneCategoryModel->where("id", $product["home_zone_category_id"])->where('home_zone_appliances_id', $product["home_zone_appliances_id"])->first();
                    $DesignerData = $DesignerAssignProductModel->where("product_id", $product["id"])->first();
                    if ($DesignerData) {
                        $DData = $DesignerModel->where("id", $DesignerData["designer_id"])->first();
                        $product['AssignTo'] = $DData['name'];
                        $product['designer_id'] = $DData['id'];
                    } else {
                        $product['AssignTo'] = null;
                        $product['designer_id'] = null;
                    }


                    $product['ProductType'] = $categoryData ? $categoryData['title'] : null;

                    if ($validation->db->error()['code']) {
                        throw new Exception($validation->db->error()['message'], 500);
                    }
                    if (!empty($validation->errors())) {
                        throw new Exception('Validation', 400);
                    }
                    if (!empty($image)) {
                        $product['thumbnail'] = $image[0];
                    }
                }
            }

            $statusCode = 200;
            $response = [
                'data' => [
                    'products' => $products,
                    'page_count' => ceil($pageCount / 10),
                    'products_count' => $pageCount,
                    'total_products_count' => $productCount,
                ],
            ];
            // die(print_r($response));
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? $validation->errors() : $e->getMessage(),
                'line' => $e->getLine()
            ];
        }
        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }
    public function updateProductStatus($productId)
    {
        try {
            $status = $this->request->getVar('status');
            $productModel = new ProductModel();
            $product = $productModel->find($productId);

            if (!$product) {
                throw new Exception('Product not found.', 404);
            }

            $product['status'] = $status;
            $productModel->set(['status' => $status])->update($productId);
            // echo $productModel->db->getLastQuery();

            $statusCode = 200;
            $response = [
                'status' => 'success',
                'message' => 'Product status updated successfully.',
                // 'data' => $product,    
            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 404 ? 404 : 500;
            $response = [
                'status' => 'error',
                'message' => $e->getCode() === 404 ? 'Product not found.' : $e->getMessage(),
            ];
        }

        return $this->respond($response, $statusCode);
    }


    public function getProductByLatest()
    {
        $productModel = new ProductModel();
        $productImageModel = new ProductImageModel();
        $productAttributesModel = new ProductAtrributesModel();

        try {
            $products = $productModel->orderBy('created_at', 'desc')->findAll();

            $statusCode = 200;
            $response = [
                'data' => $products
            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? $productModel->errors() : $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    public function get10ProductByHomeAppliancesId()
    {
        $productModel = new ProductModel();
        $productImageModel = new ProductImageModel();
        $homeZoneAppliancesModel = new HomeZoneAppliancesModel();

        try {
            $data = array();
            $homezone = $homeZoneAppliancesModel->findAll();
            foreach ($homezone as $key) {

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
                    ->where('af_home_zone_appliances.slug', $key['slug'])
                    ->where('af_product_images.image_index', 0)
                    ->findAll();

                $validation = &$productModel;

                if ($productModel->db->error()['code']) {
                    throw new Exception($productModel->db->error()['message'], 500);
                }

                if (!empty($productModel->errors())) {
                    throw new Exception('Validation', 400);
                }

                if ($products === null) {
                    throw new Exception('Product not found', 404);
                }

                $productsCount = count($products);
                $tenPercentCount = round($productsCount * 0.1);
                $products = array_slice($products, 0, $tenPercentCount);
                if ($products) {
                    $i = 0;
                    helper('products');
                    $products = array_map('get_discounted_price', $products);
                    /* foreach ($products as $key => &$product) {
                        // print_r($products);
                        // die;
                        $products[$key]['actual_price'] = $product['actual_price'] - ($product['actual_price'] / 100 * $product['increase_percent']);
                        $products[$key]['discounted_price'] = $product['actual_price'] - ($product['actual_price'] / 100 * $product['discounted_percent']);
                        array_push($data, $products[$key]);
                    } */
                }
            }


            $response = [
                'data' => $data,
            ];

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

    public function multipleProductUpload()
    {
        try {
            // echo 'asdffs';die;
            $csvFile = $this->request->getFile('multiple_products');
            $file = fopen($csvFile, 'r');
            $csvData = fgetcsv($file);
            // print_r($csvData);
            // die;
            $productModel = new ProductModel();
            $validation = &$productModel;
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? $validation->errors() : $e->getMessage(),
                'csvData' => $csvData,
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }


    public function getProductByHomeCategoryId($slug = null)
    {
        // echo"dfefde",die;
        $productModel = new ProductModel();
        $customerid = $this->request->getVar('customer_id');
        $currentPage = $this->request->getVar('page');
        $perPage = $this->request->getVar('per_page_count');
        $brands = $this->request->getVar('brands');
        $brands = json_decode(json_encode($brands), true);
        $priceRange = $this->request->getVar('actual_price');
        $priceRange = json_decode(json_encode($priceRange), true);


        // $homeZoneCategoryModel = new HomeZoneCategoryModel();
        try {
            $products = $productModel->select([
                'af_products.id AS id',
                'af_products.name AS name',
                'af_products.actual_price AS actual_price',
                'af_products.discounted_percent AS discounted_percent',
                'af_products.increase_percent AS increase_percent',
                'af_brands.id AS brand_id',
                'af_brands.name AS brand_name',
                'af_products.properties AS properties',
                'af_product_images.path_360x360 AS image'
            ])
                ->join('af_home_zone_category', 'af_products.home_zone_category_id = af_home_zone_category.id')
                ->join('af_product_images', 'af_product_images.product_id = af_products.id')
                ->join('af_brands', 'af_brands.id = af_products.brand_id')
                ->where('af_home_zone_category.slug', $slug)
                ->where('af_product_images.image_index', 0);
            if (!empty($brands)) {
                $products->whereIn('brand_id', $brands);
            }
            if (!empty($priceRange)) {
                $products->where('actual_price >=', $priceRange['minPrice']);
                $products->where('actual_price <=', $priceRange['maxPrice']);
            }
            $products = $products->paginate($perPage, 'products', $currentPage);
            // ->findAll();
            $validation = &$productModel;
            if ($productModel->db->error()['code']) {
                throw new Exception($productModel->db->error()['message'], 500);
            }
            if (!empty($productModel->errors())) {
                throw new Exception('Validation', 400);
            }

            $pageCount = $productModel->pager->getPageCount('products');
            // var_dump($productModel->db->getLastQuery());exit;

            if ($products === null) {
                throw new Exception('Product not found', 404);
            }
            // $products = array_map(array($this, 'get_discounted_price'), $products);
            helper('products');
            $products = array_map('get_discounted_price', $products);

            foreach ($products as $key => &$product) {

                $wishlistmodel = new WishlistModel();
                $wishlistdata = $wishlistmodel->where('customer_id', $customerid)->where('product_id', $product['id'])->first();

                if (!empty($wishlistdata)) {
                    $products[$key]['is_wishlist'] = 1;
                    $products[$key]['whishlist_id'] = $wishlistdata['id'];
                } else {
                    $products[$key]['is_wishlist'] = 0;
                }


                /* if ($product['increase_percent'] != '') {
                    $products[$key]['actual_price'] = $product['actual_price'] + ($product['actual_price'] / 100 * $product['increase_percent']);
                }
                if ($product['discounted_percent'] != '') {
                    $products[$key]['discounted_price'] = $product['actual_price'] - ($product['actual_price'] / 100 * $product['discounted_percent']);
                } */
            }
            // $products[$key]['description'] = json_decode($products[$key]['description'], true);
            // $products[$key]['features'] = json_decode($products[$key]['features'], true);
            // $homezoneCategoryModel = new HomeZoneCategoryModel();
            // $homezoneData = $homezoneCategoryModel->select(['features', 'description'])->where('slug', $slug)->first();
            // $validation = &$homezoneCategoryModel;
            // if ($validation->db->error()['code']) {
            //     throw new Exception($validation->db->error()['message'], 500);
            // }
            // if (!empty($validation->errors())) {
            //     throw new Exception('Validation', 400);
            // }

            $response = [
                'data' => $products,
                'total_pages' => $pageCount,
            ];
            // if (!empty($homezoneData)) {
            //     $response['features'] = json_decode($homezoneData['features'], true);
            //     $response['description'] = json_decode($homezoneData['description'], true);
            // }
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

    public function getSearchAll()
    {
        try {
            $search = $this->request->getVar('search');
            if (!is_null($search) && $search != '') {
                $homeZoneAppliancesModel = new HomeZoneAppliancesModel();
                $homeZoneCategoryModel = new HomeZoneCategoryModel();
                $productsModel = new ProductModel();
                $validation = &$homeZoneAppliancesModel;
                $searchHomeZoneAppliances = $homeZoneAppliancesModel->like('title', $search)->findAll();
                $validation = &$homeZoneCategoryModel;
                $searchHomeZoneCategory = $homeZoneCategoryModel->select('af_home_zone_category.*, af_home_zone_appliances.title AS category_title')->like('af_home_zone_appliances.title', $search)->join('af_home_zone_appliances', 'af_home_zone_appliances.id = af_home_zone_category.home_zone_appliances_id')->findAll();
                $validation = &$productsModel;
                $searchProducts = $productsModel->select('af_products.id AS id, af_products.name AS name, af_product_images.path_360x360 AS image')->like('af_products.name', $search)->join('af_product_images', 'af_product_images.product_id = af_products.id')->findAll(10);

                $statusCode = 200;
                $response = [
                    'homeZoneAppliances' => $searchHomeZoneAppliances,
                    'homeZoneCategory' => $searchHomeZoneCategory,
                    'products' => $searchProducts,
                ];
            } else {
                $statusCode = 400;
                $response = [
                    'message' => 'search feild is empty',
                ];
            }
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : ($e->getCode());
            $response = [
                'error' => $e->getCode() === 400 ? $validation->errors() : $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    public function getSimillarProducts($slug = null)
    {
        $productModel = new ProductModel();
        $customerid = $this->request->getVar('customer_id');
        // $currentPage = $this->request->getVar('page');
        // $perPage = $this->request->getVar('per_page_count');
        // $brands = $this->request->getVar('brands');
        // $brands = json_decode(json_encode($brands), true);
        // $priceRange = $this->request->getVar('actual_price');
        // $priceRange = json_decode(json_encode($priceRange), true);

        // $homeZoneCategoryModel = new HomeZoneCategoryModel();
        try {
            $products = $productModel->select([
                'af_products.id AS id',
                'af_products.name AS name',
                'af_products.actual_price AS actual_price',
                'af_products.discounted_percent AS discounted_percent',
                'af_products.increase_percent AS increase_percent',
                'af_brands.id AS brand_id',
                'af_brands.name AS brand_name',
                'af_products.properties AS properties',
                'af_product_images.path_360x360 AS image'
            ])
                ->join('af_home_zone_category', 'af_products.home_zone_category_id = af_home_zone_category.id')
                ->join('af_product_images', 'af_product_images.product_id = af_products.id')
                ->join('af_brands', 'af_brands.id = af_products.brand_id')
                ->where('af_home_zone_category.slug', $slug)
                ->where('af_product_images.image_index', 0)
                ->findAll(10);

            // $products = $products->paginate($perPage, 'products', $currentPage);
            $validation = &$productModel;
            if ($productModel->db->error()['code']) {
                throw new Exception($productModel->db->error()['message'], 500);
            }
            if (!empty($productModel->errors())) {
                throw new Exception('Validation', 400);
            }

            // $pageCount = $productModel->pager->getPageCount('products');
            // var_dump($productModel->db->getLastQuery());exit;

            if ($products === null) {
                throw new Exception('Product not found', 404);
            }
            // $products = array_map(array($this, 'get_discounted_price'), $products);
            helper('products');
            $products = array_map('get_discounted_price', $products);

            foreach ($products as $key => &$product) {

                $wishlistmodel = new WishlistModel();
                $wishlistdata = $wishlistmodel->where('customer_id', $customerid)->where('product_id', $product['id'])->first();

                if (!empty($wishlistdata)) {
                    $products[$key]['is_wishlist'] = 1;
                    $products[$key]['whishlist_id'] = $wishlistdata['id'];
                } else {
                    $products[$key]['is_wishlist'] = 0;
                }


                /* if ($product['increase_percent'] != '') {
                    $products[$key]['actual_price'] = $product['actual_price'] + ($product['actual_price'] / 100 * $product['increase_percent']);
                }
                if ($product['discounted_percent'] != '') {
                    $products[$key]['discounted_price'] = $product['actual_price'] - ($product['actual_price'] / 100 * $product['discounted_percent']);
                } */
            }
            // $products[$key]['description'] = json_decode($products[$key]['description'], true);
            // $products[$key]['features'] = json_decode($products[$key]['features'], true);
            // $homezoneCategoryModel = new HomeZoneCategoryModel();
            // $homezoneData = $homezoneCategoryModel->select(['features', 'description'])->where('slug', $slug)->first();
            // $validation = &$homezoneCategoryModel;
            // if ($validation->db->error()['code']) {
            //     throw new Exception($validation->db->error()['message'], 500);
            // }
            // if (!empty($validation->errors())) {
            //     throw new Exception('Validation', 400);
            // }

            $response = [
                'data' => $products,
                // 'total_pages' => $pageCount,
            ];
            // if (!empty($homezoneData)) {
            //     $response['features'] = json_decode($homezoneData['features'], true);
            //     $response['description'] = json_decode($homezoneData['description'], true);
            // }
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

    public function get5ProductBySubCategory($slug)
    {
        $productModel = new ProductModel();
        $productImageModel = new ProductImageModel();
        // $homeZoneAppliancesModel = new HomeZoneAppliancesModel();
        $homeZoneCategoryModel = new HomeZoneCategoryModel();

        try {
            // $homezoneAppliancesModel = new HomeZoneAppliancesModel();
            $homezoneData = $homeZoneCategoryModel->findAll();

            $productModel = new ProductModel();
            $products = array();
            helper('products');
            foreach ($homezoneData as $record) {
                $homezoneName = $record['title'];
                $homezoneID = $record['id'];
                $productCount = $productModel->where('home_zone_category_id', $homezoneID)->countAllResults();
                $homezoneProducts = $productModel->select([
                    'af_products.id AS id',
                    'af_products.name AS name',
                    'af_products.actual_price AS actual_price',
                    'af_products.discounted_percent AS discounted_percent',
                    'af_products.increase_percent AS increase_percent',
                    'af_products.properties AS properties',
                    'af_product_images.path_360x360 AS image',
                    'af_brands.id AS brand_id',
                    'af_brands.name AS brand_name'
                ])
                    ->join('af_home_zone_category', 'af_products.home_zone_category_id = af_home_zone_category.id')
                    ->join('af_product_images', 'af_product_images.product_id = af_products.id')
                    ->join('af_brands', 'af_brands.id = af_products.brand_id')
                    ->where('af_home_zone_category.slug', $slug)
                    ->where('af_product_images.image_index', 0)->orderBy('id', 'DESC')->findAll(ceil(5));
                $validation = &$productModel;
                if ($productModel->db->error()['code']) {
                    throw new Exception($productModel->db->error()['message'], 500);
                }
                if (!empty($productModel->errors())) {
                    throw new Exception('Validation', 400);
                }
                $homezoneProducts = array_map('get_discounted_price', $homezoneProducts);

                $products[] = [
                    'title' => $homezoneName,
                    'data' => $homezoneProducts,
                ];
            }

            if ($products === null) {
                throw new Exception('Product not found', 404);
            }

            $response = [
                'data' => $products,
            ];

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

    public function get10PercentProductsHomeappliances()
    {
        // $customerid = $this->request->getVar('customer_id');
        // $currentPage = $this->request->getVar('page');
        // $perPage = $this->request->getVar('per_page_count');

        // $homeZoneAppliancesModel = new HomeZoneAppliancesModel();
        try {
            $homezoneAppliancesModel = new HomeZoneAppliancesModel();
            $homezoneData = $homezoneAppliancesModel->findAll();

            $productModel = new ProductModel();
            $products = array();
            helper('products');
            foreach ($homezoneData as $record) {
                $homezoneName = $record['title'];
                $homezoneID = $record['id'];
                $slug = $record['slug'];
                $productCount = $productModel->where('home_zone_appliances_id', $homezoneID)->countAllResults();
                $homezoneProducts = $productModel->select([
                    'af_products.id AS id',
                    'af_products.name AS name',
                    'af_products.actual_price AS actual_price',
                    'af_products.discounted_percent AS discounted_percent',
                    'af_products.increase_percent AS increase_percent',
                    'af_products.properties AS properties',
                    'af_product_images.path_360x360 AS image',
                    'af_brands.id AS brand_id',
                    'af_brands.name AS brand_name'
                ])
                    ->join('af_home_zone_appliances', 'af_products.home_zone_appliances_id = af_home_zone_appliances.id')
                    ->join('af_product_images', 'af_product_images.product_id = af_products.id')
                    ->join('af_brands', 'af_brands.id = af_products.brand_id')
                    ->where('af_home_zone_appliances.id', $homezoneID)
                    ->where('af_product_images.image_index', 0)->orderBy('id', 'DESC')->findAll(ceil($productCount / 100 * 30));
                $validation = &$productModel;
                if ($productModel->db->error()['code']) {
                    throw new Exception($productModel->db->error()['message'], 500);
                }
                if (!empty($productModel->errors())) {
                    throw new Exception('Validation', 400);
                }
                $homezoneProducts = array_map('get_discounted_price', $homezoneProducts);

                $products[] = [
                    'title' => $homezoneName,
                    'slug' => $slug,
                    'data' => $homezoneProducts,
                ];

                /* foreach ($products as $key => $product) {
    
                    $wishlistmodel = new WishlistModel();
                    $wishlistdata = $wishlistmodel->where('customer_id', $customerid)->where('product_id', $product['id'])->first();
    
                    if (!empty($wishlistdata)) {
                        $products[$key]['is_wishlist'] = 1;
                        $products[$key]['whishlist_id'] = $wishlistdata['id'];
                    } else {
                        $products[$key]['is_wishlist'] = 0;
                    }
                } */
            }

            // var_dump($productModel->db->getLastQuery());exit;

            if ($products === null) {
                throw new Exception('Product not found', 404);
            }
            /*  $homezoneAppliancesModel = new HomeZoneAppliancesModel();
             $homezoneData = $homezoneAppliancesModel->select(['features', 'description'])->where('slug', $slug)->first();
             $validation = &$homezoneAppliancesModel;
             if ($validation->db->error()['code']) {
                 throw new Exception($validation->db->error()['message'], 500);
             }
             if (!empty($validation->errors())) {
                 throw new Exception('Validation', 400);
             } */

            $response = [
                'data' => $products,
            ];
            /* if (!empty($homezoneData)) {
                $response['features'] = json_decode($homezoneData['features'], true);
                $response['description'] = json_decode($homezoneData['description'], true);
            } */
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
    function UpdateProductVenderName()
    {
        $proid = $this->request->getVar("id");
        $vendername = $this->request->getVar("vender_name");
        $productModel = new ProductModel();
        $rest =  $productModel->update($proid, [
            "vender_name" => $vendername
        ]);
        if ($rest) {
            $response = [
                "Status" => 200,
                "Msg" => "Data Updated Successfully"
            ];
        } else {
            $response = [
                "Status" => 500,
                "Msg" => "Data Not Updated Successfully"

            ];
        }
        return $this->respond($response, 200);
    }
    function SearchProduct()
    {
        $serach = $this->request->getVar('serach');
        $ProductModel = new ProductModel();
        $products = $ProductModel->select(['id', 'name', 'actual_price', 'home_zone_appliances_id', 'product_code', 'quantity', 'status', 'created_at', 'vendor_name'])->like('name', $serach)->orLike('product_code', $serach)->orLike('vendor_name', $serach)->findAll();

        if (!empty($products)) {
            foreach ($products as &$product) {
                $productImageModel = new ProductImageModel();
                $validation = &$productImageModel;
                $image = $productImageModel->where('product_id', $product['id'])->findColumn('path_360x360');
                if ($validation->db->error()['code']) {
                    throw new Exception($validation->db->error()['message'], 500);
                }
                if (!empty($validation->errors())) {
                    throw new Exception('Validation', 400);
                }
                if (!empty($image)) {
                    $product['thumbnail'] = $image[0];
                }
            }
        }
        return $this->respond([
            "status" => 200,
            "Data" => $products
        ]);
    }
}
