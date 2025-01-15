<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\AdminModel;
use App\Models\PrivilegesModel;
use App\Models\RolePrivilegesModel;
use App\Models\SectionAccessModel;
use CodeIgniter\API\ResponseTrait;
use Exception;
use PhpParser\Node\Stmt\TryCatch;

class PrivilegesController extends BaseController
{
    use ResponseTrait;

    public function createSection()
    {
        try {
            $sectionAccessModel = new SectionAccessModel();
            $sectionAccessData = [
                'section_title' => $this->request->getVar('section_title'),
            ];
            $microtime = microtime();
            $accessKey = hash('sha256', $microtime);
            $sectionAccessData['access_key'] = $accessKey;
            $validation = &$sectionAccessModel;
            $sectionAccessModel->insert($sectionAccessData);
            $sectionID = $sectionAccessModel->db->insertID();

            $statusCode = 200;
            $response = [
                'message' => 'Section registered successfully',
                'section_id' => $sectionID,
                'access_key' => $accessKey,
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

    public function createRole()
    {
        try {
            $rolePrivilegesModel = new RolePrivilegesModel();
            $roleData = [
                'title' => $this->request->getVar('role_title'),
                'section_access' => $this->request->getVar('sections_id'),
            ];
            $validation = &$rolePrivilegesModel;
            $rolePrivilegesModel->insert($roleData);
            $roleID = $rolePrivilegesModel->db->insertID();

            // $sectionsID = $this->request->getVar('sections_id');
            // $sectionsData = json_decode(json_encode($sectionsData), true);
            /* $privilegesData = array();
            foreach ($sectionsData as $sectionID) {
                $privilegesData[] = [
                    'role_id' => $roleID,
                    'section_id' => $sectionID,
                ];
            } */

            /* $privilegesModel = new PrivilegesModel();
            $privilegesModel->insert($privilegesData); */

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

    // update role
    public function updateRole()
    {
        try {
            $rolePrivilegesModel = new RolePrivilegesModel();
            $roleID = $this->request->getVar('role_id');
            $roleData = [
                // 'title' => $this->request->getVar('role_title'),
                'section_access' => $this->request->getVar('sections_id'),
            ];
            $validation = &$rolePrivilegesModel;
            $rolePrivilegesModel->set($roleData)->update($roleID);

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
    public function deleteRole()
    {
        try {
            $rolePrivilegesModel = new RolePrivilegesModel();
            $roleID = $this->request->getVar('role_id');
            $adminModel = new AdminModel();
            $validation = &$adminModel;
            $isRoleAssigned = $adminModel->where('role_id', $roleID)->findAll();
            if (!empty($isRoleAssigned)) {
                throw new Exception('This role is in use!', 409);
            }
            $rolePrivilegesModel->delete($roleID);

            $statusCode = 200;
            $response = [
                'message' => 'Role deleted successfull',
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

    // get all roles
    public function getAllRoles()
    {
        try {
            $rolePrivilegesModel = new RolePrivilegesModel();
            $validation = &$rolePrivilegesModel;
            $rolesData = $rolePrivilegesModel->findAll();

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
            $rolePrivilegesModel = new RolePrivilegesModel();
            $validation = &$rolePrivilegesModel;
            $roleData = $rolePrivilegesModel->find($id);

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

    // get all pages section
    public function getAllSections()
    {
        try {
            $sectionAccessModel = new SectionAccessModel();
            $validation = &$sectionAccessModel;
            $sectionsData = $sectionAccessModel->select(['id', 'section_title'])->findAll();

            $statusCode = 200;
            $response = [
                'data' => $sectionsData,
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

    // get admin privileges
    public function getAdminPrivileges()
    {
        try {
            $adminID = $this->request->getVar('admin_id');
            $adminModel = new AdminModel();
            $adminData = $adminModel->find($adminID);
            $adminRoleID = $adminData['role_id'];
            $rolePrivilegesModel = new RolePrivilegesModel();
            $validation = &$rolePrivilegesModel;
            $roleData = $rolePrivilegesModel->find($adminRoleID);
            $sectionsID = json_decode($roleData['section_access'], true);

            $sectionAccessModel = new SectionAccessModel();
            $validation = &$sectionAccessModel;
            $privileges = $sectionAccessModel->whereIn('id', $sectionsID)->findColumn('access_key');

            $statusCode = 200;
            $response = [
                'data' => $privileges,
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
