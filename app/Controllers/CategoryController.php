<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\CategoryModel;
use App\Models\SubCategoryModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Database\Exceptions\DatabaseException;
use Exception;

class CategoryController extends BaseController
{
    use ResponseTrait;
    public function createCategory()
    {
        try {
            $categoryModel = new CategoryModel();
            $validation = \Config\Services::validation();
            $statusCode = 200;
            
            $userBackendToFrontendAttrs = [
                'title' => 'title',
                'description' => 'description',
                'features' => 'features',
                'image' => 'image'
            ];
            $categoryData = [];
            foreach ($userBackendToFrontendAttrs as $backendAttr => $frontendAttr) {
                $categoryData[$backendAttr] = $this->request->getVar($frontendAttr);
            }  
       
            helper('slug');
            $categoryData['slug'] = slugify($categoryData['title']);
            // $categoryData['image'] = $path_128x128 . $imageName;
            $categoryModel->insert($categoryData);

            if (!empty($categoryModel->errors())) {
                throw new Exception('Validation', 400);
            }
            if ($categoryModel->db->error()['code']) {
                throw new Exception($categoryModel->db->error()['message'], 500);
            }
            $statusCode = 200;
            $response = (object) [
                'message' => 'Category Created Successfully.',
                'Category_id' => $categoryModel->db->insertID(),
            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = (object) [
                'error' => $e->getCode() === 400 ? ['validation' => $validation->getErrors()] : $e->getMessage()
            ];
        } catch (DatabaseException $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = (object) [
                'error' => $e->getCode() === 400 ? ['validation' => $validation->getErrors()] : $e->getMessage()
            ];
        }

        $response->status = $statusCode;
        return $this->respond($response, $statusCode);
    }

    public function getAllCategory()
    {

        $CategoryModel = new CategoryModel();
        $SubCategoryModel = new SubCategoryModel();
        try {
            $data = $CategoryModel->findAll();


            // foreach ($data as $key => $product) {
            //     $homeZoneCategory = $SubCategoryModel->where('home_zone_appliances_id', $product['id'])->findAll();
            //     $data[$key]['category'] = $homeZoneCategory;
            // }
            if (!empty($CategoryModel->errors())) {
                // $validation = &$accountfaqModel;
                throw new Exception('Validation', 400);
            }
            if ($CategoryModel->db->error()['code'])
                throw new Exception($CategoryModel->db->error()['message'], 500);
            foreach ($data as &$value) {
                $value['features'] = json_decode($value['features']);
                $value['description'] = json_decode($value['description']);
            }
            $statusCode = 200;
            $response = [
                "data" => $data
            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? $CategoryModel->errors() : $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    public function updateCategory($id)
    {
        try {
            $CategoryModel = new CategoryModel();
            $validation = &$CategoryModel;
            // $statusCode = 200;

            $userBackendToFrontendAttrs = [
                'title' => 'title',
                'description' => 'description',
                'features' => 'features',
                'image' => 'image',
            ];

            $CategoryData = array();
            foreach ($userBackendToFrontendAttrs as $backendAttr => $frontendAttr) {
                $CategoryData[$backendAttr] = $this->request->getVar($frontendAttr);
            }
           
            $CategoryModel->update($id, $CategoryData); // update the Customer with the given ID
            if (!empty($CategoryModel->errors())) {
                throw new Exception('Validation', 400);
            }
            if ($CategoryModel->db->error()['code']) {
                throw new Exception($CategoryModel->db->error()['message'], 500);
            }
            $statusCode = 200;
            $response = [
                'message' => 'Category updated successfully.',
            ];
           
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

    public function deleteCategory($id)
    {
        try {
            $CategoryModel = new CategoryModel();
            $CategoryModel->delete($id);

            if ($CategoryModel->db->affectedRows() == 1) {
                $statusCode = 200;
                $response = [
                    'message' => 'Category deleted successfully.'
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

        public function getCategoryById($id)
    {
        try {
            $CategoryModel = new CategoryModel();
            // $bannersModel = new BannersModel();
            // $SubCategoryModel = new SubCategoryModel();
            $statusCode = 200;


            $Category = $CategoryModel->where('id', $id)->first();
            if (!empty($Category)) {
                $statusCode = 200;
                $id = $Category['id'];
                // $banners = $bannersModel->where('home_zone_appliances_id', $id)->findAll();
                // $HomeZoneSubCategory = $SubCategoryModel->where('home_zone_appliances_id', $id)->findAll();

                $response = [
                    'data' => $Category,
                    // 'Banners' => $banners,
                    // 'HomeZone_Sub_Category' => $HomeZoneSubCategory,
                ];
            } else {
                $statusCode = 404;
                $response = [
                    'error' => 'Category not found.',
                ];
            }
        } catch (Exception $e) {
            $statusCode = 500;
            $response = [
                'error' => $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    // public function updateHomeZoneCategoryImage()
    // {
    //     $image = \Config\Services::image();
    //     try {
    //         $statusCode = 200;
    //         $homeZoneImage = $this->request->getFile('image');
    //         $path_128x128 = "public/uploads/homezone-appliances/128x128/";
    //         $imageName = bin2hex(random_bytes(10)) . time() . '.png';
    //         // $productImagesData = array();
    //         // $productID = $this->request->getVar('product_id');
    //         $image->withFile($homeZoneImage)
    //             ->resize(360, 360, false)
    //             ->convert(IMAGETYPE_PNG)
    //             ->save($path_128x128 . $imageName, 90);

    //         $image_path = $path_128x128 . $imageName;

    //         $response = [
    //             'message' => 'Home Zone Image saved successfully.',
    //             'image_path' => $image_path,
    //         ];
    //     } catch (Exception $e) {
    //         $statusCode = $e->getCode() === 400 ? 400 : 500;
    //         $response = [
    //             'error' => $e->getMessage()
    //         ];
    //     }

    //     $response['status'] = $statusCode;
    //     return $this->respond($response, $statusCode);
    // }

    // public function deleteHomeZoneImage()
    // {
    //     $imagePath = $this->request->getVar('image');
    //     $imagePath = json_decode(json_encode($imagePath), true);
    //     // var_dump($imagePath);exit;
    //     foreach ($imagePath as $key => $path) {
    //         if (file_exists($path)) {
    //             unlink($path);
    //         }
    //     }
    //     $statusCode = 200;
    //     $response = [
    //         'message' => 'Image deleted successfully.'
    //     ];
    //     $response['status'] = $statusCode;
    //     return $this->respond($response, $statusCode);
    // }

    // public function getCategoryWithCategory()
    // {

    //     $CategoryModel = new CategoryModel();
    //     $SubCategoryModel = new CategoryModel();

    //     try {
    //         $data = $CategoryModel->select([
    //             'af_home_zone_category.id AS id',
    //             'af_home_zone_category.title AS title',
    //             'af_home_zone_appliances.slug AS slug',
    //         ])
    //             ->join('af_home_zone_category', 'af_home_zone_appliances.id = af_home_zone_category.home_zone_appliances_id')
    //             ->findAll();

    //         // foreach ($data as $key => $product) {
    //         //     $image = $productImageModel->where('id', $product['id'])->findColumn('path_900x500')[0];
    //         //     $data[$key]['image'] = $image;
    //         // }
    //         if (!empty($CategoryModel->errors())) {
    //             // $validation = &$accountfaqModel;
    //             throw new Exception('Validation', 400);
    //         }
    //         if ($CategoryModel->db->error()['code'])
    //             throw new Exception($CategoryModel->db->error()['message'], 500);
    //         foreach ($data as &$value) {
    //             $value['features'] = json_decode($value['features']);
    //             $value['description'] = json_decode($value['description']);
    //         }
    //         $statusCode = 200;
    //         $response = [
    //             "data" => $data
    //         ];
    //     } catch (Exception $e) {
    //         $statusCode = $e->getCode() === 400 ? 400 : 500;
    //         $response = [
    //             'error' => $e->getCode() === 400 ? $CategoryModel->errors() : $e->getMessage()
    //         ];
    //     }

    //     $response['status'] = $statusCode;
    //     return $this->respond($response, $statusCode);
    // }



    // public function getBestCategoryDeals($id)
    // {
    //     $productModel = new ProductModel();
    //     $productImageModel = new ProductImageModel();
    //     $customerid = $this->request->getVar('customer_id');
    //     // $CategoryModel = new CategoryModel();
    //     try {
    //         $products = $productModel->select([
    //             'af_products.id AS id',
    //             'af_products.name AS name',
    //             'af_products.actual_price AS actual_price',
    //             'af_products.discounted_percent AS discounted_percent',
    //             'af_brands.id AS brand_id',
    //             'af_brands.name AS brand_name',
    //             'af_products.properties AS properties',
    //             'af_product_images.path_900x500 AS image'
    //         ])
    //             ->join('af_home_zone_appliances', 'af_products.home_zone_appliances_id = af_home_zone_appliances.id')
    //             ->join('af_product_images', 'af_product_images.product_id = af_products.id')
    //             ->join('af_brands', 'af_brands.id = af_products.brand_id')
    //             ->where('af_home_zone_appliances.id', $id)
    //             ->where('af_product_images.image_index', 0)
    //             ->orderBy('af_products.actual_price', 'ASC')->findAll(10);
    //         // echo count($products);
    //         $validation = &$productModel;
    //         if ($productModel->db->error()['code']) {
    //             throw new Exception($productModel->db->error()['message'], 500);
    //         }
    //         if (!empty($productModel->errors())) {
    //             throw new Exception('Validation', 400);
    //         }
    //         // $products = array_slice($products, 0, 10);
    //         // var_dump($productModel->db->getLastQuery());exit;

    //         if ($products === null) {
    //             throw new Exception('Product not found', 404);
    //         }
    //         foreach ($products as $key => $product) {

    //             if ($product['discounted_percent'] != '') {
    //                 $products[$key]['discounted_price'] = $product['actual_price'] - ($product['actual_price'] / 100 * $product['discounted_percent']);
    //             }

    //             // $products[$key]['description'] = json_decode($products[$key]['description'], true);
    //             // $products[$key]['features'] = json_decode($products[$key]['features'], true);
    //         }

    //         $response = [
    //             'data' => $products,
    //         ];
    //         if (!empty($homezoneData)) {
    //             $response['features'] = json_decode($homezoneData['features'], true);
    //             $response['description'] = json_decode($homezoneData['description'], true);
    //         }
    //         $statusCode = 200;
    //     } catch (Exception $e) {
    //         $statusCode = $e->getCode() === 400 ? 400 : 500;
    //         $response = [
    //             'error' => $e->getCode() === 400 ? $validation->errors() : $e->getMessage()
    //         ];
    //     }

    //     $response['status'] = $statusCode;
    //     return $this->respond($response, $statusCode);
    // }
}
