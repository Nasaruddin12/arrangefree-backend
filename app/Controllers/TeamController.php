<?php

namespace App\Controllers;

use App\Models\TeamModel;
use CodeIgniter\RESTful\ResourceController;

class TeamController extends ResourceController
{
    protected $modelName = 'App\Models\TeamModel';
    protected $format    = 'json';

    // Team Registration API
    public function register()
    {
        $validation = \Config\Services::validation();
        $teamModel  = new TeamModel();

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

        // Check if all files are uploaded
        if (!$aadhaarFrontFile->isValid() || !$aadhaarBackFile->isValid() || !$panFile->isValid() || !$addressProofFile->isValid() || !$photoFile->isValid()) {
            return $this->failValidationErrors("All required files must be uploaded.");
        }

        // Save files to /writable/uploads/
        $uploadPath = 'public/uploads/team_documents/';

        // Create folder if it doesn't exist
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

        // Function to upload file and return filename
        function uploadFile($file, $uploadPath)
        {
            $newName = $file->getRandomName();
            if ($file->move($uploadPath, $newName)) {
                return $uploadPath . $newName;
            }
            return null;
        }

        // Upload files one by one
        $data['aadhaar_front']  = uploadFile($aadhaarFrontFile, $uploadPath);
        $data['aadhaar_back']   = uploadFile($aadhaarBackFile, $uploadPath);
        $data['pan_file']       = uploadFile($panFile, $uploadPath);
        $data['address_proof']  = uploadFile($addressProofFile, $uploadPath);
        $data['photo']          = uploadFile($photoFile, $uploadPath);
        // If any upload fails, return error
        if (in_array(null, [
            $data['aadhaar_front'],
            $data['aadhaar_back'],
            $data['pan_file'],
            $data['address_proof'],
            $data['photo']
        ])) {
            return $this->failValidationErrors("File upload failed.");
        }

        // Validate data against model rules
        if (!$validation->setRules($teamModel->getValidationRules())
            ->run($data)) {
            return $this->failValidationErrors($validation->getErrors());
        }

        // Save data
        if ($teamModel->insert($data)) {
            return $this->respondCreated([
                'status'  => true,
                'message' => 'Team registered successfully!',
                'data'    => $data
            ]);
        } else {
            return $this->failServerError('Failed to register team.');
        }
    }

    // List All Teams (Optional API)
    public function list()
    {
        $teamModel = new TeamModel();
        $teams = $teamModel->findAll();

        return $this->respond([
            'status'  => true,
            'message' => 'Teams fetched successfully.',
            'data'    => $teams
        ]);
    }
}
