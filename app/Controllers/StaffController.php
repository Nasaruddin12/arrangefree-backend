<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\QuotationModal;
use App\Models\StaffModel;
use CodeIgniter\API\ResponseTrait;
use Exception;

class StaffController extends BaseController
{
    use ResponseTrait;
    public function Create()
    {
        $staffModel = new StaffModel();
        try {
            $data = [
                "name" => $this->request->getVar("name"),
                "email" => $this->request->getVar("email"),
                "mobile_no" => $this->request->getVar("mobile_no"),
                "salary" => $this->request->getVar("salary"),
                "aadhar_no" => $this->request->getVar("aadhar_no"),
                "pan_no" => $this->request->getVar("pan_no"),
                "joining_date" => $this->request->getVar("joining_date"),
                "relieving_date" => $this->request->getVar("relieving_date"),
                "designation" => $this->request->getVar("designation"),
                "pan_card" => $this->request->getVar("pan_card"),
                "aadhar_card" => $this->request->getVar("aadhar_card"),
                "photo" => $this->request->getVar("photo"),
                "joining_letter" => $this->request->getVar("joining_letter"),
                "status" => $this->request->getVar("status"),
            ];

            if (!$this->validate($staffModel->validationRules, $staffModel->validationMessages)) {
                return $this->respond([
                    "status" => 400,
                    "message" => "Validation Failed",
                    "Errors" => $this->validator->getErrors(),
                ], 400);
            }

            $rest = $staffModel->insert($data);
            if ($rest) {
                $response = [
                    "status" => 201,
                    "message" => "Data Added Successfully",
                ];
            } else {
                $response = [
                    "status" => 500,
                    "message" => "Data Not Added Successfully",
                    "Validation" => $staffModel->errors(),
                ];
            }
        } catch (Exception $e) {
            $response = [
                "status" => 500,
                "Error" => $e->getMessage(),
                "Line" => $e->getLine()
            ];
        }
        return $this->respond($response, 200);
    }

    function getAllStaffs()
    {
        $staffModel = new StaffModel();
        $data = $staffModel->findAll();
        if ($data) {
            $response = [
                "status" => 200,
                "Data" => $data,
            ];
        } else {
            $response = [
                "status" => 404,
                "message" => "Not Data Found",
            ];
        }
        return $this->respond($response, 200);
    }
    function getAllStaffByID($id)
    {
        $staffModel = new StaffModel();
        $data = $staffModel->where("id", $id)->first();
        if ($data) {
            $response = [
                "status" => 200,
                "Data" => $data,
            ];
        } else {
            $response = [
                "status" => 404,
                "message" => "Not Data Found",
            ];
        }
        return $this->respond($response, 200);
    }
    public function UpdateStaff($id = null)
    {
        try {
            $staffModel = new StaffModel();

            // Check if staff exists
            $staff = $staffModel->find($id);
            if (!$staff) {
                return $this->respond([
                    "status"  => 404,
                    "message" => "Staff not found",
                ], 404);
            }

            // Prepare the data from the request
            $data = [
                "name"           => $this->request->getVar("name"),
                "email"          => $this->request->getVar("email"),
                "mobile_no"      => $this->request->getVar("mobile_no"),
                "salary"         => $this->request->getVar("salary"),
                "aadhar_no"      => $this->request->getVar("aadhar_no"),
                "pan_no"         => $this->request->getVar("pan_no"),
                "joining_date"   => $this->request->getVar("joining_date"),
                "relieving_date" => $this->request->getVar("relieving_date"),
                "designation"    => $this->request->getVar("designation"),
                "pan_card"       => $this->request->getVar("pan_card"),
                "aadhar_card"    => $this->request->getVar("aadhar_card"),
                "photo"          => $this->request->getVar("photo"),
                "joining_letter" => $this->request->getVar("joining_letter"),
                "status"         => $this->request->getVar("status"),
            ];

            // Get validation rules from model
            $rules = $staffModel->getValidationRules();

            // Fields that should skip `is_unique` for the same ID
            $fieldsToSkipUnique = ["email", "aadhar_no", "pan_no"];

            // Replace `{id}` in validation rules with the actual ID
            foreach ($fieldsToSkipUnique as $field) {
                if (isset($rules[$field])) {
                    // Replace `{id}` placeholder in the rule with the actual staff ID
                    $rules[$field] = str_replace("{id}", $id, $rules[$field]);
                }
            }

            // Validate data with modified rules
            if (!$this->validate($rules)) {
                return $this->respond([
                    "status"  => 400,
                    "message" => "Validation Failed",
                    "Errors"  => $this->validator->getErrors(),
                ], 400);
            }

            // Update record
            if ($staffModel->update($id, $data)) {
                return $this->respond([
                    "status"  => 200,
                    "message" => "Data Updated Successfully",
                ], 200);
            }

            return $this->respond([
                "status"  => 500,
                "message" => "Data Not Updated Successfully",
                "Errors"  => $staffModel->errors(),
            ], 500);
        } catch (Exception $e) {
            return $this->respond([
                "status" => 500,
                "Error"  => $e->getMessage(),
                "Line"   => $e->getLine()
            ], 500);
        }
    }
    function Delete($id)
    {
        $staffModel = new StaffModel();
        $data = $staffModel->update($id, [
            "status" => 2
        ]);
        if ($data) {
            $response = [
                "status" => 200,
                "message" => "Data Deleted Successfully",
            ];
        } else {
            $response = [
                "status" => 500,
                "message" => "Data Not Deleted Successfully",
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
                    "status" => 200,
                    "message" => "File Uploaded Successfully",
                    "path" => $uploadDirectory . $fileName
                ];
            } else {
                $response = [
                    "status" => 400,
                    "message" => "File Not Uploaded Successfully"
                ];
            }
        } catch (Exception $e) {
            $response = [
                "status" => 500,
                "Error" => $e->getMessage(),
                "Line" => $e->getLine(),
            ];
        }
        return $this->respondCreated($response, 200);
    }

    public function deletefile()
    {
        try {
            $filePath = $this->request->getVar('file_path'); // Get file path from request

            // Check if the file exists
            if (file_exists($filePath)) {
                // Delete the file
                if (unlink($filePath)) {
                    $response = [
                        "status" => 200,
                        "message" => "File Deleted Successfully",
                        "path" => $filePath
                    ];
                } else {
                    $response = [
                        "status" => 400,
                        "message" => "File Not Deleted Successfully"
                    ];
                }
            } else {
                $response = [
                    "status" => 404,
                    "message" => "File Not Found"
                ];
            }
        } catch (Exception $e) {
            $response = [
                "status" => 500,
                "Error" => $e->getMessage(),
                "Line" => $e->getLine(),
            ];
        }

        return $this->respondCreated($response, 200);
    }
    public function UpdateStaffstatus($id = null)
    {
        try {
            $staffModel = new StaffModel();

            // Check if staff exists
            if (!$staffModel->find($id)) {
                return $this->respond([
                    "status"  => 404,
                    "message" => "Staff not found",
                ], 404);
            }

            // Prepare the data from the request for status and relieving date
            $data = [
                "status"         => $this->request->getVar("status"),
                "relieving_date" => $this->request->getVar("relieving_date"),
            ];

            // Define validation rules (e.g., check if status is valid and relieving date is a valid date)
            $validationRules = [
                'status'         => 'required|in_list[active,inactive]',
            ];
            if ($data['relieving_date']) {
                $validationRules['relieving_date'] = 'valid_date';
            }

            // Validate data
            if (!$this->validate($validationRules)) {
                return $this->respond([
                    "status"  => 400,
                    "message" => "Validation Failed",
                    "Errors"  => $this->validator->getErrors(),
                ], 400);
            }

            // Update record with new status and relieving date
            if ($staffModel->update($id, $data)) {
                return $this->respond([
                    "status"  => 200,
                    "message" => "status and Relieving Date Updated Successfully",
                ], 200);
            }

            return $this->respond([
                "status"  => 500,
                "message" => "Data Not Updated Successfully",
                "Errors"  => $staffModel->errors(),
            ], 500);
        } catch (Exception $e) {
            return $this->respond([
                "status" => 500,
                "Error"  => $e->getMessage(),
                "Line"   => $e->getLine()
            ], 500);
        }
    }
}
