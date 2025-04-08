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


    public function sendWelcomeEmail($toEmail, $userName)
    {
        $toEmail = 'haseeb@seeb.in';
        $userName = 'Haseeb Khan';

        $email = \Config\Services::email();

        $email->setTo($toEmail);
        $email->setFrom('info@seeb.in', 'Seeb');
        $email->setSubject('Welcome to Seeb!');

        // Create email content
        $emailContent = '
        <!DOCTYPE html>
        <html lang="en">
        <head>
          <meta charset="UTF-8">
          <title>Welcome to Seeb</title>
        </head>
        <body style="margin:0; padding:0; background-color:#f4f4f4; font-family: Arial, sans-serif;">
          <table width="100%" bgcolor="#f4f4f4" cellpadding="0" cellspacing="0">
            <tr>
              <td>
                <table align="center" width="600" bgcolor="#ffffff" cellpadding="40" cellspacing="0" style="border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.05);">
                  <tr>
                    <td align="center">
                      <img src="https://backend.seeb.in/public/logo.webp" alt="Seeb Logo" width="120" style="margin-bottom: 20px;">
                      <h1 style="color: #333;">Welcome to Seeb!</h1>
                      <p style="font-size: 16px; color: #555; line-height: 1.6;">
                        Hello <strong>{USERNAME}</strong>,
                        <br><br>
                        We’re thrilled to have you on board. Thank you for joining Seeb – your one-stop solution for premium services.
                        <br><br>
                        You can now login and explore all our offerings.
                      </p>
                      <a href="https://seeb.in/login" style="display: inline-block; margin-top: 20px; padding: 12px 24px; background-color: #1e88e5; color: #fff; text-decoration: none; border-radius: 5px;">
                        Login Now
                      </a>
                      <p style="margin-top: 30px; font-size: 14px; color: #999;">
                        If you have any questions, feel free to contact us at <a href="mailto:info@seeb.in">info@seeb.in</a>.
                      </p>
                      <hr style="margin: 30px 0; border: none; border-top: 1px solid #eee;">
                      <p style="font-size: 12px; color: #aaa;">&copy; ' . date("Y") . ' Seeb. All rights reserved.</p>
                    </td>
                  </tr>
                </table>
              </td>
            </tr>
          </table>
        </body>
        </html>
        ';

        // Replace username placeholder
        $emailContent = str_replace('{USERNAME}', $userName, $emailContent);

        // Send email
        $email->setMessage($emailContent);
        $email->setMailType('html');

        if ($email->send()) {
            return '✅ Welcome email sent to ' . $toEmail;
        } else {
            return '❌ Email failed to send. <br>' . print_r($email->printDebugger(['headers']), true);
        }
    }
}
