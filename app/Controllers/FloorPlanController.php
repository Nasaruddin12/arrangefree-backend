<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\FloorPlanModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\ResponseInterface;

use Exception;

class FloorPlanController extends BaseController
{
    use ResponseTrait;

    protected $floorPlanModel;

    public function __construct()
    {
        $this->floorPlanModel = new FloorPlanModel();
    }

    public function index()
    {
        try {
            $userId = $this->request->getGet('user_id');
            $plans = $this->floorPlanModel->where('user_id', $userId)->findAll();

            return $this->respond([
                'status' => 200,
                'data'   => $plans,
            ]);
        } catch (Exception $e) {
            return $this->respond([
                'status'  => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id = null)
    {
        try {
            $plan = $this->floorPlanModel->find($id);
            if (!$plan) {
                return $this->respond([
                    'status'  => 404,
                    'message' => 'Floor plan not found',
                ], 404);
            }

            return $this->respond([
                'status' => 200,
                'data'   => $plan,
            ]);
        } catch (Exception $e) {
            return $this->respond([
                'status'  => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function create()
    {
        try {
            // Access file BEFORE validating
            $file = $this->request->getFile('file');

            // Dynamically inject file into request so validator sees it
            if ($file && $file->isValid()) {
                $_FILES['file'] = [
                    'name'     => $file->getName(),
                    'type'     => $file->getClientMimeType(),
                    'tmp_name' => $file->getTempName(),
                    'error'    => $file->getError(),
                    'size'     => $file->getSize(),
                ];
            }

            // Then validate
            $rules = [
                'user_id'     => 'required|integer',
                'room_name'   => 'required|string',
                'room_width'  => 'required|numeric',
                'room_height' => 'required|numeric',
                'room_length' => 'required|numeric',
                'canvas_json' => 'permit_empty|string',
                'file'        => 'uploaded[file]|max_size[file,2048]|ext_in[file,png,jpg,jpeg,svg,pdf]'
            ];

            if (!$this->validate($rules)) {
                return $this->respond([
                    'status'  => 422,
                    'message' => $this->validator->getErrors(),
                ], 422);
            }

            // Prepare input
            $data = $this->request->getPost();

            // Move file
            if ($file && $file->isValid() && !$file->hasMoved()) {

                $uploadPath = 'public/uploads/floorplans/';

                // Generate new random name and move file
                $newName = $file->getRandomName();
                $file->move($uploadPath, $newName);
                $data['file'] = $uploadPath . $newName;
    
            }

            $this->floorPlanModel->insert($data);

            return $this->respond([
                'status'  => 201,
                'message' => 'Floor plan created successfully',
                'data'    => $data,
            ], 201);
        } catch (Exception $e) {
            return $this->respond([
                'status'  => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function update($id = null)
    {
        try {
            $plan = $this->floorPlanModel->find($id);
            if (!$plan) {
                return $this->respond([
                    'status'  => 404,
                    'message' => 'Floor plan not found',
                ], 404);
            }

            if ($this->request->is('json')) {
                $data = $this->request->getJSON(true);
            } else {
                $data = $this->request->getPost();
            }
            $this->floorPlanModel->update($id, $data);

            return $this->respond([
                'status'  => 200,
                'message' => 'Floor plan updated',
            ]);
        } catch (Exception $e) {
            return $this->respond([
                'status'  => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function delete($id = null)
    {
        try {
            $plan = $this->floorPlanModel->find($id);
            if (!$plan) {
                return $this->respond([
                    'status'  => 404,
                    'message' => 'Floor plan not found',
                ], 404);
            }

            $this->floorPlanModel->delete($id);

            return $this->respond([
                'status'  => 200,
                'message' => 'Floor plan deleted',
            ]);
        } catch (Exception $e) {
            return $this->respond([
                'status'  => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
