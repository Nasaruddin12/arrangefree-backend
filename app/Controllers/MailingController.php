<?php

namespace App\Controllers;

use App\Models\CouponModel;
use CodeIgniter\Controller;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Commands\Help;
use CodeIgniter\HTTP\RequestTrait;

class MailingController extends Controller
{
    use RequestTrait;
    use ResponseTrait;

    public function get_discounted_price($productRecord)
    {
        // var_dump($productRecord);
        $increasePrice = ($productRecord['actual_price'] / 100 * $productRecord['increase_percent']);
        $discount = $increasePrice / 100 * $productRecord['discounted_percent'];
        $actualPrice = $productRecord['actual_price'] + $increasePrice;
        $discountedPercent = $discount / $actualPrice * 100;

        // $singleProductPrice = ($productRecord['actual_price'] - ($productRecord['actual_price'] / 100 * $productRecord['discounted_percent']));
        // $productRecord['discounted_price'] = $singleProductPrice;
        $productRecord['actual_price'] = $actualPrice;
        $productRecord['discounted_percent'] = $discountedPercent;
        $productRecord['discounted_price'] = $actualPrice - $discount;
        return $productRecord;
    }
    public function post_order_mail($order_id = null)
    {
        // $order_id=374;   

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
            $customer_details = $db->query("select * from customers where id=$order_details[customer_id]")->getResultArray()[0];
            $invoice_id = $order_details['invoice_id'];
            $order_invoice = $db->query("select * from af_invoices where id=$invoice_id")->getResultArray()[0];
            $invoice_pdf = base_url($order_invoice['invoice_path']);
            // print_r($invoice_pdf);
            // die();
            // $order_date=date("Y-m-d",$order_details['created_at']);
            $order_date = $order_details['order_date'];
            $order_total = $order_details['total'];
            $order_subtotal = $order_details['subtotal'];
            $coupon = $order_details['coupon'];
            $couponModel = new CouponModel();
            $coupon = $couponModel->where('id', $coupon)->first();
            $couponDiscount = 0;
            if (!empty($coupon)) {
                switch ($coupon['coupon_type']) {
                    case 1:
                        $couponDiscount = ($order_total / 100 * $coupon['coupon_type_name']);
                        break;
                    case 2:
                        $couponDiscount = $coupon['coupon_type_name'];
                        break;
                }
                // $subTotal = $couponDiscount;
                $couponID = $coupon['id'];
                // $orderData['coupon_discount'] = $$couponDiscount;
                $orderData['coupon'] = $couponID;
            }
            $order_discount = $order_subtotal - $order_total;
            // Get products from order id
            $all_products = $db->query("select * from af_order_products where order_id=$order_id")->getResultArray();

            // Changing actual price of the products

            helper('products');
            $discounted_products = array_map('get_discounted_price', $all_products);
            // $discounted_products = array_map(array($this, 'get_discounted_price'), $all_products);
            // echo "<pre>";
            // print_r($discounted_products);die;
            // Get product_images
            foreach ($discounted_products as $key => $row) {
                $temp_data = $db->query("select * from af_products ap,af_product_images api where ap.id=$row[product_id] and api.product_id=$row[product_id] and api.image_index=0")->getResultArray()[0];
                $discounted_products[$key]['product_image'] = $temp_data['path_360x360'];
                $discounted_products[$key]['product_name'] = $temp_data['name'];
            }
            // print_r($discounted_products);
            // die();


            $data['order_id'] = $order_details['razorpay_order_id'];
            $data['order_date'] = $order_date;
            $data['order_total'] = $order_total;
            $data['is_cod'] = $order_details['is_cod'];
            $data['order_subtotal'] = $order_subtotal;
            $data['order_discount'] = $order_discount;
            $data['coupon_discount'] = $couponDiscount;
            $data['shipping_address'] = $customer_shipping_address['street_address'];
            $data['discounted_products'] = $discounted_products;
            $data['invoice_pdf_path'] = $invoice_pdf;
            $to = $customer_details['email'];
            $subject = "Your order has been successfully placed";
            $message = view('email_views/order_confirmation', $data);
            // return view('email_views/order_confirmation', $data);


            $email = \Config\Services::email();
            $email->setTo($to);
            // echo $to;
            $email->setBCC('admin@arrangefree.com');
            $email->setReplyTo('contact@arrangefree.com');
            $email->setFrom('no-reply@arrangefree.com', 'Arrange Free');

            $email->setSubject($subject);
            $email->setMessage($message);

            // Invoice Attachment
            // $email->attach($invoice_pdf);
            if ($email->send()) {
            } else {
                $data = $email->printDebugger(['headers']);
                // print_r($data);
            }
        }
    }

    public function send_promotion_mail()
    {
        $to = "hiantule@gmail.com";
        // $to = "khatiksahil@hapspro.com";
        $subject = "Promotions";
        $message = view('email_views/promotions/promotions_dorfee_2');

        $email = \Config\Services::email();
        $email->setTo($to);
        $email->setFrom('no-reply@dorfee.com', 'Dorfee');

        $email->setSubject($subject);
        $email->setMessage($message);

        // Invoice Attachment
        // $email->attach($invoice_pdf);
        if ($email->send()) {
            echo 'Email successfully sent';
        } else {
            $data = $email->printDebugger(['headers']);
            // print_r($data);
        }
    }
    public function post_order_mail_content()
    {
        return view('email_views/post_order_mail');
    }
    public function order_confirmation_content()
    {
        return view('email_views/order_confirmation');
    }
}
