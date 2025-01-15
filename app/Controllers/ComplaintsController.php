<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Database\Migrations\ProductImages;
use App\Models\ComplaintsModel;
use App\Models\CustomerModel;
use App\Models\OrdersModel;
use App\Models\ProductImageModel;
use App\Models\ProductModel;
use CodeIgniter\API\ResponseTrait;
use Exception;

class ComplaintsController extends BaseController
{
    use ResponseTrait;
    public function Create()
    {
        $ComplaintsModel = new ComplaintsModel();
        $CustomerModel = new CustomerModel();
        $data = $this->request->getVar();


        if ($data) {
            $rest = $ComplaintsModel->insert($data);
            if ($rest) {
                $encode  = json_encode($data);
                $array = json_decode($encode, true);
                $description = $array["description"];
                $user_id = $array["user_id"];

                $cdata = $CustomerModel->where("id", $user_id)->first();
                $name = $cdata["name"];
                $date = date("Y-m-d H:i:s");

                $emailController = new EmailController();
                $subject = 'New Complaint!!!!!!!';
                $message = "Hey! Admin,\n There is new Complaint in Dorfee.\n Description:$description \nCustomer Name: $name \n Date:$date";



                $emailController->sendMail('complaints@dorfee.com', $subject, $message);




                $response = [
                    "Status" => 200,
                    "Msg" => "Complaint Added Successfully"
                ];
            } else {
                $response = [
                    "Status" => 400,
                    "Error" => $ComplaintsModel->errors()
                ];
            }
        } else {
            $response = [
                "Status" => 400,
                "Msg" => [
                    "user_id" => " These Fileds Are Required",
                    "product_id" => " These Fileds Are Required",
                    "order_id" => " These Fileds Are Required"
                ]
            ];
        }
        return $this->respond($response, 200);
    }
    function GetAll()
    {
        try {
            $ComplaintsModel = new ComplaintsModel();
            $ProductModel = new ProductModel();
            $ProductImageModel = new ProductImageModel();
            $CustomerModel = new CustomerModel();
            $OrdersModel = new OrdersModel();
            $searchAll = $this->request->getVar('searchAll');

            $rest = $ComplaintsModel->orderBy("id", "DESC")->findAll();
            $data = [];
            foreach ($rest as $key => $r) {
                $pdata = $ProductModel->where("id", $r["product_id"])->first();
                $pimgdata = $ProductImageModel->where("product_id", $r["product_id"])->where("image_index", 0)->first();
                $cdata = $CustomerModel->where("id", $r["user_id"])->first();


                $ter = $OrdersModel->where("id", $r["order_id"])->first();

                $data[$key]["id"] = $r["id"];
                $data[$key]["CustomerName"] = $cdata["name"];
                $data[$key]["OrderId"] = $ter["razorpay_order_id"];
                $data[$key]["ProductName"] = $pdata["name"];
                $data[$key]["ProductCode"] = $pdata["product_code"];
                $data[$key]["ProductImage"] = $pimgdata["path_128x128"];
                $data[$key]["Complaint"] = $r["description"];
                $data[$key]["ComplaintDate"] = date("Y-m-d", strtotime($r["created_at"]));
                $data[$key]["Status"] = $r["status"];
            }

            if (!($searchAll == null || $searchAll == '')) {
                $filteredData = [];
                foreach ($data as $item) {
                    if (stripos($item['ProductName'], $searchAll) !== false || stripos($item['CustomerName'], $searchAll) !== false || stripos($item['ProductCode'], $searchAll) !== false || stripos($item['ComplaintDate'], $searchAll) !== false) {
                        $filteredData[] = $item;
                    }
                }
                $data = $filteredData;
            }
            if ($data) {
                $response = [
                    "Status" => 200,
                    "Data" => $data
                ];
            } else {
                $response = [
                    "Status" => 404,
                    "Msg" => "No Data Found"
                ];
            }

            return $this->respond($response, 200);
        } catch (Exception $e) {
            return $this->respond([
                "Status" => 500,
                "Error" => $e->getMessage(),
                "line" => $e->getLine(),
            ], 200);
        }
    }
    function GetById($id)
    {
        $ComplaintsModel = new ComplaintsModel();
        $ProductModel = new ProductModel();
        $ProductImageModel = new ProductImageModel();
        $CustomerModel = new CustomerModel();
        $ComplaintsModel = new ComplaintsModel();
        $rest = $ComplaintsModel->where("id", $id)->first();
        $pdata = $ProductModel->where("id", $rest["product_id"])->first();
        $pimgdata = $ProductImageModel->where("product_id", $rest["product_id"])->where("image_index", 0)->first();
        $cdata = $CustomerModel->where("id", $rest["user_id"])->first();


        $data = [];

        $data["id"] = $rest["id"];
        $data["CustomerName"] = $cdata["name"];
        $data["ProductName"] = $pdata["name"];
        $data["ProductCode"] = $pdata["product_code"];
        $data["ProductImage"] = $pimgdata["path_128x128"];
        $data["Complaint"] = $rest["description"];
        $data["ComplaintDate"] = date("Y-m-d", strtotime($rest["created_at"]));
        $data["Status"] = $rest["status"];
        if ($data) {
            $response = [
                "Status" => 200,
                "Data" => $data
            ];
        } else {
            $response = [
                "Status" => 404,
                "Msg" => "No Data Found"
            ];
        }

        return $this->respond($response, 200);
    }
    function Delete($id)
    {
        $ComplaintsModel = new ComplaintsModel();
        $rest = $ComplaintsModel->delete($id);
        if ($rest) {
            $response = [
                "Status" => 200,
                "Msg" => "Complaints Deleted Successfully"
            ];
        } else {
            $response = [
                "Status" => 400,
                "Msg" => "Complaints Not Deleted Successfully"
            ];
        }

        return $this->respond($response, 200);
    }
    function Update()
    {
        $ComplaintsModel = new ComplaintsModel();
        $id = $this->request->getVar("id");
        $data = $this->request->getVar();

        $rules = $ComplaintsModel->getValidationRules();

        $fieldsToSkipUnique = [
            "user_id",
            "description",
            "product_id",
            "order_id"
        ];

        foreach ($fieldsToSkipUnique as $field) {
            if (isset($rules[$field])) {
                $backupRules[$field] = $rules[$field];
                unset($rules[$field]); // Remove the is_unique rule temporarily
            }
        }

        // Perform your validation with the modified rules
        $validationResult = $ComplaintsModel->setValidationRules($rules)->validate($data);

        if ($validationResult) {
            // Validation passed, update the data
            $rest = $ComplaintsModel->update($id, $data);
            if ($rest) {
                $response = [
                    "Status" => 200,
                    "Msg" => "Data Updated Successfully"
                ];
            } else {
                $response = [
                    "Status" => 403,
                    "Validation" => $ComplaintsModel->errors()
                ];
            }
        } else {
            // Validation failed
            $response = [
                "Status" => 403,
                "Validation" => $ComplaintsModel->errors()
            ];
        }

        // Restore the removed is_unique rule if applicable
        foreach ($fieldsToSkipUnique as $field) {
            if (isset($backupRules[$field])) {
                $rules[$field] = $backupRules[$field]; // Restore the removed is_unique rule
            }
        }

        $ComplaintsModel->setValidationRules($rules);


        return $this->respond($response, 200);
    }
    function Serach()
    {
        $type = $this->request->getVar('type');
        $keyword = $this->request->getVar('keyword');

        // Create an instance of the ComplaintsModel
        $ComplaintsModel = new ComplaintsModel();

        // Perform the search using the model
        $complaints = $ComplaintsModel->like('description', $keyword)->findAll();

        // Return the search results as JSON
        return $this->respond($complaints);
    }
}
