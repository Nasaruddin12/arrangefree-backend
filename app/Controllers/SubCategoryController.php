<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\SubCategoryModel;
use CodeIgniter\API\ResponseTrait;
use Exception;

class SubCategoryController extends BaseController
{
    use ResponseTrait;
    public function createSubCaterory()
    {
        try {
            $subCategoryModel = new SubCategoryModel();
            $validation = \Config\Services::validation();
            // $statusCode = 200;

            $userBackendToFrontendAttrs = [
                'title' => 'title',
                'category_id' => 'category_id',
                'image' => 'image'
            ];
            
            $subCategoryData = array();
            foreach ($userBackendToFrontendAttrs as $backendAttr => $frontendAttr) {
                $subCategoryData[$backendAttr] = $this->request->getVar($frontendAttr);
            }
            helper('slug');
            $subCategoryData['slug'] = slugify($subCategoryData['title']);
            $subCategoryModel->insert($subCategoryData);
            if (!empty($subCategoryModel->errors())) {
                throw new Exception('Validation', 400);
            }
            if ($subCategoryModel->db->error()['code']) {
                throw new Exception($subCategoryModel->db->error()['message'], 500);
            }
            if ($subCategoryModel->db->affectedRows() == 1) {
                $statusCode = 200;
                $response = [
                    'message' => 'Sub-Category Created Successfully.',
                    'homeZoneCategory_id' => $subCategoryModel->db->insertID(),
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

    public function getAllSubCategory()
    {

        $subCategoryModel = new SubCategoryModel();
        $category_id = $this->request->getVar('category_id');

        try {
            $data = $subCategoryModel;
            if(!is_null($category_id))
            {
                $data->where('category_id', $category_id);
            }
            $data = $data->findAll();

            if (!empty($subCategoryModel->errors())) {
                // $validation = &$accountfaqModel;
                throw new Exception('Validation', 400);
            }
            if ($subCategoryModel->db->error()['code'])
                throw new Exception($subCategoryModel->db->error()['message'], 500);

            $statusCode =200;
            $response = [
                "data" => $data
            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? $subCategoryModel->errors() : $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    public function updateSubCaterory($id)
    {
        try {
            $subCategoryModel = new SubCategoryModel();
            $validation = &$subCategoryModel;
            // $statusCode = 200;
    
            $userBackendToFrontendAttrs = [
                'title' => 'title',
                'category_id' => 'category_id',
                'image' => 'image',
            ];
    
            $subCategoryData = array();
            foreach ($userBackendToFrontendAttrs as $backendAttr => $frontendAttr) {
                $subCategoryData[$backendAttr] = $this->request->getVar($frontendAttr);
            }
            $subCategoryData['id'] = $id;
            // var_dump($subCategoryData);
            // print_r($subCategoryData);die;
            $subCategoryModel->update($id, $subCategoryData); 
            if (!empty($subCategoryModel->errors())) {
                throw new Exception('Validation', 400);
            }
            if ($subCategoryModel->db->error()['code']) {
                throw new Exception($subCategoryModel->db->error()['message'], 500);
            }
            if ($subCategoryModel->db->affectedRows() == 1) {
                $statusCode = 200;
                $response = [
                    'message' => 'Sub-Category updated successfully.',
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

    public function deleteSubCaterory($id)
{
    try {
        $subCategoryModel = new SubCategoryModel();
        $subCategoryModel->delete($id);

        if ($subCategoryModel->db->affectedRows() == 1) {
            $statusCode = 200;
            $response = [
                'message' => 'Sub-Category deleted successfully.'
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


public function getSubCateroryByid($id)
    {

        $subCategoryModel = new SubCategoryModel();
        try {
            $data = $subCategoryModel->where('id',$id)->findAll();

            if (!empty($subCategoryModel->errors())) {
                throw new Exception('Validation', 400);
            }
            if ($subCategoryModel->db->error()['code'])
                throw new Exception($subCategoryModel->db->error()['message'], 500);

            $statusCode =200;
            $response = [
                "data" => $data
            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? $subCategoryModel->errors() : $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }
}
