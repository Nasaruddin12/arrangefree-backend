<?php

namespace App\Controllers;

use App\Models\TeamModel;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\Exceptions\HttpException;

class TeamController extends ResourceController
{
    protected $modelName = 'App\Models\TeamModel';
    protected $format    = 'json';

    // Team Registration API
    public function register()
    {
        $validation = \Config\Services::validation();
        $teamModel  = new TeamModel();

        try {
            // Collect form + file data
            $data = [
                'name'             => $this->request->getPost('name'),
                'mobile'           => $this->request->getPost('mobile'),
                'age'              => $this->request->getPost('age'),
                'work'             => $this->request->getPost('work'),
                'labour_count'     => $this->request->getPost('labour_count'),
                'area'             => $this->request->getPost('area'),
                'service_areas'    => $this->request->getPost('service_areas'),
                'aadhaar_no'       => $this->request->getPost('aadhaar_no'),
                'pan_no'           => $this->request->getPost('pan_no')
            ];

            // Attach files if uploaded
            $aadhaarFrontFile  = $this->request->getFile('aadhaar_front');
            $aadhaarBackFile   = $this->request->getFile('aadhaar_back');
            $panFile           = $this->request->getFile('pan_file');
            $addressProofFile  = $this->request->getFile('address_proof');
            $photoFile         = $this->request->getFile('photo');

            // Validate file uploads
            if (
                !$aadhaarFrontFile->isValid() || !$aadhaarBackFile->isValid() ||
                !$panFile->isValid() || !$addressProofFile->isValid() || !$photoFile->isValid()
            ) {
                throw new HttpException(422, "All required files must be uploaded.");
            }

            // Save files to /public/uploads/team_documents/
            $uploadPath = 'public/uploads/team_documents/';

            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }

            // Helper function to upload and return file path
            function uploadFile($file, $uploadPath)
            {
                $newName = $file->getRandomName();
                if ($file->move($uploadPath, $newName)) {
                    return $uploadPath . $newName;
                }
                return null;
            }

            // Upload files and check
            $data['aadhaar_front']  = uploadFile($aadhaarFrontFile, $uploadPath);
            $data['aadhaar_back']   = uploadFile($aadhaarBackFile, $uploadPath);
            $data['pan_file']       = uploadFile($panFile, $uploadPath);
            $data['address_proof']  = uploadFile($addressProofFile, $uploadPath);
            $data['photo']          = uploadFile($photoFile, $uploadPath);

            if (in_array(null, [
                $data['aadhaar_front'],
                $data['aadhaar_back'],
                $data['pan_file'],
                $data['address_proof'],
                $data['photo']
            ])) {
                throw new HttpException(500, "File upload failed.");
            }

            // Validate data
            if (!$validation->setRules($teamModel->getValidationRules())->run($data)) {
                return $this->failValidationErrors($validation->getErrors());
            }

            // Save data
            if (!$teamModel->insert($data)) {
                throw new \RuntimeException('Failed to register team.');
            }

            // Success response
            return $this->respondCreated([
                'status'  => true,
                'message' => 'Team registered successfully!',
                'data'    => $data
            ]);
        } catch (HttpException $e) {
            // Validation or upload issue
            return $this->fail($e->getMessage(), $e->getCode());
        } catch (\Throwable $e) {
            // Unexpected errors
            return $this->failServerError($e->getMessage());
        }
    }

    // List All Teams
    public function list()
    {
        try {
            $teamModel = new TeamModel();
            $teams = $teamModel->findAll();

            return $this->respond([
                'status'  => true,
                'message' => 'Teams fetched successfully.',
                'data'    => $teams
            ]);
        } catch (\Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }
}
