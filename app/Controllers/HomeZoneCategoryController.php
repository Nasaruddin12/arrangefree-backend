<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\HomeZoneCategoryModel;
use CodeIgniter\API\ResponseTrait;
use Exception;

class HomeZoneCategoryController extends BaseController
{
    use ResponseTrait;
    public function createHomeZoneCaterory()
    {
        try {
            
            $homeZoneCategoryModel = new HomeZoneCategoryModel();
            $validation = \Config\Services::validation();
            // $statusCode = 200;

            $userBackendToFrontendAttrs = [
                'title' => 'title',
                'home_zone_appliances_id' => 'home_zone_appliances_id'

            ];

            $image = \Config\Services::image();
            $productImage = $this->request->getFile('image');
            // $path_900x500 =  "public/uploads/products/900x500/";
            $path_128x128 =   "public/uploads/homezone-category/128x128/";
            $imageName = bin2hex(random_bytes(10)) . time() . '.jpeg';
            // $productImagesData = array();
            // $productID = $this->request->getVar('product_id');
            $image->withFile($productImage)
                ->resize(360, 360, false)
                ->save($path_128x128 . $imageName, 90);

            $HomeZoneCategoryData = array();
            foreach ($userBackendToFrontendAttrs as $backendAttr => $frontendAttr) {
                $HomeZoneCategoryData[$backendAttr] = $this->request->getVar($frontendAttr);
            }
            helper('slug');
            $HomeZoneCategoryData['slug'] = slugify($HomeZoneCategoryData['title']);
            $HomeZoneCategoryData['image'] = $path_128x128 . $imageName;
            $homeZoneCategoryModel->insert($HomeZoneCategoryData);

            // $homeZoneCategoryModel->insert($HomeZoneCategoryData);
            if (!empty($homeZoneCategoryModel->errors())) {
                throw new Exception('Validation', 400);
            }
            if ($homeZoneCategoryModel->db->error()['code']) {
                throw new Exception($homeZoneCategoryModel->db->error()['message'], 500);
            }
            if ($homeZoneCategoryModel->db->affectedRows() == 1) {
                $statusCode = 200;
                $response = [
                    'message' => 'Home Zone Category Created Successfully.',
                    'homeZoneCategory_id' => $homeZoneCategoryModel->db->insertID(),
                ];
            }
        } catch (Exception $e) {
            // $db->transRollback();
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? ['validation' => $validation->getErrors()] : $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
        // $response['status'] = $statusCode;
    }

    public function getHomeZoneCaterory()
    {

        $homeZoneCategoryModel = new HomeZoneCategoryModel();
        $home_zone_category_id = $this->request->getVar('home_zone_appliances_id');

        try {
            $data = $homeZoneCategoryModel;
            if (!is_null($home_zone_category_id)) {
                $data->where('home_zone_appliances_id', $home_zone_category_id);
            }
            $data = $data->select([
                'af_home_zone_category.id AS id',
                'af_home_zone_category.home_zone_appliances_id AS home_zone_appliances_id',
                'af_home_zone_category.title AS title',
                'af_home_zone_category.image AS image',
                'af_home_zone_category.slug AS slug',
                'af_home_zone_category.created_at AS created_at',
                'af_home_zone_category.updated_at AS updated_at',
                'af_home_zone_appliances.title AS home_zone_appliances_title',

            ])
                ->join('af_home_zone_appliances', 'af_home_zone_category.home_zone_appliances_id = af_home_zone_appliances.id')->findAll();

            if (!empty($homeZoneCategoryModel->errors())) {
                // $validation = &$accountfaqModel;
                throw new Exception('Validation', 400);
            }
            if ($homeZoneCategoryModel->db->error()['code'])
                throw new Exception($homeZoneCategoryModel->db->error()['message'], 500);

            $statusCode = 200;
            $response = [
                "data" => $data
            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? $homeZoneCategoryModel->errors() : $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    public function updateHomeZoneCaterory($id)
    {
        try {
            
            $homeZoneCategoryModel = new HomeZoneCategoryModel();
            $validation = &$homeZoneCategoryModel;
            // $statusCode = 200;

            $userBackendToFrontendAttrs = [
                'title' => 'title',
                'home_zone_appliances_id' => 'home_zone_appliances_id',
                'image' => 'image',
            ];

            $HomeZoneCategoryData = array();
            foreach ($userBackendToFrontendAttrs as $backendAttr => $frontendAttr) {
                $HomeZoneCategoryData[$backendAttr] = $this->request->getVar($frontendAttr);
            }
            $HomeZoneCategoryData['id'] = $id;
            // var_dump($HomeZoneCategoryData);
            // print_r($HomeZoneCategoryData);die;
            $homeZoneCategoryModel->update($id, $HomeZoneCategoryData);
            if (!empty($homeZoneCategoryModel->errors())) {
                throw new Exception('Validation', 400);
            }
            if ($homeZoneCategoryModel->db->error()['code']) {
                throw new Exception($homeZoneCategoryModel->db->error()['message'], 500);
            }
            if ($homeZoneCategoryModel->db->affectedRows() == 1) {
                $statusCode = 200;
                $response = [
                    'message' => 'Home zone Category updated successfully.',
                ];
            } else {
                throw new Exception('Nothing to update', 200);
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

    public function deleteHomeZoneCaterory($id)
    {
        try {
            $homeZoneCategoryModel = new HomeZoneCategoryModel();
            $homeZoneCategoryModel->delete($id);

            if ($homeZoneCategoryModel->db->affectedRows() == 1) {
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


    public function getHomeZoneCateroryByid($id)
    {

        $homeZoneCategoryModel = new HomeZoneCategoryModel();
        try {
            $data = $homeZoneCategoryModel->where('home_zone_appliances_id', $id)->findAll();

            if (!empty($homeZoneCategoryModel->errors())) {
                throw new Exception('Validation', 400);
            }
            if ($homeZoneCategoryModel->db->error()['code'])
                throw new Exception($homeZoneCategoryModel->db->error()['message'], 500);

            $statusCode = 200;
            $response = [
                "data" => $data
            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? $homeZoneCategoryModel->errors() : $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    public function updateHomeZoneSubCategoryImage()
    {
        $image = \Config\Services::image();
        try {
            $statusCode = 200;
            $homeZoneImage = $this->request->getFile('image');
            $path_128x128 =   "public/uploads/homezone-category/128x128/";
            $imageName = bin2hex(random_bytes(10)) . time() . '.png';
            // $productImagesData = array();
            // $productID = $this->request->getVar('product_id');
            $image->withFile($homeZoneImage)
                ->resize(360, 360, false)
                ->convert(IMAGETYPE_PNG)
                ->save($path_128x128 . $imageName, 90);

            $image_path = $path_128x128 . $imageName;

            $response = [
                'message' => 'Home Zone Category Image saved successfully.',
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
}
