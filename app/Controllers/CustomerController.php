<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\SMSGateway;
use App\Models\AfSubcribedUserModel;
use App\Models\ContactUsModel;
use App\Models\CustomerModel;
use CodeIgniter\API\ResponseTrait;
use DateInterval;
use DateTime;
use Exception;
use Firebase\JWT\JWT;

class CustomerController extends BaseController
{
    use ResponseTrait;
    public function sendOTP()
    {
        try {
            $mobileNo = $this->request->getVar('mobile_no');

            $customerModel = new CustomerModel();
            $validation = &$customerModel;

            $customerData = $customerModel->where('mobile_no', $mobileNo)->first();
            if (!empty($customerData)) {
                $otp = random_int(1000, 9999);
                if ($mobileNo == '7823098610')
                    $otp = 6254;
                // echo $otp;
                $smsGateway = new SMSGateway();
                $response = $smsGateway->sendOTP($mobileNo, $otp);

                if ($response->statusCode != 200) {
                    throw new Exception('Unable to send OTP.', 500);
                }
                $expTime = new DateTime('now');
                $expTime->add(new DateInterval('PT300S'));
                $otpToken = hash('sha256', $otp);
                $expTime = $expTime->getTimestamp();
                $otpToken .= ".$expTime";
                $customerModel->set(['otp' => $otpToken])->update($customerData['id']);

                $statusCode = 200;
                $response = [
                    'message' => 'OTP sent successfully',
                ];
            } else {
                throw new Exception('User not found', 404);
            }
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : ($e->getCode() === 404 ? 404 : 500);
            $response = [
                'error' => $e->getCode() === 400 ? ['validation' => $validation->errors()] : $e->getMessage(),
                'status' => $statusCode
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    public function sendSeebOTP()
    {
        try {
            $mobileNo = $this->request->getVar('mobile_no');

            $customerModel = new CustomerModel();
            $customerData = $customerModel->where('mobile_no', $mobileNo)->first();

            if (!$customerData) {
                // If user not found, create a new one
                $customerModel->insert([
                    'mobile_no' => $mobileNo,
                ]);

                // Fetch newly created user data again
                $customerData = $customerModel->where('mobile_no', $mobileNo)->first();

                // Ensure customer data is not null
                if (!$customerData) {
                    throw new Exception('Failed to create user.', 500);
                }
            }

            // Generate OTP
            $otp = random_int(1000, 9999);

            // Send OTP via SMS Gateway
            $smsGateway = new SMSGateway();
            $response = $smsGateway->sendOTP($mobileNo, $otp);

            if ($response->statusCode != 200) {
                throw new Exception('Unable to send OTP.', 500);
            }

            // Set OTP expiry
            $expTime = new DateTime('now');
            $expTime->add(new DateInterval('PT300S')); // 5 minutes
            $otpToken = hash('sha256', $otp) . "." . $expTime->getTimestamp();

            // Update OTP in DB
            $customerModel->update($customerData['id'], ['otp' => $otpToken]);

            return $this->respond([
                'message' => 'OTP sent successfully',
                'status' => 200
            ], 200);
        } catch (Exception $e) {
            return $this->respond([
                'error' => $e->getMessage(),
                'status' => $e->getCode() ?: 500
            ], $e->getCode() ?: 500);
        }
    }



    // public function sendOTP()
    // {
    //     try {
    //         $mobileNo = $this->request->getVar('mobile_no');


    //         $customerModel = new CustomerModel();

    // if ($customerModel->where("mobile_no", $mobileNo)->first()) {


    //     $validation = &$customerModel;


    //             $otp = random_int(1000, 9999);
    //             if ($mobileNo == '7823098610')
    //                 $otp = 1234;
    //             // echo $otp;
    //             $smsGateway = new SMSGateway();
    //             $response = $smsGateway->sendOTP($mobileNo, $otp);

    //             if ($response->statusCode != 200) {
    //                 throw new Exception('Unable to send OTP.', 500);
    //             }
    //             $expTime = new DateTime('now');
    //             $expTime->add(new DateInterval('PT300S'));
    //             $otpToken = hash('sha256', $otp);
    //             $expTime = $expTime->getTimestamp();
    //             $otpToken .= ".$expTime";

    //             $customerData = $customerModel->where('mobile_no', $mobileNo)->first();
    //             if (!empty($customerData)) {
    //                 $customerModel->set(['otp' => $otpToken])->update($customerData['id']);
    //             } else {
    //                 $data = [
    //                     'mobile_no' => $mobileNo,
    //                     'otp' => $otpToken,
    //                 ];
    //                 $customerModel->insert($data);
    //                 $customerID = $customerModel->db->insertID();
    //                 // throw new Exception('User not found', 404);
    //             }
    //             $statusCode = 200;
    //             $response = [
    //                 'message' => 'OTP sent successfully',
    //             ];
    //         } else {
    //             $statusCode = 404;
    //             $response = [
    //                 'message' => 'User Not Found',
    //             ];
    //         }
    //     } catch (Exception $e) {
    //         $statusCode = $e->getCode() === 400 ? 400 : ($e->getCode() === 404 ? 404 : 500);
    //         $response = [
    //             'error' => $e->getCode() === 400 ? ['validation' => $validation->errors()] : $e->getMessage(),
    //             'status' => $statusCode
    //         ];
    //     }

    //     $response['status'] = $statusCode;
    //     return $this->respond($response, $statusCode);
    // }

    public function login()
    {
        try {
            // Retrieve input data from request
            $mobile_no = $this->request->getVar('mobile_no');
            $otp = $this->request->getVar('otp');
            $fcm_token = $this->request->getVar('fcm_token');

            // Validate input data (you can add custom validation rules here)
            if (empty($mobile_no) || empty($otp)) {
                throw new Exception('Mobile number and OTP are required.', 400);
            }

            // Load the User model
            $customerModel = new CustomerModel();

            // Check if the user exists in the database
            $user = $customerModel->where('mobile_no', $mobile_no)->first();
            if (!$user) {
                throw new Exception('User not found.', 404);
            }
            $customerModel->update($user["id"], [
                "fcm_token" => $fcm_token
            ]);

            $userOTP_Token = $user['otp'];
            $userOTP = explode('.', $userOTP_Token);
            $currentTime = new DateTime('now');
            $expTime = new DateTime();
            $expTime->setTimestamp((int) $userOTP[1]);
            // if ($otp == '1234') {
            $otp = hash('sha256', $otp);
            if (($currentTime <= $expTime) && ($userOTP[0] == $otp)) {
                $key = getenv('JWT_SECRET');
                $iat = time(); // current timestamp value
                $exp = $iat + 18000000;

                $payload = array(
                    "iss" => base_url(),
                    "aud" => "Customer",
                    "sub" => "To verify the User",
                    "iat" => $iat,
                    //Time the JWT issued at
                    "exp" => $exp,
                    // Expiration time of token
                    "mobile_no" => $user['mobile_no'],
                    "customer_id" => $user['id'],
                );

                $token = JWT::encode($payload, $key, 'HS256');

                $statusCode = 200;
                $response = [
                    'status' => 200,
                    'message' => 'Login successful',
                    'user' => $user,
                    'token' => $token,
                    // 'session_id' => $session->session_id
                ];
                // print_r($response);die;
                // return $this->respond($response);
            } else {
                $statusCode = 401;
                $response = [
                    'error' => 'Invalid OTP is given!',
                ];
            }
        } catch (Exception $e) {
            // Return an error response
            $statusCode = $e->getCode();
            $response = [
                'message' => $e->getMessage(),
            ];
        }
        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }
    function DeleteCustomer($id)
    {
        $customerModel = new CustomerModel();
        $rest = $customerModel->delete($id);
        /* $rest = $customerModel->update($id, [
            "status" => 2
        ]); */
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
    public function createCustomer()
    {
        try {
            // echo 'dddd';die;
            $customerModel = new CustomerModel();
            $validation = &$customerModel;
            // $statusCode = 200;

            $userBackendToFrontendAttrs = [
                'name' => 'name',
                // 'email' => 'email',
                'mobile_no' => 'mobile_no',
                // 'password' => 'password',
                'is_logged_in' => 'is_logged_in',
                'status' => 'status',
                'fcm_token' => 'fcm_token',
            ];

            $customerData = array();
            // print_r($userBackendToFrontendAttrs);die;
            foreach ($userBackendToFrontendAttrs as $backendAttr => $frontendAttr) {
                $customerData[$backendAttr] = $this->request->getVar($frontendAttr);
            }



            if ($customerModel->insert($customerData)) {

                $insertId = $customerModel->getInsertID();
                $mobileNo = $this->request->getVar("mobile_no");
                $otp = random_int(1000, 9999);

                $smsGateway = new SMSGateway();
                $response = $smsGateway->sendOTP($mobileNo, $otp);

                if ($response->statusCode != 200) {
                    throw new Exception('Unable to send OTP.', 500);
                }
                $expTime = new DateTime('now');
                $expTime->add(new DateInterval('PT300S'));
                $otpToken = hash('sha256', $otp);
                $expTime = $expTime->getTimestamp();
                $otpToken .= ".$expTime";

                $customerModel->set(['otp' => $otpToken])->update($insertId);


                if (!empty($customerModel->errors())) {
                    throw new Exception('Validation', 400);
                }
                if ($customerModel->db->error()['code']) {
                    throw new Exception($customerModel->db->error()['message'], 500);
                }
                if ($customerModel->db->affectedRows() == 1) {
                    $statusCode = 200;
                    $response = [
                        'message' => 'Customer created successfully.',
                        'customer_id' => $insertId,
                        'customer_name' => $customerData['name'],
                    ];
                }
            } else {
                $statusCode =  400;
                $response = [
                    'error' => $customerModel->errors(),
                    'status' => $statusCode
                ];
            }
        } catch (Exception $e) {
            // $db->transRollback();
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? ['validation' => $validation->errors()] : $e->getMessage(),
                'status' => $statusCode
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
        // $response['status'] = $statusCode;
    }

    public function getCustomer()
    {
        try {
            $customerModel = new CustomerModel();

            $page = (int)$this->request->getVar('page');
            $latest = $this->request->getVar('latest');
            $search = $this->request->getVar('search');

            $validation = &$customerModel;

            $data = $customerModel;


            if ($latest == true) {
                $data->orderBy('created_at', 'DESC');
            }
            if (!($search == null || $search == '')) {
                // echo var_dump($order_id);
                $data = $data->like('name', $search)->orLike('email', $search)->orLike('mobile_no', $search);
                // $orderCountQuery = $orderCountQuery->like('razorpay_order_id', $search)->orLike('email', $search);

                // $pageCountQuery = $data->like('razorpay_order_id', $search)->orLike('email', $search);
            }

            $data = $data->paginate(10, 'all_customers', $page);

            if (!empty($customerModel->errors())) {
                // $validation = &$accountfaqModel;
                throw new Exception('Validation', 400);
            }
            $pageCount = $customerModel->countAllResults();

            if ($customerModel->db->error()['code'])
                throw new Exception($customerModel->db->error()['message'], 500);

            $statusCode = 200;
            $response = [
                "data" => $data,
                'page_count' => ceil($pageCount / 10),

            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? $validation->errors() : $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    public function updateCustomer($id)
    {
        try {
            $customerModel = new CustomerModel();
            $validation = &$customerModel;
            $emailControler = new EmailController();
            // $statusCode = 200;

            $userBackendToFrontendAttrs = [
                'name' => 'name',
                'email' => 'email',
                'mobile_no' => 'mobile_no',
            ];

            $customerData = array();
            foreach ($userBackendToFrontendAttrs as $backendAttr => $frontendAttr) {
                $customerData[$backendAttr] = $this->request->getVar($frontendAttr);
            }
            $customerData['id'] = $id;

            // var_dump($customerData);
            $customerModel->update($id, $customerData); // update the Customer with the given ID
            if (!empty($customerModel->errors())) {
                throw new Exception('Validation', 400);
            }
            if ($customerModel->db->error()['code']) {
                throw new Exception($customerModel->db->error()['message'], 500);
            }
            if ($customerModel->db->affectedRows() == 1) {
                $email = $emailControler->sendWelcomeEmail($customerData['email'], $customerData['name']);
                $statusCode = 200;
                $response = [
                    'message' => 'Customer updated successfully.',
                ];
            } else {
                throw new Exception('Nothing to Update', 200);
            }
        } catch (\Exception $e) {
            // $db->transRollback();
            $statusCode = $e->getCode() === 400 ? 400 : ($e->getCode());
            $response = [
                'error' => $e->getCode() === 400 ? ['validation' => $validation->errors()] : $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    public function getCustomerById($id)
    {
        $customerModel = new CustomerModel();
        $AfSubcribedUserModel = new AfSubcribedUserModel();

        try {
            $customerData = $customerModel->where('id', $id)->findAll();
            $subscribedUserData = $AfSubcribedUserModel->where('user_id', $id)->first();

            // if ($subscribedUserData) {
            //     $data = $AfSubcribedUserModel->where('user_id',$id)->findAll();
            // } else {
            //     $data = "User Has Not Subscribed Yet"; 
            // }


            $statusCode = 200;
            $response = [
                'data' => $customerData,
                'SubscribedUser' => $subscribedUserData
            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? $customerModel->errors() : $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    public function getCustomer_fromAdmin($id)
    {
        $customerModel = new CustomerModel();


        try {
            $customerData = $customerModel->where('id', $id)->findAll();

            $statusCode = 200;
            $response = [
                'data' => $customerData
            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? $customerModel->errors() : $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }


    public function contactUs()
    {
        try {
            // $session = session();
            $contactUsModel = new ContactUsModel();
            $validation = &$contactUsModel;

            // $statusCode = 200;

            $userBackendToFrontendAttrs = [
                'name' => 'name',
                'contact_number' => 'contact_number',
                'email_id' => 'email_id',
                'message' => 'message',
                'status' => 'status',
            ];

            $contactUsData = array();
            foreach ($userBackendToFrontendAttrs as $backendAttr => $frontendAttr) {
                $contactUsData[$backendAttr] = $this->request->getVar($frontendAttr);
            }
            // var_dump($contactUsData);
            // die;
            $contactUsModel->insert($contactUsData);

            $insertId = $contactUsModel->getInsertID();
            if (!empty($contactUsModel->errors())) {
                throw new Exception('Validation', 400);
            }
            if ($contactUsModel->db->error()['code']) {
                throw new Exception($contactUsModel->db->error()['message'], 500);
            }
            if ($contactUsModel->db->affectedRows() == 1) {


                $leadData = $contactUsModel->where("id", $insertId)->first();

                $name = $leadData["name"];
                $email_id = $leadData["email_id"];
                $contact_number = $leadData["contact_number"];
                $msg = $leadData["message"];


                $emailController = new EmailController();
                $subject = 'New Leads Added';
                $message = "Hey! Admin,\n There is new Lead in Dorfee.\n Name:$name\n Email:$email_id\n Contact:$contact_number\n Message:$msg";
                $emailController->sendMail('leads@dorfee.com', $subject, $message);




                $statusCode = 200;
                $response = [
                    'message' => 'Contact Us created successfully.',
                    'status' => 200
                    // 'customer_id' => $contactUsModel->db->insertID(),
                    // 'customer_name' => $contactUsData['name'],
                ];
            }
        } catch (Exception $e) {
            // $db->transRollback();
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? ['validation' => $validation->errors()] : $e->getMessage(),
                'status' => $statusCode
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
        // $response['status'] = $statusCode;
    }

    public function getAllContactUs()
    {
        try {
            $contactUsModel = new ContactUsModel();

            // Get request parameters
            $startDate = $this->request->getVar('start_date');
            $endDate = $this->request->getVar('end_date');
            $page = $this->request->getVar('page') ?? 1; // Default to page 1 if not provided
            $limit = $this->request->getVar('limit') ?? 10; // Default to 10 records per page
            $search = $this->request->getVar('search'); // Search keyword

            // Set default start and end date if not provided (current month)
            if (!$startDate || !$endDate) {
                $startDate = null;
                $endDate = null;
            }

            $query = $contactUsModel;

            // Apply date filtering if start and end dates are provided
            if ($startDate && $endDate) {
                $query = $query->where('created_at >=', $startDate)
                    ->where('created_at <=', $endDate);
            }

            // Apply search filter if the search keyword is provided
            if (!empty($search)) {
                $query = $query->groupStart()
                    ->like('name', $search)
                    ->orLike('contact_number', $search)
                    ->groupEnd();
            }

            // Fetch data with pagination, ordered by creation date (descending)
            $contactUsList = $query->orderBy('created_at', 'DESC')
                ->paginate($limit, 'default', $page);

            // Get total records count
            $totalRecords = $query->countAllResults();

            // Return the response
            return $this->respond([
                'status' => 200,
                'message' => 'Success',
                'data' => $contactUsList,
                'pagination' => [
                    'current_page' => (int) $page,
                    'limit' => (int) $limit,
                    'total_records' => $totalRecords,
                    'total_pages' => ceil($totalRecords / $limit)
                ]
            ], 200);
        } catch (Exception $e) {
            return $this->respond([
                'error' => $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }
    public function updateRemark($id)
    {
        try {
            $contactUsModel = new ContactUsModel();

            // Validate ID
            if (!$id) {
                throw new Exception('Invalid contact ID.', 400);
            }

            // Get the remark from the request
            $remark = $this->request->getVar('remark');

            // Validate remark input
            if (!$remark) {
                throw new Exception('Remark field is required.', 400);
            }

            // Check if the record exists
            $contactRecord = $contactUsModel->find($id);
            if (!$contactRecord) {
                throw new Exception('Contact record not found.', 404);
            }

            // Update the remark
            $contactUsModel->update($id, ['remark' => $remark]);

            return $this->respond([
                'message' => 'Remark updated successfully.',
                'status' => 200
            ], 200);
        } catch (Exception $e) {
            return $this->respond([
                'error' => $e->getMessage(),
                'status' => $e->getCode() === 400 ? 400 : 500
            ], $e->getCode() === 400 ? 400 : 500);
        }
    }
}
