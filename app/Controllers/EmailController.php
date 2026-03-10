<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\CustomerModel;
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
    $email->setFrom('info@seeb.in', 'Seeb');
    $email->setSubject($subject);
    $email->setMessage($message);
    // print_r($email->send());die;
    return $email->send();
  }


  public function sendWelcomeEmail($toEmail, $userName)
  {
    // $toEmail = 'haseeb@seeb.in';
    // $userName = 'Mr. Haseeb Khan';
    $email = \Config\Services::email();

    $email->setTo($toEmail);
    $email->setFrom('info@seeb.in', 'Seeb');
    $email->setSubject('🎉 Welcome to Seeb – Let’s Design and Build Your Dream Space');

    $emailContent = '
        <!DOCTYPE html>
        <html lang="en">
        <head>
          <meta charset="UTF-8">
          <title>Welcome to Seeb</title>
        </head>
        <body style="margin:0; padding:0; background-color:#f9f9f9; font-family: Arial, sans-serif;">
          <table width="100%" bgcolor="#f9f9f9" cellpadding="0" cellspacing="0">
            <tr>
              <td align="center">
                <table width="600" bgcolor="#ffffff" cellpadding="40" cellspacing="0" style="border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.05);">
                  <tr>
                    <td align="center">
                      <img src="https://backend.seeb.in/public/logo.webp" alt="Seeb Logo" width="120" style="margin-bottom: 20px;">
                      <h2 style="color: #333;">🎉 Welcome to Seeb!</h2>
                      <p style="font-size: 16px; color: #555; line-height: 1.6; text-align:left;">
                        Hi <strong>{USERNAME}</strong>,
                        <br><br>
                        Welcome to <strong>Seeb</strong>, India’s most advanced interior platform.
                        We’re excited to help you design your space with clarity, speed, and 100% execution control.
                      </p>
    
                      <h3 style="color: #1e88e5; text-align:left;">✨ What You Can Do in Seeb</h3>
                      <ul style="font-size: 15px; color: #444; text-align:left; padding-left:20px;">
                        <li>📏 <strong>Scan Your Room or Enter Size</strong><br>Use LiDAR or enter basic dimensions — get your 2D layout instantly.</li>
                        <li>🎨 <strong>Choose Your Design Style</strong><br>Select Modern, Luxury, or Traditional. Seeb suggests design ideas room-by-room.</li>
                        <li>🛋 <strong>Customize Wall by Wall</strong><br>Design your TV wall, sofa side, partition, bed area, wardrobe, ceiling, and more.</li>
                        <li>👀 <strong>Get 3D Design + 2D Plan</strong><br>We deliver full 3D room design, 2D drawings, and execution-ready visuals.</li>
                        <li>🧾 <strong>Auto Material & Color Breakdown</strong><br>See all wall shades, laminates, curtain fabrics, and finishes clearly for every design.</li>
                      </ul>
    
                      <h3 style="color: #1e88e5; text-align:left;">🛠 Meet Your Skilled Team</h3>
                      <p style="text-align:left; font-size: 15px; color: #444;">
                        Your on-site work will be executed by our Skilled Team – Seeb Certified All-in-One<br><br>
                        They are:
                        <ul style="text-align:left; padding-left:20px;">
                          <li>✅ Trained & Verified by Seeb</li>
                          <li>✅ Skilled in furniture install, false ceilings, wall panels, electricals, plumbing, paint</li>
                          <li>✅ Always available near your pin code</li>
                          <li>✅ Guaranteed to follow your exact design</li>
                          <li>✅ Monitored by Seeb support team for quality, speed, and finish</li>
                        </ul>
                      </p>
    
                      <p style="text-align:left; font-size: 15px; color: #444;">
                        📍 You can also visit our Seeb Experience Centers in Pune to see how this works live.
                      </p>
    
                      <h3 style="color: #1e88e5; text-align:left;">🚀 What’s Next?</h3>
                      <p style="text-align:left; font-size: 15px; color: #444;">
                        Open the Seeb app<br>
                        Create or scan your room<br>
                        Start designing OR tap <strong>Request a Call</strong><br>
                        Our team is ready to help you step by step.
                      </p>
    
                      <a href="https://seeb.in/login" style="display: inline-block; margin-top: 30px; padding: 12px 24px; background-color: #1e88e5; color: #fff; text-decoration: none; border-radius: 5px;">
                        Login to Seeb
                      </a>
    
                      <p style="margin-top: 30px; font-size: 14px; color: #999;">
                        Need help? Contact us anytime at <a href="mailto:info@seeb.in">info@seeb.in</a>.
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

    $emailContent = str_replace('{USERNAME}', $userName, $emailContent);

    $email->setMessage($emailContent);
    $email->setMailType('html');

    if ($email->send()) {
      return '✅ Welcome email sent to ' . $toEmail;
    } else {
      return '❌ Email failed to send. <br>' . print_r($email->printDebugger(['headers']), true);
    }
  }

  public function sendRoomStepEmailToMultiple(array $recipients)
  {
    // $recipients = [
    //   ['email' => 'myselfnasaruddin@gmail.com', 'name' => 'Nasaruddin Mulla'],
    //   ['email' => 'haseeb@seeb.in', 'name' => 'Haseeb Khan'],
    //   ['email' => 'aftab@seeb.in', 'name' => 'Aftab Naik'],
    // ];
    $email = \Config\Services::email();
    $results = [];

    foreach ($recipients as $recipient) {
      // Expecting ['email' => '...', 'name' => '...']
      $toEmail = $recipient['email'];
      $userName = $recipient['name'];

      $email->clear(); // Clear previous email setup

      $email->setTo($toEmail);
      $email->setFrom('info@seeb.in', 'Seeb');
      $email->setSubject('Begin Your First Step – Add Your Room Size or Scan It');

      $emailContent = '
          <!DOCTYPE html>
          <html lang="en">
          <head>
              <meta charset="UTF-8">
              <title>Start Designing with Seeb</title>
          </head>
          <body style="margin:0; padding:0; background-color:#f9f9f9; font-family: Arial, sans-serif;">
              <table width="100%" bgcolor="#f9f9f9" cellpadding="0" cellspacing="0">
                  <tr>
                      <td>
                          <table align="center" width="600" bgcolor="#ffffff" cellpadding="40" cellspacing="0" style="border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.05);">
                              <tr>
                                  <td align="center">
                                      <img src="https://backend.seeb.in/public/logo.webp" alt="Seeb Logo" width="120" style="margin-bottom: 20px;">
                                      <h2 style="color: #333;">Hi {USERNAME},</h2>
                                      <p style="font-size: 16px; color: #555; line-height: 1.6;">
                                          Welcome again! Ready to take the first step toward your dream space?
                                      </p>
  
                                      <h3 style="color:#1e88e5;">🏠 Step 1: Create Your Room Plan</h3>
                                      <p style="font-size: 16px; color: #555; line-height: 1.6;">
                                          With Seeb, it’s super simple:
                                          <br><br>
                                          📱 <strong>Scan your room</strong> using your phone (LiDAR supported), <br>
                                          OR<br>
                                          ✍️ <strong>Manually enter</strong> your room size (like 12x10 ft, height 9 ft)
                                      </p>
  
                                      <h4 style="color:#1e88e5;">⚙️ Why This Matters:</h4>
                                      <ul style="text-align: left; color: #555; font-size: 15px;">
                                          <li>Unlocks design tools tailored to your room</li>
                                          <li>Gives accurate 3D previews & more</li>
                                          <li>Helps our Skilled Team execute exactly what you see</li>
                                          <li>Connects to real material estimates & cost breakdowns</li>
                                      </ul>
  
                                      <a href="https://seeb.in/room-start" style="display: inline-block; margin-top: 20px; padding: 12px 24px; background-color: #1e88e5; color: #fff; text-decoration: none; border-radius: 5px;">
                                          🔗 Scan or Enter My Room
                                      </a>
  
                                      <p style="margin-top: 30px; font-size: 14px; color: #999;">
                                          Need help? Use “Request a Call” in the app — we’re ready to guide you.
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

      $emailContent = str_replace('{USERNAME}', $userName, $emailContent);

      $email->setMessage($emailContent);
      $email->setMailType('html');

      if ($email->send()) {
        $results[] = '✅ Email sent to ' . $toEmail;
      } else {
        $results[] = '❌ Failed to send to ' . $toEmail . '<br>' . print_r($email->printDebugger(['headers']), true);
      }
    }

    return $results;
  }

  // <p>You can view/download your invoice here:</p>
  //       <p><a href='$invoiceLink' style='padding:10px 15px; background:#007bff; color:#fff; text-decoration:none; border-radius:5px;'>View Invoice</a></p>

  public function sendBookingSuccessEmail($to, $userName, $bookingId, $id)
  {
    $email = \Config\Services::email();

    $invoiceLink = base_url("invoice/$id"); // 👈 adjust route as needed

    $subject = '🎉 Booking Confirmed - Seeb';
    $message = "
        <h2>Welcome to Seeb, $userName!</h2>
        <p>Your booking has been successfully confirmed. Below are the details:</p>
        <ul>
            <li><strong>Booking ID:</strong> $bookingId</li>
            <li><strong>Status:</strong> Confirmed ✅</li>
        </ul>
        <br>
        <p>Warm regards,<br>Team Seeb</p>
    ";

    $email->setTo($to);
    $email->setSubject($subject);
    $email->setMessage($message);

    if ($email->send()) {
      return '✅ Email sent successfully!';
    } else {
      return '❌ Email failed to send.<br>' . print_r($email->printDebugger(['headers', 'subject', 'body']), true);
    }
  }

  public function sendComparisonEmail()
  {
    $toEmail = "myselfnasaruddin@gmail.com";
    $userName = "Haseeb Khan";
    $email = \Config\Services::email();

    $email->setTo($toEmail);
    $email->setFrom('info@seeb.in', 'Seeb');
    $email->setSubject('Seeb vs Others – Not Just Furniture, We Deliver Full Interiors');

    $emailContent = '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Why Seeb Beats Traditional Players</title>
    </head>
    <body style="margin:0; padding:0; background-color:#f9f9f9; font-family: Arial, sans-serif;">
        <table width="100%" bgcolor="#f9f9f9" cellpadding="0" cellspacing="0">
            <tr>
                <td>
                    <table align="center" width="600" bgcolor="#ffffff" cellpadding="40" cellspacing="0" style="border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.05);">
                        <tr>
                            <td>
                                <img src="https://backend.seeb.in/public/logo.webp" alt="Seeb Logo" width="120" style="margin-bottom: 20px;">
                                <h2 style="color: #333;">Hi {USERNAME},</h2>
                                <p style="font-size: 16px; color: #555; line-height: 1.6;">
                                    When planning your home interiors, it’s important to compare how different platforms work — from design tools to execution support.
                                    <br><br>
                                  Before you decide, see why thousands are switching to Seeb – the only app that gives you full interior control, not just product catalogs.
                                </p>

                                <h3 style="color: #1e88e5;">🆚 Seeb vs Others – Real Interior Comparison</h3>

                                <table border="1" cellpadding="10" cellspacing="0" style="border-collapse: collapse; width:100%; font-size: 14px; color: #555;">
                                    <tr style="background-color: #f0f0f0;">
                                        <th>Feature</th>
                                        <th>Seeb (All-in-One)</th>
                                        <th>Livspace / HomeLane / DesignCafe / Godrej</th>
                                    </tr>
                                    <tr>
                                        <td>Full Home Interiors</td>
                                        <td>✅ Yes – End-to-end</td>
                                        <td>❌ Mostly furniture & modular setup</td>
                                    </tr>
                                    <tr>
                                        <td>Room-Wise Customization</td>
                                        <td>✅ Design wall-by-wall</td>
                                        <td>❌ Limited or pre-set designs</td>
                                    </tr>
                                    <tr>
                                        <td>AI-Powered Design App</td>
                                        <td>✅ Instant 3D + 2D + cost breakdown</td>
                                        <td>❌ No real design tool</td>
                                    </tr>
                                    <tr>
                                        <td>Single Service Booking</td>
                                        <td>✅ Book just a wardrobe, ceiling, etc.</td>
                                        <td>❌ Full package required</td>
                                    </tr>
                                    <tr>
                                        <td>Execution Support</td>
                                        <td>✅ Skilled Team + Factory Build</td>
                                        <td>❌ Vendors & showroom dependency</td>
                                    </tr>
                                    <tr>
                                        <td>Real-Time 3D Walkthroughs</td>
                                        <td>✅ Delivered via app</td>
                                        <td>❌ Not available</td>
                                    </tr>
                                    <tr>
                                        <td>Material Transparency</td>
                                        <td>✅ Full details (laminates, color codes)</td>
                                        <td>❌ Not revealed before work</td>
                                    </tr>
                                    <tr>
                                        <td>Warranty</td>
                                        <td>✅ 10-Year on all factory-made furniture</td>
                                        <td>✅ Varies</td>
                                    </tr>
                                    <tr>
                                        <td>Pricing</td>
                                        <td>✅ 40–60% more affordable</td>
                                        <td>❌ High fixed-cost packages</td>
                                    </tr>
                                </table>

                                <h4 style="margin-top:30px; color:#1e88e5;">🚫 What They Don’t Offer:</h4>
                                <ul style="font-size: 15px; color:#555; line-height: 1.6;">
                                    <li>No 100% online design system</li>
                                    <li>No way to design by yourself</li>
                                    <li>No single-task booking (e.g., just ceiling or one room)</li>
                                    <li>No live 3D view before execution</li>
                                    <li>No AI + Skilled Team combination</li>
                                </ul>

                                <h4 style="color:#1e88e5;">✅ What You Get with Seeb:</h4>
                                <ul style="font-size: 15px; color:#555; line-height: 1.6;">
                                    <li>Design room-by-room using AI</li>
                                    <li>Preview your home in 3D before starting</li>
                                    <li>Book only what you need</li>
                                    <li>Get execution support via Seeb’s well-skilled, trained team</li>
                                    <li>Direct factory-made furniture with 10-year warranty</li>
                                </ul>
                                <h4 style="color:#1e88e5;">How Seeb Compares to Livspace, HomeLane & Others – What You Should Know</h4>

                                <p style="font-size: 16px; color: #555; line-height: 1.6;">
                                   Brand names like Livspace, HomeLane, DesignCafe, and Godrej Interio are used purely for educational and comparison purposes.
                                   All rights belong to their respective owners. Seeb respects these companies and provides this information to help customers make informed decisions.
                                </p>
                                <a href="https://seeb.in/room-start" style="display: inline-block; margin-top: 20px; padding: 12px 24px; background-color: #1e88e5; color: #fff; text-decoration: none; border-radius: 5px;">
                                    🔗 Try Seeb Now
                                </a>

                                <p style="margin-top: 30px; font-size: 14px; color: #999;">
                                    Questions? We’re here to help at <a href="mailto:info@seeb.in">info@seeb.in</a>
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

    $emailContent = str_replace('{USERNAME}', $userName, $emailContent);

    $email->setMessage($emailContent);
    $email->setMailType('html');

    if ($email->send()) {
      return '✅ Comparison email sent to ' . $toEmail;
    } else {
      return '❌ Failed to send comparison email.<br>' . print_r($email->printDebugger(['headers']), true);
    }
  }

  public function sendFirstStepEmail()
  {
    $userModel = new CustomerModel();

    // Get tomorrow's date
    $tomorrow = date('Y-m-d', strtotime('+1 day'));

    // Fetch users created tomorrow (assuming `created_at` is DATE or DATETIME)
    $users = $userModel
      ->where("DATE(created_at)", $tomorrow)
      ->findAll();

    if (empty($users)) {
      return 'ℹ️ No users found with created_at = tomorrow (' . $tomorrow . ')';
    }

    // Prepare recipients array
    $recipients = [];

    foreach ($users as $user) {
      $recipients[] = [
        'email' => $user['email'],
        'name'  => $user['name'] ?? 'Customer'
      ];
    }

    // Call EmailController's function
    $result = $this->sendRoomStepEmailToMultiple($recipients);

    // Log results
    foreach ($result as $log) {
      echo $log . "<br>";
    }
  }
}
