<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\BannersModel;
use App\Models\HomeZoneAppliancesModel;
use App\Models\HomeZoneCategoryModel;
use App\Models\OrderProductsModel;
use App\Models\ProductImageModel;
use App\Models\ProductModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Database\Exceptions\DatabaseException as ExceptionsDatabaseException;
use CodeIgniter\DatabaseExceptions\DatabaseException;
use Exception;
use Kreait\Firebase\Exception\DatabaseException as ExceptionDatabaseException;

class HomeZoneAppliancesController extends BaseController
{
    use ResponseTrait;
    public function createHomeZoneAppliances()
    {
        try {
            // $session = session();
            $homeZoneAppliancesModel = new HomeZoneAppliancesModel();
            $validation = \Config\Services::validation();
            $statusCode = 200;

            $userBackendToFrontendAttrs = [
                'title' => 'title',
                'description' => 'description',
                'features' => 'features',
                'image' => 'image',
            ];

            /* $image = \Config\Services::image();
            $productImage = $this->request->getFile('image');
            // $path_900x500 =  "public/uploads/products/900x500/";
            $path_128x128 = "public/uploads/homezone-appliances/128x128/";
            $imageName = bin2hex(random_bytes(10)) . time() . '.jpeg';
            // $productImagesData = array();
            // $productID = $this->request->getVar('product_id');
            $image->withFile($productImage)
                // ->resize(1920, 1156, false)
                ->save($path_128x128 . $imageName, 90); */

            $HomeZoneAppliancesData = [];
            foreach ($userBackendToFrontendAttrs as $backendAttr => $frontendAttr) {
                $HomeZoneAppliancesData[$backendAttr] = $this->request->getVar($frontendAttr);
            }



            helper('slug');
            $HomeZoneAppliancesData['slug'] = slugify($HomeZoneAppliancesData['title']);
            // $HomeZoneAppliancesData['image'] = $path_128x128 . $imageName;
            $homeZoneAppliancesModel->insert($HomeZoneAppliancesData);

            //Banner Image
            $homeZoneAppliancesID = $homeZoneAppliancesModel->db->insertID();
            $banner_image = $this->request->getVar('banner_image');
            $banner_image = json_decode($banner_image, true);
            //   var_dump($banner_image);
            // return $this->respond(var_dump($banner_image));exit;
            if (!empty($banner_image)) {
                // echo $banner_image[0];
                // die;
                $addhomeZoneAppliancesID = function ($item) use ($homeZoneAppliancesID) {
                    $item['home_zone_appliances_id'] = $homeZoneAppliancesID;
                    $item['image_index'] = 0;
                    return $item;
                };
                $banner_image = array_map($addhomeZoneAppliancesID, $banner_image);

                $bannersModel = new BannersModel();
                $validation = &$bannersModel;
                $bannersModel->insertBatch($banner_image);
                if (!empty($bannersModel->errors())) {
                    throw new Exception('Validation', 400);
                }
                if ($bannersModel->db->error()['code']) {
                    throw new Exception($bannersModel->db->error()['message'], 500);
                }
                // echo $bannersModel->db->getLastQuery();
                // exit;
            }



            if (!empty($homeZoneAppliancesModel->errors())) {
                throw new Exception('Validation', 400);
            }
            if ($homeZoneAppliancesModel->db->error()['code']) {
                throw new Exception($homeZoneAppliancesModel->db->error()['message'], 500);
            }
            $statusCode = 200;
            $response = (object) [
                'message' => 'Home Zone Appliances Created Successfully.',
                'homeZoneAppliances_id' => $homeZoneAppliancesID,
            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = (object) [
                'error' => $e->getCode() === 400 ? ['validation' => $validation->getErrors()] : $e->getMessage()
            ];
        } catch (ExceptionsDatabaseException $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = (object) [
                'error' => $e->getCode() === 400 ? ['validation' => $validation->getErrors()] : $e->getMessage()
            ];
        }

        $response->status = $statusCode;
        return $this->respond($response, $statusCode);
    }

    public function getHomeZoneAppliances()
    {

        $homeZoneAppliancesModel = new HomeZoneAppliancesModel();
        $homeZoneCategoryModel = new HomeZoneCategoryModel();
        // $headers = $this->request->getHeaders();
        // print_r($headers);
        // die;


        try {
            $data = $homeZoneAppliancesModel->findAll();

            $productModel = new ProductModel();
            foreach ($data as $key => $record) {
                $homeZoneCategory = $homeZoneCategoryModel->where('home_zone_appliances_id', $record['id'])->findAll();
                $data[$key]['category'] = $homeZoneCategory;
                $data[$key]['product_count'] = $productModel->where('home_zone_appliances_id', $record['id'])->countAllResults();
            }
            if (!empty($homeZoneAppliancesModel->errors())) {
                // $validation = &$accountfaqModel;
                throw new Exception('Validation', 400);
            }
            if ($homeZoneAppliancesModel->db->error()['code'])
                throw new Exception($homeZoneAppliancesModel->db->error()['message'], 500);
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
                'error' => $e->getCode() === 400 ? $homeZoneAppliancesModel->errors() : $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    public function getHomeZoneAppliancesWithOrderCount()
    {

        $homeZoneAppliancesModel = new HomeZoneAppliancesModel();
        $homeZoneCategoryModel = new HomeZoneCategoryModel();
        $orderProductsModel = new OrderProductsModel();


        try {
            $data = $homeZoneAppliancesModel->findAll();

            $productModel = new ProductModel();
            foreach ($data as $key => $record) {
                $count = [];
                $homeZoneCategory = $homeZoneCategoryModel->where('home_zone_appliances_id', $record['id'])->first();
                $data[$key]['category'] = $homeZoneCategory;

                $prodata = $productModel->where('home_zone_appliances_id', $record['id'])->findAll();
                foreach ($prodata as $pr) {
                    $count[] = $orderProductsModel->where("product_id", $pr["id"])->countAllResults();
                }
                $data[$key]['product_count'] = array_sum($count);
            }
            if (!empty($homeZoneAppliancesModel->errors())) {
                // $validation = &$accountfaqModel;
                throw new Exception('Validation', 400);
            }
            if ($homeZoneAppliancesModel->db->error()['code'])
                throw new Exception($homeZoneAppliancesModel->db->error()['message'], 500);
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
                'error' => $e->getCode() === 400 ? $homeZoneAppliancesModel->errors() : $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }
    public function updateHomeZoneAppliances($id)
    {
        try {
            // $session = session();
            $homeZoneAppliancesModel = new HomeZoneAppliancesModel();
            $validation = &$homeZoneAppliancesModel;
            // $statusCode = 200;

            $userBackendToFrontendAttrs = [
                'title' => 'title',
                'description' => 'description',
                'features' => 'features',
                'image' => 'image',
            ];

            $HomeZoneAppliancesData = array();
            foreach ($userBackendToFrontendAttrs as $backendAttr => $frontendAttr) {
                $HomeZoneAppliancesData[$backendAttr] = $this->request->getVar($frontendAttr);
            }
            // $HomeZoneAppliancesData['id'] = $id;
            // var_dump($HomeZoneAppliancesData);
            $homeZoneAppliancesModel->update($id, $HomeZoneAppliancesData); // update the Customer with the given ID
            if (!empty($homeZoneAppliancesModel->errors())) {
                throw new Exception('Validation', 400);
            }
            if ($homeZoneAppliancesModel->db->error()['code']) {
                throw new Exception($homeZoneAppliancesModel->db->error()['message'], 500);
            }
            //BannerImage

            $BannersImageData = $this->request->getVar('banner_image');
            $BannersImageData = json_decode($BannersImageData, true);
            // var_dump($BannersImageData);
            // die;

            if (!empty($BannersImageData)) {
                $BannerImageModel = new BannersModel();
                foreach ($BannersImageData as $key => &$BannerImage) {
                    $BannerImage['image_index'] = $key;
                }
                $BannersImageData_Insert = array_filter($BannersImageData, function ($imageData) {
                    return ($imageData['id'] == null);
                });
                $BannersImageData_Update = array_filter($BannersImageData, function ($imageData) {
                    return ($imageData['id'] != null);
                });
                if (!empty($BannersImageData_Insert)) {
                    $addhomeZoneAppliancesID = function ($banner) use ($id) {
                        $banner['home_zone_appliances_id'] = $id;
                        return $banner;
                    };
                    $BannersImageData_Insert  = array_map($addhomeZoneAppliancesID, $BannersImageData_Insert);
                    $validation = &$BannerImageModel;
                    $BannerImageModel->insertBatch($BannersImageData_Insert);
                    if ($BannerImageModel->db->error()['code']) {
                        throw new Exception($BannerImageModel->db->error()['message'], 500);
                    }
                    if (!empty($BannerImageModel->errors())) {
                        throw new Exception('Validation', 400);
                    }
                }

                // Update Image
                if (!empty($BannersImageData_Update)) {

                    $BannerImageModel->updateBatch($BannersImageData_Update, 'id');
                    if ($BannerImageModel->db->error()['code']) {
                        throw new Exception($BannerImageModel->db->error()['message'], 500);
                    }
                    if (!empty($BannerImageModel->errors())) {
                        throw new Exception('Validation', 400);
                    }
                }
            }
            $statusCode = 200;
            $response = [
                'message' => 'Home zone Appliances updated successfully.',
            ];
            /* if ($homeZoneAppliancesModel->db->affectedRows() == 1) {
            } else {
                throw new Exception('Nothing to update', 200);
            } */
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

    public function deleteHomeZoneAppliances($id)
    {
        try {
            $homeZoneAppliancesModel = new HomeZoneAppliancesModel();
            $homeZoneAppliancesModel->delete($id);

            if ($homeZoneAppliancesModel->db->affectedRows() == 1) {
                $statusCode = 200;
                $response = [
                    'message' => 'Home Zone deleted successfully.'
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

    public function updateHomeZoneCategoryImage()
    {
        $image = \Config\Services::image();
        try {
            $statusCode = 200;
            $homeZoneImage = $this->request->getFile('image');
            $path_128x128 = "public/uploads/homezone-appliances/128x128/";
            $imageName = bin2hex(random_bytes(10)) . time() . '.png';
            // $productImagesData = array();
            // $productID = $this->request->getVar('product_id');
            $image->withFile($homeZoneImage)
                ->resize(360, 360, false)
                ->convert(IMAGETYPE_PNG)
                ->save($path_128x128 . $imageName, 90);

            $image_path = $path_128x128 . $imageName;

            $response = [
                'message' => 'Home Zone Image saved successfully.',
                'image_path' => $image_path,
            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    public function deleteHomeZoneImage()
    {
        $imagePath = $this->request->getVar('image');
        $imagePath = json_decode(json_encode($imagePath), true);
        // var_dump($imagePath);exit;
        if (!empty($imagePath)) {
            foreach ($imagePath as $key => $path) {
                if (file_exists($path)) {
                    unlink($path);
                }
            }
        }
        $statusCode = 200;
        $response = [
            'message' => 'Image deleted successfully.'
        ];
        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    public function getHomeZoneAppliancesWithCategory()
    {

        $homeZoneAppliancesModel = new HomeZoneAppliancesModel();
        $homeZoneCategoryModel = new HomeZoneAppliancesModel();

        try {
            $data = $homeZoneAppliancesModel->select([
                'af_home_zone_category.id AS id',
                'af_home_zone_category.title AS title',
                'af_home_zone_appliances.slug AS slug',
            ])
                ->join('af_home_zone_category', 'af_home_zone_appliances.id = af_home_zone_category.home_zone_appliances_id')
                ->findAll();

            // foreach ($data as $key => $product) {
            //     $image = $productImageModel->where('id', $product['id'])->findColumn('path_900x500')[0];
            //     $data[$key]['image'] = $image;
            // }
            if (!empty($homeZoneAppliancesModel->errors())) {
                // $validation = &$accountfaqModel;
                throw new Exception('Validation', 400);
            }
            if ($homeZoneAppliancesModel->db->error()['code'])
                throw new Exception($homeZoneAppliancesModel->db->error()['message'], 500);
            foreach ($data as &$value) {
                $value['features'] = json_decode($value['features']);
                $value['description'] = json_decode($value['description']);
            }
            $statusCode = 200;
            $response = [
                "data" => $data
            ];
        } catch (\Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? $homeZoneAppliancesModel->errors() : $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    public function getHomeZoneAppliancesById($slug)
    {
        try {
            $homeZoneAppliancesModel = new HomeZoneAppliancesModel();
            $bannersModel = new BannersModel();
            $homeZoneCategoryModel = new HomeZoneCategoryModel();
            $statusCode = 200;
            $id = $this->request->getVar('id');


            $homeZoneAppliances = $homeZoneAppliancesModel->where('slug', $slug)->first();
            if (!empty($homeZoneAppliances)) {
                $statusCode = 200;
                $id = $homeZoneAppliances['id'];
                $banners = $bannersModel->where('home_zone_appliances_id', $id)->findAll();
                $HomeZoneSubCategory = $homeZoneCategoryModel->where('home_zone_appliances_id', $id)->findAll();

                $response = [
                    'Data' => $homeZoneAppliances,
                    'Banners' => $banners,
                    'HomeZone_Sub_Category' => $HomeZoneSubCategory,
                ];
            } else {
                $statusCode = 404;
                $response = [
                    'error' => 'Home Zone Appliance not found.',
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

    public function getBestHomeZoneAppliancesDeals($id)
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
                'af_products.discounted_percent AS discounted_percent',
                'af_products.increase_percent AS increase_percent',
                'af_brands.id AS brand_id',
                'af_brands.name AS brand_name',
                'af_products.properties AS properties',
                'af_product_images.path_360x360 AS image'
            ])
                ->join('af_home_zone_appliances', 'af_products.home_zone_appliances_id = af_home_zone_appliances.id')
                ->join('af_product_images', 'af_product_images.product_id = af_products.id')
                ->join('af_brands', 'af_brands.id = af_products.brand_id')
                ->where('af_home_zone_appliances.id', $id)
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

            helper('products');
            $products = array_map('get_discounted_price', $products);
            /* foreach ($products as $key => $product) {

                if ($product['discounted_percent'] != '') {
                    $products[$key]['discounted_price'] = $product['actual_price'] - ($product['actual_price'] / 100 * $product['discounted_percent']);
                }

                // $products[$key]['description'] = json_decode($products[$key]['description'], true);
                // $products[$key]['features'] = json_decode($products[$key]['features'], true);
            } */

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
}
