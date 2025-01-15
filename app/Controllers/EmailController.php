<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;

class EmailController extends BaseController
{
    use ResponseTrait;
    public function order_confirmed($order_id, $toEmail)
    {
        // $order_id = $this->request->getVar('order_id');

        if (empty($order_id)) {
            $response = [
                'status' => 0,
                'msg' => "param order_id cannot be empty"
            ];
            return $this->respond($response, 200);
        } else {
            $db = db_connect();
            $order_details = $db->query("select *,date(created_at) as order_date from af_orders where id=$order_id")->getResultArray()[0];
            $customer_shipping_address = $db->query("select * from af_customer_address where id=$order_details[address_id]")->getResultArray()[0];
            $invoice_id = $order_details['invoice_id'];
            $order_invoice = $db->query("select * from af_invoices where id=$invoice_id")->getResultArray()[0];
            $invoice_pdf = $order_invoice['invoice_path'];
            $order_date = $order_details['order_date'];
            $order_total = $order_details['subtotal'];

            $data['order_id'] = $order_details['razorpay_order_id'];
            $data['order_date'] = $order_date;
            $data['order_total'] = $order_total;
            $data['shipping_address'] = $customer_shipping_address['street_address'];
            $to = $toEmail;
            $subject = "Your order has been successfully placed";
            $message = view('email_views/order_confirmation', $data);


            $email = \Config\Services::email();
            $email->setTo($to);
            $email->setFrom('no-reply@dorfee.com', 'Dorfee');

            $email->setSubject($subject);
            $email->setMessage($message);

            // Invoice Attachment
            $email->attach($invoice_pdf);
            $response = $email->send();
            // echo 'email ' . $response;
            return $response;

            /* if ($email->send()) {
                echo 'Email successfully sent';
            } else {
                $data = $email->printDebugger(['headers']);
                print_r($data);
            } */
        }
    }

    public function sendMail($emailID, $subject, $message)
    {
        $email = \Config\Services::email();
        $to = $emailID;


        $email->setTo($to);
        $email->setFrom('no-reply@dorfee.com', 'Dorfee');
        $email->setSubject($subject);
        $email->setMessage($message);
        // print_r($email->send());die;
        return $email->send();
    }
}
