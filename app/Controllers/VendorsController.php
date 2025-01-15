<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\SubscriptionModel;
use App\Models\UsersModel;
use App\Models\VendorsDocumentsModel;
use App\Models\VendorsModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Database\Exceptions\DatabaseException;
use Exception;

class VendorsController extends BaseController
{
    use ResponseTrait;

    // Create a new vendor
    public function createvendor()
    {
        $db = db_connect();
        try {
            $db->transBegin(); // Start transaction

            $vendorsModel = new VendorsModel();
            $vendorsDocumentsModel = new VendorsDocumentsModel();
            $validation = \Config\Services::validation();
            $statusCode = 200;

            // vendor Data
            $vendorData = [
                'name' => $this->request->getVar('name'),
                'email' => $this->request->getVar('email'),
                'user_id' => $this->request->getVar('user_id'),
                'company_name' => $this->request->getVar('company_name'),
                'mobile_no' => $this->request->getVar('mobile_no'),
                'vendor_address' => $this->request->getVar('vendor_address'),
                'vendor_gst_no' => $this->request->getVar('vendor_gst_no'),
            ];
            // print_r($vendorData);die;
            $validation = &$vendorsModel;
            $vendorsModel->insert($vendorData);
            // echo $vendorsModel->db->getLastQuery();

            if (!empty($validation->errors())) {
                throw new Exception('Validation', 400);
            }

            if ($validation->db->error()['code']) {
                throw new Exception($validation->db->error()['message'], 500);
            }
            $vendorID = $vendorsModel->db->insertID();
            $vendorNumber = 'HAP1' . str_pad($vendorID, 3, '0', STR_PAD_LEFT);
            $vendorsModel->set(['vendor_code' => $vendorNumber])->where('id', $vendorID)->update();


            // vendor Documents Data
            $vendorDocumentsData = [
                'vendor_id' => $vendorID,
                'sign' => $this->request->getVar('sign'),
                'aggrement' => $this->request->getVar('aggrement'),
                'pan_card' => $this->request->getVar('pan_card'),
                'aadhar_card' => $this->request->getVar('aadhar_card'),
                'shop_act' => $this->request->getVar('shop_act'),
                'shop_image' => $this->request->getVar('shop_image'),
            ];
            $validation = &$vendorsDocumentsModel;
            $vendorsDocumentsModel->insert($vendorDocumentsData);

            if (!empty($validation->errors())) {
                throw new Exception('Validation', 400);
            }

            if ($validation->db->error()['code']) {
                throw new Exception($validation->db->error()['message'], 500);
            }

            $db->transCommit(); // Commit the transaction

            $response = [
                'message' => 'vendor created successfully.',
                'vendor_id' => $vendorID,
                'vendor_code' => $vendorNumber,
            ];
        } catch (Exception $e) {
            $db->transRollback(); // Rollback the transaction in case of an exception
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? ['validation' => $validation->errors()] : $e->getMessage()
            ];
            // echo 'clear';
        } catch (DatabaseException $e) {
            var_dump($e);
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }


    // Get all vendors
    public function getvendors($id)
    {
        $vendorsModel = new VendorsModel();
        $VendorsDocumentsModel = new VendorsDocumentsModel();
        $subscriptionModel = new SubscriptionModel();

        try {
            $vendordata = $vendorsModel->where('id', $id)->findAll();
            $vendorsDocumentdata = $VendorsDocumentsModel->where('vendor_id', $id)->findAll();
            $vendorsSubscriptionData = $subscriptionModel->where('vendor_id', $id)->findAll();

            if (!empty($vendorsModel->errors())) {

                throw new Exception('Validation', 400);
            }

            if ($vendorsModel->db->error()['code']) {
                throw new Exception($vendorsModel->db->error()['message'], 500);
            }

            $statusCode = 200;
            $response = [
                'vendor_data' => $vendordata,
                'vendors_documents' => $vendorsDocumentdata,
                'vendors_subscriptions' => $vendorsSubscriptionData
            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? $vendorsModel->errors() : $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    public function getvendorsByUserid($id)
    {
        $userModel = new UsersModel();
        $vendorsModel = new VendorsModel();
        try {

            $vendor_data = $vendorsModel->where('user_id', $id)->findAll();
            // // echo "hfedhf";die;
            // $vendor_data = [];

            // if ($userRole === 1) {
            //     // Get all vendors for role 1
            //     $vendor_data = $vendorsModel->findAll();
            // } else {
            //     // Get vendor details for the specific user
            //     $vendor_data = $vendorsModel->where('user_id', $id )->findAll();
            // }

            if (!empty($vendorsModel->errors())) {
                throw new Exception('Validation');
            }

            if ($vendorsModel->db->error()['code']) {
                throw new Exception($vendorsModel->db->error()['message']);
            }

            $statusCode = 200;
            $response = [
                'vendor_data' => $vendor_data
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

    public function getAllvendors()
    {
        $vendorsModel = new VendorsModel();
        $VendorsDocumentsModel = new VendorsDocumentsModel();

        try {
            $vendordata = $vendorsModel->findAll();

            if (!empty($vendorsModel->errors())) {

                throw new Exception('Validation', 400);
            }

            if ($vendorsModel->db->error()['code']) {
                throw new Exception($vendorsModel->db->error()['message'], 500);
            }

            $statusCode = 200;
            $response = [
                'vendor_data' => $vendordata,
            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? $vendorsModel->errors() : $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    // Update a vendor by ID
    public function updatevendor($id)
    {
        try {
            $vendorsModel = new VendorsModel();
            $validation = &$vendorsModel;

            $vendorData = [
                'name' => $this->request->getVar('name'),
                'email' => $this->request->getVar('email'),
                'user_id' => $this->request->getVar('user_id'),
                'company_name' => $this->request->getVar('company_name'),
                'mobile_no' => $this->request->getVar('mobile_no'),
                'vendor_address' => $this->request->getVar('vendor_address'),
                'vendor_code' => $this->request->getVar('vendor_code'),
                'vendor_gst_no' => $this->request->getVar('vendor_gst_no'),
            ];

            $vendorsModel->update($id, $vendorData);

            if (!empty($vendorsModel->errors())) {
                throw new Exception('Validation', 400);
            }

            if ($vendorsModel->db->error()['code']) {
                throw new Exception($vendorsModel->db->error()['message'], 500);
            }

            if ($vendorsModel->db->affectedRows() == 1) {
                $statusCode = 200;
                $response = [
                    'message' => 'vendor updated successfully.',
                ];
            } else {
                throw new Exception('Nothing to update', 200);
            }
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : ($e->getCode());
            $response = [
                'error' => $e->getCode() === 400 ? ['validation' => $validation->errors()] : $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }



    // Delete a vendor by ID
    public function deletevendor($id)
    {
        try {
            $vendorsModel = new VendorsModel();
            $vendorsModel->delete($id);

            if ($vendorsModel->db->affectedRows() == 1) {
                $statusCode = 200;
                $response = [
                    'message' => 'vendor deleted successfully.'
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

    public function createvendorImage()
    {
        $image = \Config\Services::image();
        try {
            $vendorsModel = new VendorsModel();
            $validation = &$vendorsModel;
            $statusCode = 200;

            $vendorImagesData = $this->request->getFile('image');
            $vendor = "public/uploads/vendors/Documents/";
            $imageName = bin2hex(random_bytes(10)) . time() . '.jpeg';
            // $vendorImagesData = array();
            // $productID = $this->request->getVar('product_id');
            $image->withFile($vendorImagesData)
                // ->resize(1080, 1620, true)
                ->resize(1620, 1620, true)
                ->convert(IMAGETYPE_JPEG)
                ->save($vendor . $imageName, 90);

            $data = $vendor . $imageName;


            $response = [
                'message' => 'vendor Image created successfully.',
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
    public function createvendorPdf()
    {
        try {
            $vendorsModel = new VendorsModel();
            $validation = &$vendorsModel;
            $statusCode = 200;

            $pdfFile = $this->request->getFile('pdf');
            $pdfPath = "uploads/vendors/Documents/";
            $pdfName = bin2hex(random_bytes(10)) . time() . '.pdf';

            $pdfFile->move($pdfPath, $pdfName);

            $pdfFullPath = $pdfPath . $pdfName;

            $response = [
                'message' => 'PDF uploaded successfully.',
                'data' => $pdfFullPath,
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
    public function updateVendorAgreement()
    {
        try {
            $VendorsDocumentsModel = new VendorsDocumentsModel();
            $validation = &$VendorsDocumentsModel;
            $statusCode = 200;

            $vendor_id = $this->request->getVar('vendor_id');
            $aggrement = $this->request->getVar('aggrement');

            // Update the specified column for the document with the given ID
            $VendorsDocumentsModel->where('vendor_id', $vendor_id)
                ->set(['aggrement' => $aggrement])
                ->update();

            $response = [
                'message' => 'Aggrement Updated successfully.',
                // 'data' => $pdfFullPath,
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