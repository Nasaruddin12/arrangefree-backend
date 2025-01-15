<?php
namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ProductVariationModel;
use CodeIgniter\API\ResponseTrait;
use Exception;

class ProductVariationController extends BaseController
{
    use ResponseTrait;

    // Create a new product
    public function createProductVariation()
    {
        try {
            $productVariationModel = new ProductVariationModel();
            $validation = \Config\Services::validation();
            $statusCode = 200;

            $productData = [
                'product_id' => $this->request->getVar('product_id'),
                'variation_type' => $this->request->getVar('variation_type'),
                'actual_price' => $this->request->getVar('actual_price'),
                'discount' => $this->request->getVar('discount'),
                'quantity_left' => $this->request->getVar('quantity_left')
            ];

            $productVariationModel->insert($productData);

            if (!empty($productVariationModel->errors())) {
                throw new Exception('Validation', 400);
            }

            if ($productVariationModel->db->error()['code']) {
                throw new Exception($productVariationModel->db->error()['message'], 500);
            }

            if ($productVariationModel->db->affectedRows() == 1) {
                $response = [
                    'message' => 'Product created successfully.',
                    'product_id' => $productVariationModel->db->insertID(),
                ];
            }
        } catch (\Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? ['validation' => $validation->errors()] : $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    // Get all products
    public function getProductVariation()
    {
        $productVariationModel = new ProductVariationModel();
        try {
            $data = $productVariationModel->findAll();

            if (!empty($productVariationModel->errors())) {
                throw new Exception('Validation', 400);
            }

            if ($productVariationModel->db->error()['code']) {
                throw new Exception($productVariationModel->db->error()['message'], 500);
            }

            $statusCode = 200;
            $response = [
                'data' => $data
            ];
        } catch (\Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? $productVariationModel->errors() : $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    // Update a product by ID
   public function updateProductVariation($id)
   {
       try {
           $productVariationModel = new ProductVariationModel();
           $validation = &$productVariationModel;

           $userBackendToFrontendAttrs = [
               'product_id' => 'product_id',
               'variation_type' => 'variation_type',
               'actual_price' => 'actual_price',
               'discount' => 'discount',
               'quantity_left' => 'quantity_left'
           ];

           $productData = [];
           foreach ($userBackendToFrontendAttrs as $backendAttr => $frontendAttr) {
               $productData[$backendAttr] = $this->request->getVar($frontendAttr);
           }
           $productData['id'] = $id;

           $productVariationModel->update($id, $productData);
           if (!empty($productVariationModel->errors())) {
               throw new Exception('Validation', 400);
           }
           if ($productVariationModel->db->error()['code']) {
               throw new Exception($productVariationModel->db->error()['message'], 500);
           }
           if ($productVariationModel->db->affectedRows() == 1) {
               $statusCode = 200;
               $response = [
                   'message' => 'Product updated successfully.',
               ];
           } else {
               throw new Exception('Nothing to update', 200);
           }
       } catch (\Exception $e) {
           $statusCode = $e->getCode() === 400 ? 400 : ($e->getCode());
           $response = [
               'error' => $e->getCode() === 400 ? ['validation' => $validation->errors()] : $e->getMessage()
           ];
       }

       $response['status'] = $statusCode;
       return $this->respond($response, $statusCode);
   }

    // Delete a product by ID
   public function deleteProductVariation($id)
   {
       try {
           $productVariationModel = new ProductVariationModel();
           $productVariationModel->delete($id);

           if ($productVariationModel->db->affectedRows() == 1) {
               $statusCode = 200;
               $response = [
                   'message' => 'Product deleted successfully.'
               ];
           } else {
               throw new Exception('Nothing to delete', 200);
           }
       } catch (\Exception $e) {
           $statusCode = $e->getCode() === 400 ? 400 : ($e->getCode());
           $response = [
               'error' => $e->getMessage()
           ];
       }

       $response['status'] = $statusCode;
       return $this->respond($response, $statusCode);
   }
}
              
