<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\BrandModel;
use App\Models\HomeZoneAppliancesModel;
use App\Models\HomeZoneCategoryModel;
use App\Models\ProductModel;
use CodeIgniter\API\ResponseTrait;
use Exception;

class BrandController extends BaseController
{
    use ResponseTrait;
    public function createBrand()
    {
        try {
            $brandModel = new BrandModel();
            $validation = \Config\Services::validation();

            $BrandData = [
                'name' => $this->request->getVar('name'),
                'description' => $this->request->getVar('description')
            ];
            helper('slug');
            $BrandData['slug'] = slugify($BrandData['name']);

            $brandModel->insert($BrandData);

            if (!empty($validation->getErrors())) {
                throw new Exception('Validation', 400);
            }

            if ($brandModel->db->error()['code']) {
                throw new Exception($brandModel->db->error()['message'], 500);
            }

            if ($brandModel->db->affectedRows() == 1) {
                $response = [
                    'status' => 200,
                    'message' => 'Brand created successfully.',
                    'Brand_id' => $brandModel->db->insertID()
                ];
            }
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'status' => $statusCode,
                'error' => $e->getCode() === 400 ? ['validation' => $validation->getErrors()] : $e->getMessage()
            ];
        }

        return $this->respond($response, $response['status']);
    }

    public function getBrandById($id)
    {
        try {
            $brandModel = new BrandModel();
            $Brand = $brandModel->find($id);

            if (!$Brand) {
                throw new Exception('Brand not found', 404);
            }

            $response = [
                'status' => 200,
                'Brand' => $Brand
            ];
        } catch (Exception $e) {
            $response = [
                'status' => $e->getCode() === 404 ? 404 : 500,
                'error' => $e->getCode() === 404 ? 'Brand not found' : $e->getMessage()
            ];
        }

        return $this->respond($response, $response['status']);
    }
    public function getAllBrand()
    {
        try {
            $brandModel = new BrandModel();
            $Brand = $brandModel->findAll();

            if (!$Brand) {
                throw new Exception('Brand not found', 404);
            }

            $response = [
                'status' => 200,
                'Brand' => $Brand
            ];
        } catch (Exception $e) {
            $response = [
                'status' => $e->getCode() === 404 ? 404 : 500,
                'error' => $e->getCode() === 404 ? 'Brand not found' : $e->getMessage()
            ];
        }

        return $this->respond($response, $response['status']);
    }

    public function updateBrand($id)
    {
        try {
            $brandModel = new BrandModel();
            $validation = \Config\Services::validation();
            $Brand = $brandModel->find($id);

            if (!$Brand) {
                throw new Exception('Brand not found', 404);
            }

            $BrandData = [
                'name' => $this->request->getVar('name'),
                'description' => $this->request->getVar('description')
            ];

            $brandModel->update($id, $BrandData);

            if (!empty($validation->getErrors())) {
                throw new Exception('Validation', 400);
            }

            if ($brandModel->db->error()['code']) {
                throw new Exception($brandModel->db->error()['message'], 500);
            }

            $response = [
                'status' => 200,
                'message' => 'Brand updated successfully.'
            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : ($e->getCode() === 404 ? 404 : 500);
            $response = [
                'status' => $statusCode,
                'error' => $e->getCode() === 400 ? ['validation' => $validation->getErrors()] : $e->getMessage()
            ];
        }

        return $this->respond($response, $response['status']);
    }

    public function deleteBrand($id)
    {
        try {
            $brandModel = new BrandModel();
            $Brand = $brandModel->find($id);

            if (!$Brand) {
                throw new Exception('Brand not found', 404);
            }

            $brandModel->delete($id);

            $response = [
                'status' => 200,
                'message' => 'Brand deleted successfully.'
            ];
        } catch (Exception $e) {
            $response = [
                'status' => $e->getCode() === 404 ? 404 : 500,
                'error' => $e->getCode() === 404 ? 'Brand not found' : $e->getMessage()
            ];
        }

        return $this->respond($response, $response['status']);
    }

    public function getBrandBySlug($slug)
    {
        try {
            $brandModel = new BrandModel();
            $brand = $brandModel->where('slug', $slug)->first();

            if (!$brand) {
                throw new Exception('Brand not found', 404);
            }

            $productModel = new ProductModel();
            $products = $productModel->select([
                'af_products.id AS id',
                'af_products.home_zone_appliances_id AS home_zone_appliances_id',
                'af_products.name AS name',
                'af_products.actual_price AS actual_price',
                'af_products.discounted_percent AS discounted_percent',
                'af_brands.id AS brand_id',
                'af_brands.name AS brand_name',
                'af_products.properties AS properties',
                'af_product_images.path_360x360 AS image'
            ])
                ->join('af_home_zone_category', 'af_products.home_zone_category_id = af_home_zone_category.id')
                ->join('af_product_images', 'af_product_images.product_id = af_products.id')
                ->join('af_brands', 'af_brands.id = af_products.brand_id')
                ->where('af_products.brand_id', $brand['id'])
                ->where('af_product_images.image_index', 0)->findAll();

            $appliancesIDs = array_column($products, 'brand_id');
            // print_r($appliancesIDs);
            $homezoneAppliancesModel = new HomeZoneAppliancesModel();
            $categories = $homezoneAppliancesModel->whereIn('id', $appliancesIDs)->findAll();
            // echo "<pre>";
            // print_r($products);
            // print_r($categories);
            // die;
            $statusCode = 200;
            $response = [
                'status' => 200,
                'data' => [
                    'categories' => $categories,
                    'products' => $products,
                ],
            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 404 ? 404 : 500;
            $response = [
                'error' => $e->getCode() === 404 ? 'Brand not found' : $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }
}