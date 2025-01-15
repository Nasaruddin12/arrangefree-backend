<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\DesignerAssignProductModel;
use App\Models\DesignerModel;
use App\Models\HomeZoneAppliancesModel;
use App\Models\HomeZoneCategoryModel;
use App\Models\ProductImageModel;
use App\Models\ProductModel;
use CodeIgniter\API\ResponseTrait;
use Exception;

class DesignerController extends BaseController
{
    use ResponseTrait;
    public function Create()
    {
        $this->response->setHeader('Content-Type', 'application/json');
        $DesignerModel = new DesignerModel();
        $data = $this->request->getVar();
        if ($data) {
            if ($DesignerModel->insert($data)) {
                $response = [
                    "Status" => 201,
                    "Msg" => "Data Added Successfully"
                ];
            } else {
                $response = [
                    "Status" => 403,
                    "Validation" => $DesignerModel->errors()
                ];
            }
        } else {
            $response = [
                "Status" => 400,
                "Msg" => "No data provided"
            ];
        }
        return $this->respond($response, 200);
    }
    function GetAll()
    {
        try {
            $DesignerModel = new DesignerModel();
            $DesignerAssignProductModel = new DesignerAssignProductModel();

            $rest = $DesignerModel->where("status", 1)->findAll();
            foreach ($rest as $key => $re) {
                $productdata = $DesignerAssignProductModel->where("designer_id", $re["id"])->countAllResults();
                $rest[$key]["Product"] = $productdata;
            }

            if (!empty($DesignerModel->errors())) {
                throw new Exception('Validation', 400);
            }

            if ($DesignerModel->db->error()['code']) {
                throw new Exception($DesignerModel->db->error()['message'], 500);
            }

            $statusCode = 200;
            $response = [
                'data' => $rest
            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? $DesignerModel->errors() : $e->getMessage()
            ];
        }
        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }
    function GetById($id)
    {
        try {
            $DesignerModel = new DesignerModel();
            $rest = $DesignerModel->where("id", $id)->first();
            if ($rest) {
                $response = [
                    "Status" => 200,
                    "Data" => $rest
                ];
            } else {
                $response = [
                    "Status" => 404,
                    "Msg" => "No Data Found"
                ];
            }
        } catch (Exception $e) {
            $response = [
                "Status" => 500,
                "Error" => $e->getMessage()
            ];
        }
        return $this->respond($response, 200);
    }
    function Update()
    {
        try {
            $DesignerModel = new DesignerModel();
            $id = $this->request->getVar("id");
            $data = $this->request->getVar();

            // Temporarily remove is_unique validation rule for 'pan_number' and 'adhaar_number'
            $rules = $DesignerModel->getValidationRules();

            $fieldsToSkipUnique = ['pan_number', 'adhaar_number'];

            foreach ($fieldsToSkipUnique as $field) {
                if (isset($rules[$field])) {
                    $backupRules[$field] = $rules[$field];
                    unset($rules[$field]); // Remove the is_unique rule temporarily
                }
            }

            // Perform your validation with the modified rules
            $validationResult = $DesignerModel->setValidationRules($rules)->validate($data);

            if ($validationResult) {
                // Validation passed, update the data
                $rest = $DesignerModel->update($id, $data);
                if ($rest) {
                    $response = [
                        "Status" => 200,
                        "Msg" => "Data Updated Successfully"
                    ];
                } else {
                    $response = [
                        "Status" => 403,
                        "Validation" => $DesignerModel->errors()
                    ];
                }
            } else {
                // Validation failed
                $response = [
                    "Status" => 403,
                    "Validation" => $DesignerModel->errors()
                ];
            }

            // Restore the removed is_unique rule if applicable
            foreach ($fieldsToSkipUnique as $field) {
                if (isset($backupRules[$field])) {
                    $rules[$field] = $backupRules[$field]; // Restore the removed is_unique rule
                }
            }

            $DesignerModel->setValidationRules($rules);
        } catch (Exception $e) {
            $response = [
                "Status" => 500,
                "Error" => $e->getMessage()
            ];
        }

        return $this->respond($response, 200);
    }
    function Delete($id)
    {
        try {
            $DesignerModel = new DesignerModel();
            $rest = $DesignerModel->delete($id);
            if ($rest) {
                $response = [
                    "Status" => 200,
                    "Msg" => "Data Deleted Successfully"
                ];
            } else {
                $response = [
                    "Status" => 500,
                    "Msg" => "Data Not Deleted Successfully"
                ];
            }
        } catch (Exception $e) {
            $response = [
                "Status" => 500,
                "Error" => $e->getMessage()
            ];
        }
        return $this->respond($response, 200);
    }
    function GetDeletedDesigner()
    {
        try {
            $DesignerModel = new DesignerModel();
            $rest = $DesignerModel->onlyDeleted()->findAll();

            $response = [
                "Status" => 200,
                "Data" => $rest
            ];
        } catch (Exception $e) {
            $response = [
                "Status" => 500,
                "Error" => $e->getMessage()
            ];
        }
        return $this->respond($response, 200);
    }
    public function AssignProduct()
    {
        $this->response->setHeader('Content-Type', 'application/json');

        $DesignerAssignProductModel = new DesignerAssignProductModel();
        $data = $this->request->getVar();


        if ($DesignerAssignProductModel->insert($data)) {
            $response = [
                "Status" => 201,
                "Msg" => "Data Added Successfully"
            ];
        } else {
            $response = [
                "Status" => 403,
                "Error" => $DesignerAssignProductModel->errors()
            ];
        }
        return $this->respond($response, 200);
    }
    function UpdateAssignProduct()
    {
        $this->response->setHeader('Content-Type', 'application/json');

        $DesignerAssignProductModel = new DesignerAssignProductModel();
        $data = $this->request->getVar();
        $id = $this->request->getVar("id");


        if ($DesignerAssignProductModel->update($id, $data)) {
            $response = [
                "Status" => 200,
                "Msg" => "Data Updated Successfully"
            ];
        } else {
            $response = [
                "Status" => 403,
                "Error" => $DesignerAssignProductModel->errors()
            ];
        }
        return $this->respond($response, 200);
    }
    public function UnAssignProduct($id)
    {
        $DesignerAssignProductModel = new DesignerAssignProductModel();
        if ($DesignerAssignProductModel->delete($id)) {
            $response = [
                "Status" => 201,
                "Msg" => "Data Deleted Successfully"
            ];
        } else {
            $response = [
                "Status" => 500,
                "Msg" => "Data Not Deleted Successfully"
            ];
        }
        return $this->respond($response, 200);
    }
    function GetProductsByDesignerId($id)
    {
        $DesignerAssignProductModel = new DesignerAssignProductModel();
        $ProductImageModel = new ProductImageModel();
        $ProductModel = new ProductModel();
        $HomeZoneCategoryModel = new HomeZoneCategoryModel();
        $homeZoneAppliancesModel = new HomeZoneAppliancesModel();

        $categoryData = $homeZoneAppliancesModel->findAll();
        $data = [];
        $i = 0;
        foreach ($categoryData as $r) {
            $Harrypotter = $ProductModel->select(["id", "name", "product_code", "home_zone_category_id", "created_at"])->where("home_zone_appliances_id", $r['id'])->findAll();
            $ter = [];
            $count = [];
            $p = 0;
            foreach ($Harrypotter as $re) {
                if ($DesignerAssignProductModel->where("designer_id", $id)->where("product_id", $re["id"])->first()) {
                    $ImgData = $ProductImageModel->where("product_id", $id)->first();
                    $catData = $HomeZoneCategoryModel->where("id", $re["home_zone_category_id"])->first();

                    $ter[$p]["id"] = $re["id"];
                    $ter[$p]["name"] = $re["name"];
                    $ter[$p]["product_code"] = $re["product_code"];
                    $ter[$p]["home_zone_category_id"] = $re["home_zone_category_id"];
                    $ter[$p]["created_at"] = date("Y-m-d / H:i:s", strtotime($re["created_at"]));
                    $ter[$p]["ProductType"] = $catData["title"];
                    $ter[$p]["path_360x360"] = $ImgData['path_360x360'];
                    $ter[$p]["path_580x580"] = $ImgData['path_580x580'];
                    $count[$p] = 1;
                    $p++;
                }
            }
            $data[$i]['Category'] = $r['title'];
            $data[$i]["TotalCount"] = $count ?  array_sum($count) : 0;
            $data[$i]["Products"] = $ter;
            $i++;
        }






        $response = [
            "Status" => 200,
            "Data" => $data
        ];
        return $this->respond($response, 200);
    }

    public function createDesignerImage()
    {
        $image = \Config\Services::image();
        try {
            $DesignerModel = new DesignerModel();
            $validation = &$DesignerModel;
            $statusCode = 200;

            $designerImagesData = $this->request->getFile('image');
            $designer = "public/uploads/designers/";
            $imageName = bin2hex(random_bytes(10)) . time() . '.jpeg';
            // $designerImagesData = array();
            // $productID = $this->request->getVar('product_id');
            $image->withFile($designerImagesData)
                ->resize(1620, 1620, true)
                ->convert(IMAGETYPE_JPEG)
                ->save($designer . $imageName, 90);

            $data = $designer . $imageName;


            $response = [
                'message' => 'designer Image created successfully.',
                'data' => $data,
            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }
}
