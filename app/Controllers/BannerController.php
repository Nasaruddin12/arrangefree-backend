<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\BannersModel;
use CodeIgniter\API\ResponseTrait;
use Exception;

class BannerController extends BaseController
{
    use ResponseTrait;
    public function createBannerImage()
    {
        $image = \Config\Services::image();
        try {
            $Bannersmodel = new BannersModel();
            $validation = &$Bannersmodel;
            $statusCode = 200;

            $BannerImage = $this->request->getFile('path');

            // Validate uploaded file
            if (!$BannerImage || !$BannerImage->isValid()) {
                throw new \Exception('No valid image uploaded.', 400);
            }

            // Paths
            $publicRelative = 'public/uploads/banner/';
            $fullPath = FCPATH . 'public/uploads/banner/';

            // Ensure folder exists
            if (!is_dir($fullPath)) {
                if (!mkdir($fullPath, 0755, true) && !is_dir($fullPath)) {
                    throw new \Exception('Failed to create upload directory.', 500);
                }
            }

            $imageName = bin2hex(random_bytes(10)) . time() . '.jpeg';
            // Process and save image to the full path
            $image->withFile($BannerImage)
                // ->resize(1080, 1620, true)
                ->resize(1620, 1620, true)
                ->convert(IMAGETYPE_JPEG)
                ->save($fullPath . $imageName, 90);

            $data = [
                'banner_image' => $publicRelative . $imageName,
            ];

            $response = [
                'message' => 'Banner Image created successfully.',
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

public function getBanner($bannerId)
{
    try {
        $bannersModel = new BannersModel();
        $banner = $bannersModel->find($bannerId);

        if ($banner) {
            $response = [
                'status' => 200,
                'data' => $banner,
            ];
        } else {
            throw new Exception('Banner not found.', 404);
        }
    } catch (Exception $e) {
        $response = [
            'status' => $e->getCode(),
            'error' => $e->getMessage(),
        ];
    }

    return $this->respond($response, $response['status']);
}


public function updateBanner($bannerId)
{
    try {
        $imagePath = $this->request->getVar('image_path');

        $validation = \Config\Services::validation();
        $validation->setRules([
            'image_path' => 'required',
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            throw new Exception('Validation', 400);
        }

        $bannersModel = new BannersModel();
        $banner = $bannersModel->find($bannerId);

        if (!$banner) {
            throw new Exception('Banner not found.', 404);
        }

        $data = [
            'path' => $imagePath,
        ];

        $updated = $bannersModel->update($bannerId, $data);

        if ($updated) {
            $response = [
                'status' => 200,
                'message' => 'Banner updated successfully.',
            ];
        } else {
            throw new Exception('Failed to update banner.', 500);
        }
    } catch (Exception $e) {
        $statusCode = $e->getCode() === 400 ? 400 : 500;
        $response = [
            'status' => $statusCode,
            'error' => $e->getCode() === 400 ? ['validation' => $validation->getErrors()] : $e->getMessage(),
        ];
    }

    return $this->respond($response, $response['status']);
}


public function deleteBanner($id)
{
    try {
        $bannersModel = new BannersModel();
        $banner = $bannersModel->find($id);

        if (!$banner) {
            throw new Exception('Banner not found.', 404);
        }

        $deleted = $bannersModel->delete($id);

        if ($deleted) {
            $response = [
                'status' => 200,
                'message' => 'Banner deleted successfully.',
            ];
        } else {
            throw new Exception('Failed to delete banner.', 500);
        }
    } catch (Exception $e) {
        $response = [
            'status' => $e->getCode(),
            'error' => $e->getMessage(),
        ];
    }

    return $this->respond($response, $response['status']);
}

    public function createMainBanner()
    {
        try {
           
            $statusCode = 200;
            $banner_image = $this->request->getVar('banner_image');
            $banner_image = json_decode($banner_image, true);
            if (!empty($banner_image)) {

                $addhomeZoneAppliancesID = function ($item) {
                    $item['home_zone_appliances_id'] = 0;
                    $item['image_index'] = 0;
                    $item['id'] = null; // Add this line to define the 'id' key
                    return $item;
                };
                $banner_image = array_map($addhomeZoneAppliancesID, $banner_image);

                $bannersModel = new BannersModel();
                // $BannersImageData = $this->request->getVar('banner_image');
                // $BannersImageData = json_decode($BannersImageData, true);

                $validation = &$bannersModel;
                $BannersImageData_Insert = array_filter($banner_image, function ($imageData) {
                    return ($imageData['id'] == null);
                });
                if (!empty($BannersImageData_Insert)) {
                    $bannersModel->insertBatch($BannersImageData_Insert);
                    if (!empty($bannersModel->errors())) {
                        throw new Exception('Validation', 400);
                    }
                    if ($bannersModel->db->error()['code']) {
                        throw new Exception($bannersModel->db->error()['message'], 500);
                    }
                }
                $BannersImageData_Update = array_filter($banner_image, function ($imageData) {
                    return ($imageData['id'] != null);
                });

                if (!empty($BannersImageData_Update)) {
                    $bannersModel->updateBatch($BannersImageData_Update, 'id');
                    if ($bannersModel->db->error()['code']) {
                        throw new Exception($bannersModel->db->error()['message'], 500);
                    }
                    if (!empty($bannersModel->errors())) {
                        throw new Exception('Validation', 400);
                    }
                }
                $response = [
                    'message' => 'Main Banner Image created successfully.',
                    'image_path' => $banner_image,
                ];
                // echo $bannersModel->db->getLastQuery();
                // exit;
            }
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getMessage()
            ];
        }
        
        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
         
    }

    public function getMainBanner()
{
    try {
        $bannersModel = new BannersModel();
        $banner = $bannersModel->where('home_zone_appliances_id	',0)->findAll();

        if ($banner) {
            $response = [
                'status' => 200,
                'data' => $banner,
            ];
        } else {
            throw new Exception('Banner not found.', 404);
        }
    } catch (Exception $e) {
        $response = [
            'status' => $e->getCode(),
            'error' => $e->getMessage(),
        ];
    }

    return $this->respond($response, $response['status']);
}

}




