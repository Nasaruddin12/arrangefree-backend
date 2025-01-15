<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\CartModel;
use App\Models\GeneralOptionModel;
use App\Models\MetaDataModel;
use App\Models\ProductModel;
use App\Models\WishlistModel;
use CodeIgniter\API\ResponseTrait;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class MetaDataController extends BaseController
{
    use ResponseTrait;
    public function applyDiscount()
    {
        try {
            $MetaDataModel = new MetaDataModel();
            $discountPercent = $this->request->getVar('discounted_percent');
            $discountData = [
                'discounted_percent' => $discountPercent
            ];
            $MetaDataModel->set(['value' => $discountData['discounted_percent']])->update(1);
            // die(var_dump($MetaDataModel->db->getLastQuery()));
            $validation = &$MetaDataModel;
            if ($validation->db->error()['code']) {
                throw new Exception($validation->db->error()['message'], 500);
            }

            // For applying on products
            $productModel = new ProductModel();
            // $productId = $this->request->getVar('product_id');
            $validation = &$productModel;
            $productModel->set($discountData)->where('id !=', 0)->update();
            if ($validation->db->error()['code']) {
                throw new Exception($validation->db->error()['message'], 500);
            }

            $statusCode = 200;
            $response = [
                'message' => 'Discount applied'
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

    public function getDiscount()
    {

        try {
            $MetaDataModel = new MetaDataModel();
            $getDiscount = $MetaDataModel->find(1);

            $validation = &$MetaDataModel;

            if ($validation->db->error()['code']) {
                throw new Exception($validation->db->error()['message'], 500);
            }

            $statusCode = 200;
            $response = [
                'message' => 'Discount found Successfully',
                'discounted_percent' => $getDiscount['value']
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



    public function create()
    {

        try {
            $MetaDataModel = new MetaDataModel();
            $validation = \Config\Services::validation();
            $statusCode = 200;



            $data = [
                'group_id' => $this->request->getVar('group_id'),
                'title' => $this->request->getVar('title'),
                'value' => $this->request->getVar('value'),
            ];

            $MetaDataModel->insert($data);


            if (!empty($MetaDataModel->errors())) {
                throw new Exception('Validation', 400);
            }

            if ($MetaDataModel->db->error()['code']) {
                throw new Exception($MetaDataModel->db->error()['message'], 500);
            }

            if ($MetaDataModel->db->affectedRows() == 1) {
                $response = [
                    'message' => 'Meta Data created successfully.',
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



    public function update($id)
    {
        try {
            $MetaDataModel = new MetaDataModel();
            $validation = \Config\Services::validation();
            $statusCode = 200;


            $data = [
                'group_id' => $this->request->getVar('group_id'),
                'title' => $this->request->getVar('title'),
                'value' => $this->request->getVar('value'),
            ];
            $MetaDataModel->update($id, $data);

            if (!empty($MetaDataModel->errors())) {
                throw new Exception('Validation', 400);
            }

            if ($MetaDataModel->db->error()['code']) {
                throw new Exception($MetaDataModel->db->error()['message'], 500);
            }

            if ($MetaDataModel->db->affectedRows() == 1) {
                $response = [
                    'message' => 'Meta Data Updated successfully.',
                    'general_id' => $MetaDataModel->db->insertID(),
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



    public function delete($id)
    {

        try {
            $MetaDataModel = new MetaDataModel();
            $MetaDataModel->delete($id);

            if ($MetaDataModel->db->affectedRows() == 1) {
                $statusCode = 200;
                $response = [
                    'message' => 'Meta Data deleted successfully.'
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



    public function read()
    {
        $MetaDataModel = new MetaDataModel();
        try {
            $data = $MetaDataModel->findAll();

            if (!empty($MetaDataModel->errors())) {
                throw new Exception('Validation', 400);
            }

            if ($MetaDataModel->db->error()['code']) {
                throw new Exception($MetaDataModel->db->error()['message'], 500);
            }

            $statusCode = 200;
            $response = [
                'data' => $data
            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? $MetaDataModel->errors() : $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    public function setPrice()
    {
        try {
            $metaDataModel = new MetaDataModel();
            $increment = $this->request->getVar('increment');
            $metaDataModel->set(['value' => $increment])->update(6);

            $productModel = new ProductModel();
            // $productId = $this->request->getVar('product_id');
            $validation = &$productModel;
            $productModel->set('increase_percent', $increment)->where('id !=', 0)->update();

            $statusCode = 200;
            $response = [
                'message' => 'Increment applied'
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

    public function getIncrement()
    {
        try {
            $MetaDataModel = new MetaDataModel();
            $getDiscount = $MetaDataModel->find(6);

            $validation = &$MetaDataModel;

            if ($validation->db->error()['code']) {
                throw new Exception($validation->db->error()['message'], 500);
            }

            $statusCode = 200;
            $response = [
                'message' => 'Increment found Successfully',
                'increment_percent' => $getDiscount['value']
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
