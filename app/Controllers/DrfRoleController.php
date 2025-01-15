<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\DrfRoleModel;
use CodeIgniter\API\ResponseTrait;
use Exception;

class DrfRoleController extends BaseController
{
    use ResponseTrait;
    public function createRole()
    {
        try {
            $drfRoleModel = new DrfRoleModel();
            $roleData = [
                'name' => $this->request->getVar('name'),
                'description' => $this->request->getVar('description'),
            ];
            $validation = &$drfRoleModel;
            $drfRoleModel->insert($roleData);
            $roleID = $drfRoleModel->db->insertID();
            $statusCode = 200;
            $response = [
                'message' => 'Role created successfully',
                'role_id' => $roleID,
            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? $validation->getErrors() : $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    public function updateRole()
    {
        try {
            $drfRoleModel = new DrfRoleModel();
            $roleID = $this->request->getVar('role_id');
            $roleData = [
                'name' => $this->request->getVar('name'),
                'description' => $this->request->getVar('description'),
            ];
            $validation = &$drfRoleModel;
            $drfRoleModel->set($roleData)->update($roleID);

            $statusCode = 200;
            $response = [
                'message' => 'Role updated successfully',
            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? $validation->getErrors() : $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    // delete role
    // public function deleteRole()
    // {
    //     try {
    //         $drfRoleModel = new DrfRoleModel();
    //         $roleID = $this->request->getVar('role_id');
    //         // $adminModel = new AdminModel();
    //         $validation = &$adminModel;
    //         $isRoleAssigned = $adminModel->where('role_id', $roleID)->findAll();
    //         if (!empty($isRoleAssigned)) {
    //             throw new Exception('This role is in use!', 409);
    //         }
    //         $drfRoleModel->delete($roleID);

    //         $statusCode = 200;
    //         $response = [
    //             'message' => 'Role deleted successfull',
    //         ];
    //     } catch (Exception $e) {
    //         $statusCode = $e->getCode() === 400 ? 400 : 500;
    //         $response = [
    //             'error' => $e->getCode() === 400 ? $validation->getErrors() : $e->getMessage()
    //         ];
    //     }

    //     $response['status'] = $statusCode;
    //     return $this->respond($response, $statusCode);
    // }

    // get all roles
    public function getAllRoles()
    {
        try {
            $drfRoleModel = new DrfRoleModel();
            $validation = &$drfRoleModel;
            $rolesData = $drfRoleModel->findAll();

            $statusCode = 200;
            $response = [
                'data' => $rolesData,
            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? $validation->getErrors() : $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    // get single role
    public function getRole($id)
    {
        try {
            $drfRoleModel = new DrfRoleModel();
            $validation = &$drfRoleModel;
            $roleData = $drfRoleModel->find($id);

            $statusCode = 200;
            $response = [
                'data' => $roleData,
            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? $validation->getErrors() : $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }
}
