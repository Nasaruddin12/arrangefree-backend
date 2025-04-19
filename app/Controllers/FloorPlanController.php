<?php

namespace App\Controllers\API;

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
            $rules = [
                'user_id'     => 'required|integer',
                'room_name'   => 'required|string',
                'room_width'  => 'required|numeric',
                'room_height' => 'required|numeric',
                'room_length' => 'required|numeric',
                'canvas_json' => 'permit_empty|string',
                'file'        => 'required|uploaded[file]|max_size[file,2048]|ext_in[file,png,jpg,jpeg,svg,pdf]'
            ];

            if (!$this->validate($rules)) {
                return $this->respond([
                    'status'  => 422,
                    'message' => $this->validator->getErrors(),
                ], 422);
            }

            $data = $this->request->getPost();

            $file = $this->request->getFile('file');
            if ($file && $file->isValid()) {
                $fileName = $file->getRandomName();
                $file->move(WRITEPATH . 'public/uploads/floorplans', $fileName);
                $data['file'] = 'public/uploads/floorplans/' . $fileName;
            }

            $this->floorPlanModel->insert($data);

            return $this->respond([
                'status'  => 201,
                'message' => 'Floor plan created successfully',
                'data'    => $data
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

            $data = $this->request->getRawInput();
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
