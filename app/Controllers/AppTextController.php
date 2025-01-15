<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\AppTechHeadersModel;
use App\Models\AppTechHeadersValueModel;
use CodeIgniter\API\ResponseTrait;

class AppTextController extends BaseController
{
    use ResponseTrait;
    function AddHeaders()
    {
        $AppTechHeadersModel = new AppTechHeadersModel();
        $data = $AppTechHeadersModel->insert([
            "name" => $this->request->getVar("name")
        ]);
        if ($data) {
            $response = [
                "Status" => 201,
                "Msg" => "Data Added Successfully"
            ];
        } else {
            $response = [
                "Status" => 400,
                "Msg" => "Data Not Added Successfully",
            ];
        }
        return $this->respond($response, 200);
    }
    public function GetHeaders()
    {
        $AppTechHeadersModel = new AppTechHeadersModel();
        $AppTechHeadersValueModel = new AppTechHeadersValueModel();
        $data = $AppTechHeadersModel->select(["id", "name"])->findAll();
        foreach ($data as $key =>  $d) {
            if ($AppTechHeadersValueModel->where("header_id", $d["id"])->first()) {
                $value =  $AppTechHeadersValueModel->where("header_id", $d["id"])->first();
                $data[$key]["value"] = $value["value"];
            } else {
                $data[$key]["value"] = "--";
            }
        }
        if ($data) {
            $response = [
                "Status" => 200,
                "Data" => $data
            ];
        } else {
            $response = [
                "Status" => 404,
                "Msg" => "No Data Available"
            ];
        }
        return $this->respond($response, 200);
    }
    function AddHeadersValue()
    {
        $AppTechHeadersValueModel = new AppTechHeadersValueModel();

        $header_id = $this->request->getVar("header_id");
        $value = $this->request->getVar("value");


        $data =  $AppTechHeadersValueModel->insert([
            "header_id" => $header_id,
            "value" => $value
        ]);
        if ($data) {
            $response = [
                "Status" => 201,
                "Msg" => "Data Added Successfully"
            ];
        } else {
            $response = [
                "Status" => 400,
                "Msg" => "Data Not Added Successfully",
                "Error" => $AppTechHeadersValueModel->errors()
            ];
        }
        return $this->respond($response, 200);
    }
    function UpdateHeadersValue($id)
    {
        $AppTechHeadersValueModel = new AppTechHeadersValueModel();

        // $id = $this->request->getVar("header_id");
        $value = $this->request->getVar("value");

        $record = $AppTechHeadersValueModel->where("header_id", $id)->first();


        $data = $AppTechHeadersValueModel->update($record['id'], [
            "value" => $value
        ]);
        if ($data) {
            $response = [
                "Status" => 201,
                "Msg" => "Data Updated Successfully"
            ];
        } else {
            $response = [
                "Status" => 400,
                "Msg" => "Data Not Updated Successfully",
            ];
        }
        return $this->respond($response, 200);
    }
}
