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

    public function index($userId = null)
    {
        try {

            $plans = $userId
                ? $this->floorPlanModel->where('user_id', $userId)->findAll()
                : $this->floorPlanModel->findAll();

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

    public function getAll()
    {
        try {
            $db = \Config\Database::connect();
            $builder = $db->table('floor_plans');
            $builder->select('floor_plans.*, af_customers.name AS user_name');
            $builder->join('af_customers', 'af_customers.id = floor_plans.user_id', 'left');

            // Parameters
            $page      = (int) ($this->request->getVar('page') ?? 1);
            $perPage   = (int) ($this->request->getVar('perPage') ?? 10);
            $search    = $this->request->getVar('search');
            $startDate = $this->request->getVar('start_date');
            $endDate   = $this->request->getVar('end_date');
            $offset    = max(0, ($page - 1) * $perPage);

            // Filters
            if (!empty($search)) {
                $builder->groupStart()
                    ->like('af_customers.name', $search)
                    ->orLike('floor_plans.room_name', $search)
                    ->groupEnd();
            }

            if (!empty($startDate) && !empty($endDate)) {
                $builder->where('floor_plans.created_at >=', date('Y-m-d', strtotime($startDate)));
                $builder->where('floor_plans.created_at <=', date('Y-m-d', strtotime($endDate)));
            }

            // Count total results before pagination
            $totalQuery = clone $builder;
            $totalRecords = $totalQuery->countAllResults(false); // Do not reset query

            // Pagination
            $builder->orderBy('floor_plans.created_at', 'DESC');
            $builder->limit($perPage, $offset);
            $data = $builder->get()->getResultArray();

            if (empty($data)) {
                return $this->failNotFound('No floor plans found.');
            }

            return $this->respond([
                'status'     => 200,
                'message'    => 'Floor plans retrieved successfully',
                'data'       => $data,
                'pagination' => [
                    'currentPage'   => $page,
                    'perPage'       => $perPage,
                    'totalPages'    => ceil($totalRecords / $perPage),
                    'totalRecords'  => $totalRecords,
                ]
            ]);
        } catch (\Exception $e) {
            return $this->failServerError('Unexpected error: ' . $e->getMessage());
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
                'user_id'         => 'required|integer',
                'room_name'       => 'required|string|max_length[255]',
                'room_size'       => 'required|string|max_length[100]',
                'name'            => 'permit_empty|string|max_length[255]', // ✅ added
                'primary_color'   => 'permit_empty|string|max_length[50]',
                'accent_color'    => 'permit_empty|string|max_length[50]',
                'style_name'      => 'permit_empty|string|max_length[255]',
                'floorplan_image' => 'required|string',
                'floor3d_image'   => 'permit_empty|string',
                'elements_json'   => 'required|string',
            ];

            if (!$this->validate($rules)) {
                return $this->respond([
                    'status'  => 422,
                    'message' => $this->validator->getErrors(),
                ], 422);
            }

            $data = $this->request->getVar();

            $this->floorPlanModel->insert($data);

            return $this->respondCreated([
                'status'  => 201,
                'message' => 'Floor plan created successfully',
                'data'    => $data,
            ]);
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

            $data = $this->request->getVar(); // ✅ Supports JSON, form-data, and x-www-form-urlencoded

            $this->floorPlanModel->update($id, $data);

            return $this->respond([
                'status'  => 200,
                'message' => 'Floor plan updated successfully',
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
    public function upload()
    {
        try {
            $file = $this->request->getFile('file');

            if (!$file || !$file->isValid()) {
                return $this->failValidationError('Invalid or missing file.');
            }

            $uploadPath = 'public/uploads/floorplans/';
            $newName = $file->getRandomName();
            $file->move(FCPATH . $uploadPath, $newName);

            // Return only the relative path
            $relativePath = $uploadPath . $newName;

            return $this->respond([
                'status'     => 200,
                'message'    => 'File uploaded successfully',
                'file_path'  => $relativePath
            ]);
        } catch (\Exception $e) {
            return $this->failServerError('Upload failed: ' . $e->getMessage());
        }
    }
}
