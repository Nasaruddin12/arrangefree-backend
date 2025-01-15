<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UsersModel;
use CodeIgniter\API\ResponseTrait;
use Exception;

class UsersController extends BaseController
{
    use ResponseTrait;

    public function login()
    {
        try {
            // Get the email and password from the request
            $user_id = $this->request->getVar('user_id');
            $password = $this->request->getVar('password');

            if (empty($user_id) || empty($password)) {
                throw new Exception('username and password are required.', 400);
            }
            // Load the UserModel
            $userModel = new UsersModel();

            // Check if the user exists with the provided user_id
            $user = $userModel->select([
                'drf_users.id AS id',
                'drf_users.user_id AS user_name',
                'drf_users.name AS name',
                'drf_users.password AS password',
                'drf_users.role_id AS role_id',
                'drf_roles.name AS role_name',    
            ])
                ->join('drf_roles', 'drf_users.role_id = drf_roles.id')->where('drf_users.user_id', $user_id)->first();

            // If user not found or password mismatch, throw an exception
            if ($user) {
                $pass = $user['password'];
                // $authenticatePassword = password_verify($password, $pass);
                $encrypted_pass=hash('sha256',$password);
                if ($encrypted_pass==$pass) {
                    $statusCode = 200;
                    $response = [
                        'status' => $statusCode,
                        'message' => 'Login successful.',
                        'data' => $user,
                    ];
                }else{
                    $response = [
                        'status' => 401,
                        'message' => 'Incorrect credentials',
                    ];
                }
                return $this->respond($response,$response['status']);
            }
            if (!$user) {
                throw new Exception('User not found.', 404);
            }

            unset($user['password']);
            
     

        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'status' => $e->getCode(),
                'message' => $e->getMessage(),
            ];

        }

        return $this->respond($response, $e->getCode());
    }


    public function createUser()
    {
        try {
            $userModel = new UsersModel();
            $validation = \Config\Services::validation();
            $statusCode = 200;
            
            $userData = [
                'user_id' => $this->request->getVar('user_id'),
                'name' => $this->request->getVar('name'),
                'password' => $this->request->getVar('password'),
                'role_id' => $this->request->getVar('role_id'),
            ];
            $encrypted_password = hash('sha256', $userData['password']);
            $userData['password'] = $encrypted_password;
            // print_r($userData);die;
            $userModel->insert($userData);

            if (!empty($userModel->errors())) {
                throw new Exception('Validation', );
            }

            if ($userModel->db->error()['code']) {
                throw new Exception($userModel->db->error()['message']);
            }

            $response = [
                'message' => 'User created successfully.',
                'user_id' => $userModel->db->insertID(),
            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getMessage()
            ];
        }

        return $this->respond($response, $statusCode);
    }

    public function getUsers($id)
    {
        $userModel = new UsersModel();
        try {
            $data = $userModel->where('id', $id)->findAll();

            if (!empty($userModel->errors())) {
                throw new Exception('Validation', );
            }

            if ($userModel->db->error()['code']) {
                throw new Exception($userModel->db->error()['message']);
            }

            $statusCode = 200;
            $response = [
                'data' => $data
            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getMessage()
            ];
        }

        return $this->respond($response, $statusCode);
    }

}
