<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ProductImageModel;
use CodeIgniter\API\ResponseTrait;
use Exception;

class ProductImageController extends BaseController
{
    use ResponseTrait;
    public function createProductImageMultiple()
    {
        $image = \Config\Services::image();
        try {
            $productImageModel = new ProductImageModel();
            $validation = &$productImageModel;
            $statusCode = 200;


            $productImages = $this->request->getFiles('image_path')['image_path'];
            $productID = $this->request->getVar('product_id');
            $filePath = '';
            foreach($productImages as $key => $productImage) {
                // mkdir('upload/products/900x')
                $image->withFile($productImage)
                ->resize(1080, 1620, true)
                ->convert(IMAGETYPE_JPEG)
                ->save('upload/products/900x500/.'. $productID .'-' . bin2hex(random_bytes(10)) . '.jpeg', 90);
                $image->withFile($productImage)
                ->resize(100, 100, true)
                ->convert(IMAGETYPE_JPEG)
                ->save('upload/products/100x100/.'. $productID . '-' . bin2hex(random_bytes(10)) . '.jpeg', 90);
                $foldername = "upload/" .date('ymdis') . "/" ;
                $filename = $productImage->getRandomName();
                $productImage->move($foldername, $filename);
                $filePath .= $foldername . $filename . ',' ;
            }


            $data = [
                'product_id' => $this->request->getVar('product_id'),
                'image_path' => $filePath
            ];

            $productImageModel->insert($data);

            if (!empty($productImageModel->errors())) {
                throw new Exception('Validation', 400);
            }

            if ($productImageModel->db->error()['code']) {
                throw new Exception($productImageModel->db->error()['message'], 500);
            }

            if ($productImageModel->db->affectedRows() == 1) {
                $response = [
                    'message' => 'Product created successfully.',
                    'product_id' => $productImageModel->db->insertID(),
                ];
            }
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? ['validation' => $validation->errors()] : $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);

    }

    public function createProductImage()
    {
        $image = \Config\Services::image();
        try {
            $productImageModel = new ProductImageModel();
            $validation = &$productImageModel;
            $statusCode = 200;


            $productImage = $this->request->getFile('image_path');
            $path_1620x1620 =  "public/uploads/products/1620x1620/";
            $path_580x580 =  "public/uploads/products/580x580/";
            $path_360x360 =   "public/uploads/products/360x360/";
            $imageName = bin2hex(random_bytes(10)) .time(). '.jpeg';
            $productImagesData = array();
            // $productID = $this->request->getVar('product_id');
            $image->withFile($productImage)
            // ->resize(1080, 1620, true)
            ->resize(1620, 1620, true)
            ->convert(IMAGETYPE_JPEG)
            ->save($path_1620x1620 . $imageName, 90);
            $image->withFile($productImage)
            ->resize(580, 580, true)
            ->convert(IMAGETYPE_JPEG)
            ->save($path_580x580 . $imageName, 80);
            $image->withFile($productImage)
            ->resize(360, 360, true)
            ->convert(IMAGETYPE_JPEG)
            ->save($path_360x360 . $imageName, 70);

            $data = [
                'path_1620x1620' => $path_1620x1620 . $imageName,
                'path_580x580' => $path_580x580 . $imageName,
                'path_360x360' => $path_360x360 . $imageName,
            ];

            $response = [
                'message' => 'Product created successfully.',
                'data' => $data,
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

    public function deleteProductImage()
    {
        $imagePath = $this->request->getVar('image_path');
        $imagePath = json_decode(json_encode($imagePath), true);
        // var_dump($imagePath);exit;
        foreach($imagePath as $key => $path) {
            if(file_exists($path)) {
                unlink($path);
            }
        }
        $statusCode = 200;
        $response = [
            'message' => 'Image deleted successfully.'
        ];
        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    public function deleteProductImageById()
    {
        $productImageModel = new ProductImageModel();
        $imageID = $this->request->getVar('id');
        // var_dump($imageID == null);die;
        if($imageID != null) {
            $imageRecord = $productImageModel->find($imageID);
            if(!empty($imageRecord)) {

                $imagePath = [
                    $imageRecord['path_1620x1620'],
                    $imageRecord['path_580x580'],
                    $imageRecord['path_360x360'],
                ];
            }
            $imageRecord = $productImageModel->delete($imageID);
        } else {
            $imagePath = $this->request->getVar('image_path');
            $imagePath = json_decode(json_encode($imagePath), true);
            // var_dump($imagePath);exit;
        }
        if(!empty($imagePath)) {

            foreach($imagePath as $key => $path) {
                if(file_exists($path)) {
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
}
