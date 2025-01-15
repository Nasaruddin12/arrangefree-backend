<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\CustomerModel;
use App\Models\OrderProductsModel;
use App\Models\OrdersModel;
use App\Models\PhonePeTransactionModel;
use App\Models\ProductImageModel;
use App\Models\ProductModel;
use App\Models\TransactionModel;
use CodeIgniter\API\ResponseTrait;

class TransactionController extends BaseController
{
    use ResponseTrait;
    public function getTransactionsByCustomerId($customerId)
    {
        $transactionModel = new TransactionModel();

        try {
            $transactions = $transactionModel
                ->where('customer_id', $customerId)
                ->findAll();

            return $this->respond([
                'status' => 200,
                'message' => 'Transactions retrieved successfully',
                'data' => $transactions,
            ]);
        } catch (\Exception $e) {
            return $this->respond([
                'status' => $e->getCode(),
                'message' => $e->getMessage(),
            ], $e->getCode());
        }
    }
    function GetAll()
    {
        $PhonePeTransactionModel = new PhonePeTransactionModel();
        $CustomerModel = new CustomerModel();
        $OrderProductsModel = new OrderProductsModel();
        $ProductModel = new ProductModel();
        $ProductImageModel = new ProductImageModel();
        $OrdersModel = new OrdersModel();
        $searchAll = $this->request->getVar('searchAll');

        $rest = $PhonePeTransactionModel->orderBy("id", "DESC")->findAll();
        $data = [];
        $i = 0;

        foreach ($rest as $k => $re) {
            $string = $re["merchantTransactionId"]; // Your input string
            $modifiedString = substr($string, 3);
            $orddata = $OrdersModel->like("razorpay_order_id", $modifiedString)->first();

            if ($orddata) {
                $POdata = $OrderProductsModel->where("order_id", $orddata["id"])->first();
                $Cdata = $CustomerModel->where("id", $orddata["customer_id"])->first();
                $Pdata = $ProductModel->where("id", $POdata["product_id"])->first();
                $PImgdata = $ProductImageModel->where("product_id", $POdata["product_id"])->where("image_index", 0)->first();

                $data[$i]["CustomerName"] = $Cdata["name"];
                $data[$i]["ProductName"] = $Pdata["name"];
                $data[$i]["ProductCode"] = $Pdata["product_code"];
                $data[$i]["ProductImage"] = $PImgdata["path_128x128"];
                $data[$i]["TransactionId"] = $re["merchantTransactionId"];
                $data[$i]["Transaction_Status"] = $re["transation_status"];
                $data[$i]["Amount"] = $re["amount"] / 100;
                $data[$i]["Created_At"] = date("Y-m-d", strtotime($re["created_at"]));
                $i++;
            }
        }

        if (!($searchAll == null || $searchAll == '')) {
            $filteredData = [];
            foreach ($data as $item) {
                if (stripos($item['TransactionId'], $searchAll) !== false || stripos($item['CustomerName'], $searchAll) !== false || stripos($item['Amount'], $searchAll) !== false) {
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
                "Msg" => "No Data Available"
            ];
        }
        return $this->respond($response, 200);
    }

    function GetById($id)
    {
        $PhonePeTransactionModel = new PhonePeTransactionModel();
        $CustomerModel = new CustomerModel();
        $OrderProductsModel = new OrderProductsModel();
        $ProductModel = new ProductModel();
        $ProductImageModel = new ProductImageModel();
        $OrdersModel = new OrdersModel();
        $rest = $PhonePeTransactionModel->where("id", $id)->findAll();
        $data = [];
        foreach ($rest as $k => $re) {
            $string = $re["merchantTransactionId"]; // Your input string
            $modifiedString = substr($string, 3);
            $orddata = $OrdersModel->like("razorpay_order_id", $modifiedString)->first();
            if ($orddata) {
                $POdata = $OrderProductsModel->where("order_id", $orddata["id"])->first();
                $Cdata = $CustomerModel->where("id", $orddata["customer_id"])->first();
                $Pdata = $ProductModel->where("id", $POdata["product_id"])->first();



                $PImgdata = $ProductImageModel->where("product_id", $POdata["product_id"])->where("image_index", 0)->first();



                $data[$k]["CustomerName"] = $Cdata["name"];
                $data[$k]["ProductName"] = $Pdata["name"];
                $data[$k]["ProductCode"] = $Pdata["product_code"];
                $data[$k]["ProductImage"] = $PImgdata["path_128x128"];
                $data[$k]["TransactionId"] = $re["merchantTransactionId"];
                $data[$k]["Transaction_Status"] = $re["transation_status"];
                $data[$k]["Amount"] = $re["amount"] / 100;
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
}