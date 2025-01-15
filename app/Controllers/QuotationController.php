<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\QuotationModal;
use App\Models\StaffsModel;
use CodeIgniter\API\ResponseTrait;
use Exception;

class QuotationController extends BaseController
{
    use ResponseTrait;
    public function Create()
    {


        $StaffsModel = new StaffsModel();
        try {
            $data = [
                "username" => $this->request->getVar("username"),
                "password" => password_hash($this->request->getVar("password"), PASSWORD_BCRYPT),
                "name" => $this->request->getVar("name"),
                "phone" => $this->request->getVar("phone"),
                "email" => $this->request->getVar("email"),
                "aadhaar_no" => $this->request->getVar("aadhaar_no"),
                "pan_no" => $this->request->getVar("pan_no"),
                "adhaar_file" => $this->request->getVar("adhaar_file"),
                "pan_file" => $this->request->getVar("pan_file")
            ];
            $rest = $StaffsModel->insert($data);
            if ($rest) {
                $response = [
                    "Status" => 201,
                    "Msg" => "Data Added Successfully",
                ];
            } else {
                $response = [
                    "Status" => 500,
                    "Msg" => "Data Not Added Successfully",
                    "Validation" => $StaffsModel->errors(),
                ];
            }
        } catch (Exception $e) {
            $response = [
                "Status" => 500,
                "Error" => $e->getMessage(),
                "Line" => $e->getLine()
            ];
        }
        return $this->respond($response, 200);
    }
    function getAllStaffs()
    {
        $StaffsModel = new StaffsModel();
        $data = $StaffsModel->findAll();
        if ($data) {
            $response = [
                "Status" => 200,
                "Data" => $data,
            ];
        } else {
            $response = [
                "Status" => 404,
                "Msg" => "Not Data Found",
            ];
        }
        return $this->respond($response, 200);
    }
    function getAllStaffByID($id)
    {
        $StaffsModel = new StaffsModel();
        $data = $StaffsModel->where("id", $id)->first();
        if ($data) {
            $response = [
                "Status" => 200,
                "Data" => $data,
            ];
        } else {
            $response = [
                "Status" => 404,
                "Msg" => "Not Data Found",
            ];
        }
        return $this->respond($response, 200);
    }
    function UpdateStaff()
    {
        try {
            $id = $this->request->getVar("id");
            $StaffsModel = new StaffsModel();
            $data = [
                "username" => $this->request->getVar("username"),
                "password" => password_hash($this->request->getVar("password"), PASSWORD_BCRYPT),
                "name" => $this->request->getVar("name"),
                "phone" => $this->request->getVar("phone"),
                "email" => $this->request->getVar("email"),
                "aadhaar_no" => $this->request->getVar("aadhaar_no"),
                "pan_no" => $this->request->getVar("pan_no"),
                "adhaar_file" => $this->request->getVar("adhaar_file"),
                "pan_file" => $this->request->getVar("pan_file")
            ];




            $rules = $StaffsModel->getValidationRules();

            $fieldsToSkipUnique = ["username", "password", "name", "phone", "email", "aadhaar_no", "pan_no", "adhaar_file", "pan_file", "status"];

            foreach ($fieldsToSkipUnique as $field) {
                if (isset($rules[$field])) {
                    $backupRules[$field] = $rules[$field];
                    unset($rules[$field]); // Remove the is_unique rule temporarily
                }
            }

            // Perform your validation with the modified rules
            $validationResult = $StaffsModel->setValidationRules($rules)->validate($data);

            if ($validationResult) {
                // Validation passed, update the data

                $rest = $StaffsModel->update($id, $data);

                if ($rest) {
                    $response = [
                        "Status" => 200,
                        "Msg" => "Data Updated Successfully",
                    ];
                } else {
                    $response = [
                        "Status" => 500,
                        "Msg" => "Data Not Updated Successfully",
                        "Validation" => $StaffsModel->errors(),
                    ];
                }
            } else {
                // Validation failed
                $response = [
                    "Status" => 403,
                    "Validation" => $StaffsModel->errors()
                ];
            }

            // Restore the removed is_unique rule if applicable
            foreach ($fieldsToSkipUnique as $field) {
                if (isset($backupRules[$field])) {
                    $rules[$field] = $backupRules[$field]; // Restore the removed is_unique rule
                }
            }

            $StaffsModel->setValidationRules($rules);
        } catch (Exception $e) {
            $response = [
                "Status" => 500,
                "Error" => $e->getMessage(),
                "Line" => $e->getLine()
            ];
        }
        return $this->respond($response, 200);
    }
    function Delete($id)
    {
        $StaffsModel = new StaffsModel();
        $data = $StaffsModel->update($id, [
            "status" => 2
        ]);
        if ($data) {
            $response = [
                "Status" => 200,
                "Msg" => "Data Deleted Successfully",
            ];
        } else {
            $response = [
                "Status" => 500,
                "Msg" => "Data Not Deleted Successfully",
            ];
        }
        return $this->respond($response, 200);
    }
    function QuotationCreate()
    {
        $QuotationModal = new QuotationModal();
        $data = [
            "customer_name" => $this->request->getVar("customer_name"),
            "phone" => $this->request->getVar("phone"),
            "address" => $this->request->getVar("address"),
            "items" => $this->request->getVar("items"),
            "mark_list" => $this->request->getVar("mark_list"),
            "total_amount" => $this->request->getVar("total_amount"),
            "sgst" => $this->request->getVar("sgst"),
            "cgst" => $this->request->getVar("cgst"),
            "installment" => $this->request->getVar("installment"),
            "time_line" => $this->request->getVar("time_line"),
            "created_by" => $this->request->getVar("created_by")
        ];
        $rest = $QuotationModal->insert($data);
        if ($rest) {
            $response = [
                "Status" => 201,
                "Msg" => "Data Added Successfully"
            ];
        } else {
            $response = [
                "Status" => 500,
                "Msg" => "Data Not Added Successfully"
            ];
        }
        return $this->respond($response, 200);
    }
    function GetAllQuotation()
    {
        $QuotationModal = new QuotationModal();
        $rest = $QuotationModal->select(["id", "customer_name", "phone", "address", "created_by"])->findAll();
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
        return $this->respond($response, 200);
    }
    function QuotationUpdate()
    {
        $QuotationModal = new QuotationModal();

        $id = $this->request->getVar("id");
        $data = [
            "customer_name" => $this->request->getVar("customer_name"),
            "phone" => $this->request->getVar("phone"),
            "address" => $this->request->getVar("address"),
            "items" => $this->request->getVar("items"),
            "mark_list" => $this->request->getVar("mark_list"),
            "total_amount" => $this->request->getVar("total_amount"),
            "sgst" => $this->request->getVar("sgst"),
            "cgst" => $this->request->getVar("cgst"),
            "installment" => $this->request->getVar("installment"),
            "time_line" => $this->request->getVar("time_line"),
            "created_by" => $this->request->getVar("created_by")
        ];
        $rest = $QuotationModal->update($id, $data);
        if ($rest) {
            $response = [
                "Status" => 200,
                "Msg" => "Data Updated Successfully"
            ];
        } else {
            $response = [
                "Status" => 500,
                "Msg" => "Data Not Updated Successfully"
            ];
        }
        return $this->respond($response, 200);
    }
    function GetQuotationById($id)
    {
        $QuotationModal = new QuotationModal();
        $rest = $QuotationModal->where("id", $id)->first();
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
        return $this->respond($response, 200);
    }
    function QuotationDelete($id)
    {
        $QuotationModal = new QuotationModal();
        $rest = $QuotationModal->where("id", $id)->delete();
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
        return $this->respond($response, 200);
    }
    function FileUpload()
    {
        try {
            $file = $this->request->getFile('file');

            $uploadDirectory = 'uploads/Staff/';

            $fileName = $file->getRandomName();
            if ($file->move($uploadDirectory, $fileName)) {
                $response = [
                    "Status" => 200,
                    "Msg" => "File Uploaded Successfully",
                    "path" => $uploadDirectory . $fileName
                ];
            } else {
                $response = [
                    "Status" => 400,
                    "Msg" => "File Not Uploaded Successfully"
                ];
            }
        } catch (Exception $e) {
            $response = [
                "Status" => 500,
                "Error" => $e->getMessage(),
                "Line" => $e->getLine(),
            ];
        }
        return $this->respondCreated($response, 200);
    }
}
