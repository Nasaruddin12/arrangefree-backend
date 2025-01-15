<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\OffersImageModel;
use App\Models\OffersModel;
use CodeIgniter\API\ResponseTrait;
use Exception;

class OffersController extends BaseController
{
    use ResponseTrait;
    public function createOffers()
    {
        try {
            $offersModel = new OffersModel();
            $validation = \Config\Services::validation();

            $offersData = [
                'offer_group' => $this->request->getVar('offer_group'),
                'offer_title' => $this->request->getVar('offer_title'),
                'offer_link' => $this->request->getVar('offer_link'),
                'offer_start_date' => $this->request->getVar('offer_start_date'),
                'offer_end_date' => $this->request->getVar('offer_end_date'),
                'offer_mobile_path' => $this->request->getVar('offer_mobile_path'),
                'offer_web_path' => $this->request->getVar('offer_web_path'),
                'offer_index' => $this->request->getVar('offer_index'),
            ];

            $offersModel->insert($offersData);

            if (!empty($validation->getErrors())) {
                throw new Exception('Validation', 400);
            }

            if ($offersModel->db->error()['code']) {
                throw new Exception($offersModel->db->error()['message'], 500);
            }

            if ($offersModel->db->affectedRows() == 1) {
                $response = [
                    'status' => 200,
                    'message' => 'Offers created successfully.',
                    'offers_id' => $offersModel->db->insertID()
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

    public function getOffersById($id)
    {
        try {
            $offersModel = new OffersModel();
            $offers = $offersModel->find($id);

            if (!$offers) {
                throw new Exception('Offers not found', 404);
            }

            $response = [
                'status' => 200,
                'offers' => $offers
            ];
        } catch (Exception $e) {
            $response = [
                'status' => $e->getCode() === 404 ? 404 : 500,
                'error' => $e->getCode() === 404 ? 'Offers not found' : $e->getMessage()
            ];
        }

        return $this->respond($response, $response['status']);
    }

    public function updateOffers($id)
    {
        try {
            $offersModel = new OffersModel();
            $validation = \Config\Services::validation();
            $offers = $offersModel->find($id);

            if (!$offers) {
                throw new Exception('Offers not found', 404);
            }

            $offersData = [
                'offer_group' => $this->request->getVar('offer_group'),
                'offer_title' => $this->request->getVar('offer_title'),
                'offer_link' => $this->request->getVar('offer_link'),
                'offer_start_date' => $this->request->getVar('offer_start_date'),
                'offer_end_date' => $this->request->getVar('offer_end_date'),
                'offer_mobile_path' => $this->request->getVar('offer_mobile_path'),
                'offer_web_path' => $this->request->getVar('offer_web_path'),
                'offer_index' => $this->request->getVar('offer_index'),
            ];

            $offersModel->update($id, $offersData);

            if (!empty($validation->getErrors())) {
                throw new Exception('Validation', 400);
            }

            if ($offersModel->db->error()['code']) {
                throw new Exception($offersModel->db->error()['message'], 500);
            }

            $response = [
                'status' => 200,
                'message' => 'Offers updated successfully.'
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

    public function deleteOffers($id)
    {
        try {
            $offersModel = new OffersModel();
            $offers = $offersModel->find($id);

            if (!$offers) {
                throw new Exception('Offers not found', 404);
            }

            $offersModel->delete($id);

            $response = [
                'status' => 200,
                'message' => 'Offers deleted successfully.'
            ];
        } catch (Exception $e) {
            $response = [
                'status' => $e->getCode() === 404 ? 404 : 500,
                'error' => $e->getCode() === 404 ? 'Offers not found' : $e->getMessage()
            ];
        }

        return $this->respond($response, $response['status']);
    }

    public function getAllOffers()
    {
        try {
            $offersModel = new OffersModel();

            $offersData = $offersModel->findAll();

            $statusCode = 200;
            $response = [
                'data' => $offersData,
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

    public function createOffersImage()
    {
        $image = \Config\Services::image();
        try {
            $offersModel = new OffersModel();
            $validation = &$offersModel;
            $statusCode = 200;


            $OffersImage = $this->request->getFile('image_banner');
            $Offers = "public/uploads/Offers/";
            $imageName = bin2hex(random_bytes(10)) . time() . '.jpeg';
            $OffersImagesData = array();
            // $productID = $this->request->getVar('product_id');
            $image->withFile($OffersImage)
                // ->resize(1080, 1620, true)
                ->resize(1620, 1620, true)
                ->convert(IMAGETYPE_JPEG)
                ->save($Offers . $imageName, 90);

            $data = [
                'Offers_image' => $Offers . $imageName,
            ];

            $response = [
                'message' => 'Offers Image created successfully.',
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

    public function deleteOfferImage()
    {
        $imagePath = $this->request->getVar('image_banner');
        $offre_id = $this->request->getVar('id');
        $device = $this->request->getVar('device');
        $offersModel = new OffersModel();

        if ($offre_id != null) {
            // echo $offre_id;
            $imageRecord = $offersModel->find($offre_id);
            if (!empty($imageRecord)) {
                if ($device == 1) {
                    $offerData = [
                        'offer_web_path' => '',
                    ];
                } else {
                    $offerData = [
                        'offer_mobile_path' => '',
                    ];
                }
                $offersModel->update($offre_id, $offerData);
            }
        }
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }

        $statusCode = 200;
        $response = [
            'message' => 'Offers Image deleted successfully.'
        ];
        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }
}
