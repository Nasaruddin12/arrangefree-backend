<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;

class OtpController extends BaseController
{
    use ResponseTrait;
    public $gateway_key = "addGYdRieeyho068Dp6uwg==";
    public function notify_vendor_reg()
    {
        $send_to_number = $this->request->getVar('send_to_number');
        $vendor_code = $this->request->getVar('vendor_code');
        if (empty($send_to_number)) {
            $response = [
                'status' => 0,
                'msg' => "param send_to_number cannot be empty"
            ];
            return $this->respond($response, 401);
        } else if (empty($vendor_code)) {
            $response = [
                'status' => 0,
                'msg' => "param vendor_code cannot be empty"
            ];
            return $this->respond($response, 401);
        } else {
            $panel_link = "admin.dorfee.in";
            $message = "Dear Vendor, You are successfully registered on Arrange Free as vendor and your vendor code is $vendor_code \nWelcome to Arrange Free Family! \nPanel Access:$panel_link \n\n-Team Haps";
            // $message = "Dear Vendor, You are successfully registered  ARRANGE FREE as vendor and your vendor code is $vendor_code \nWelcome to ARRANGE FREE Family!\nPanel Access:$panel_link \n\n-Team Haps";
            $message = urlencode($message);

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://japi.instaalerts.zone/httpapi/QueryStringReceiver?ver=1.0&key=$this->gateway_key&encrpt=0&dest=$send_to_number&send=HAPSIN&text=$message",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
            )
            );

            $response = curl_exec($curl);

            curl_close($curl);
            echo $response;
        }

    }

    public function notify_order_confirmation()
    {
        $send_to_number = $this->request->getVar('send_to_number');
        $order_id = $this->request->getVar('order_id');
        if (empty($send_to_number)) {
            $response = [
                'status' => 0,
                'msg' => "param send_to_number cannot be empty"
            ];
            return $this->respond($response, 401);
        } else if (empty($order_id)) {
            $response = [
                'status' => 0,
                'msg' => "param order_id cannot be empty"
            ];
            return $this->respond($response, 401);
        } else {
            $tracking_link = "https://arrangefree.com/";
            $message = "Thank you for your purchase on Arrange Free. Your order id is $order_id . Click this link to track your order: $tracking_link \n\n--Team Haps";
            // $message = "Dear Vendor, You are successfully registered  ARRANGE FREE as vendor and your vendor code is $vendor_code \nWelcome to ARRANGE FREE Family!\nPanel Access:$panel_link \n\n-Team Haps";
            $message = urlencode($message);

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://japi.instaalerts.zone/httpapi/QueryStringReceiver?ver=1.0&key=$this->gateway_key&encrpt=0&dest=$send_to_number&send=HAPSIN&text=$message",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
            )       
            );

            $response = curl_exec($curl);

            curl_close($curl);
            echo $response;
        }

    }
    public function notify_otp_verification()
    {
        $send_to_number = $this->request->getVar('send_to_number');
        $otp = $this->request->getVar('otp');
        if (empty($send_to_number)) {
            $response = [
                'status' => 0,
                'msg' => "param send_to_number cannot be empty"
            ];
            return $this->respond($response, 401);
        } else if (empty($otp)) {
            $response = [
                'status' => 0,
                'msg' => "param otp cannot be empty"
            ];
            return $this->respond($response, 401);
        } else {
            $message = "$otp is your OTP for verification with Arrange Free \n\n-Team Haps";
            $message = urlencode($message);

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://japi.instaalerts.zone/httpapi/QueryStringReceiver?ver=1.0&key=$this->gateway_key&encrpt=0&dest=$send_to_number&send=HAPSIN&text=$message",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
            )
            );

            $response = curl_exec($curl);

            curl_close($curl);
            echo $response;
        }

    }
}