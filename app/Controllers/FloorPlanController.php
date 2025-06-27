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

            // Parameters
            $page      = (int) ($this->request->getVar('page') ?? 1);
            $perPage   = (int) ($this->request->getVar('perPage') ?? 10);
            $search    = $this->request->getVar('search');
            $startDate = $this->request->getVar('start_date');
            $endDate   = $this->request->getVar('end_date');
            $offset    = max(0, ($page - 1) * $perPage);

            // Base builder for subquery
            $builder = $db->table('floor_plans')
                ->select('user_id, MAX(created_at) as latest_created_at, COUNT(id) as floor_plan_count')
                ->groupBy('user_id');

            if (!empty($startDate) && !empty($endDate)) {
                $builder->where('created_at >=', date('Y-m-d', strtotime($startDate)))
                    ->where('created_at <=', date('Y-m-d', strtotime($endDate)));
            }

            $subQuery = $builder->getCompiledSelect();

            // Final builder joining subquery + customer names
            $finalBuilder = $db->table("($subQuery) as summary")
                ->select('summary.user_id, af_customers.name as user_name, af_customers.mobile_no as user_mobile, summary.floor_plan_count, summary.latest_created_at')
                ->join('af_customers', 'af_customers.id = summary.user_id', 'left');

            if (!empty($search)) {
                $finalBuilder->like('af_customers.name', $search);
            }

            // Clone for total count
            $totalQuery = clone $finalBuilder;
            $totalRecords = count($totalQuery->get()->getResultArray());

            // Pagination + order
            $finalBuilder->orderBy('summary.latest_created_at', 'DESC');
            $finalBuilder->limit($perPage, $offset);
            $data = $finalBuilder->get()->getResultArray();

            if (empty($data)) {
                return $this->failNotFound('No floor plans found.');
            }

            return $this->respond([
                'status'     => 200,
                'message'    => 'User floor plan summary retrieved successfully',
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
