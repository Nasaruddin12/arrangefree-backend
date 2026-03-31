<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ServiceProcessStepModel;
use App\Services\ImageProcessingService;
use CodeIgniter\API\ResponseTrait;
use Exception;

class ServiceProcessStepController extends BaseController
{
    use ResponseTrait;

    protected ServiceProcessStepModel $stepModel;
    protected ImageProcessingService $imageProcessingService;

    public function __construct()
    {
        $this->stepModel = new ServiceProcessStepModel();
        $this->imageProcessingService = new ImageProcessingService();
    }

    public function listByService($serviceId = null)
    {
        try {
            $serviceId = $serviceId ?? (int) $this->request->getVar('service_id');

            if (!$serviceId) {
                return $this->respond([
                    'status' => 400,
                    'message' => 'Service ID is required'
                ], 400);
            }

            $status = $this->request->getVar('status');
            $builder = $this->stepModel->where('service_id', $serviceId);

            if ($status) {
                $builder->where('status', $status);
            }

            $steps = $builder->orderBy('step_order', 'ASC')->findAll();

            return $this->respond([
                'status' => 200,
                'message' => 'Service process steps retrieved',
                'data' => $steps
            ]);
        } catch (Exception $e) {
            log_message('error', 'ServiceProcessStep list error: ' . $e->getMessage());
            return $this->failServerErrors();
        }
    }

    public function show($id)
    {
        try {
            $step = $this->stepModel->find($id);

            if (!$step) {
                return $this->respond([
                    'status' => 404,
                    'message' => 'Service process step not found'
                ], 404);
            }

            return $this->respond([
                'status' => 200,
                'message' => 'Service process step retrieved',
                'data' => $step
            ]);
        } catch (Exception $e) {
            log_message('error', 'ServiceProcessStep show error: ' . $e->getMessage());
            return $this->failServerErrors();
        }
    }

    public function create()
    {
        try {
            $data = $this->request->getJSON(true) ?? $this->request->getVar();

            $validation = \Config\Services::validation();
            $validation->setRules([
                'service_id' => 'required|integer',
                'step_title' => 'required|string|max_length[255]',
                'step_description' => 'permit_empty|string',
                'step_order' => 'permit_empty|integer',
                'estimated_time' => 'permit_empty|string|max_length[100]',
                'icon' => 'permit_empty|string|max_length[255]',
                'status' => 'permit_empty|in_list[active,inactive]',
            ]);

            if (!$validation->withRequest($this->request)->run()) {
                return $this->respond([
                    'status' => 400,
                    'message' => 'Validation failed',
                    'errors' => $validation->getErrors()
                ], 400);
            }

            if (empty($data['step_order'])) {
                $maxStep = $this->stepModel->select('MAX(step_order) as step_order')->where('service_id', $data['service_id'])->first();
                $data['step_order'] = ($maxStep['step_order'] ?? 0) + 1;
            }

            if (!$this->stepModel->insert($data)) {
                return $this->respond([
                    'status' => 400,
                    'message' => 'Failed to create service process step',
                    'errors' => $this->stepModel->errors()
                ], 400);
            }

            $created = $this->stepModel->find($this->stepModel->getInsertID());

            return $this->respondCreated([
                'status' => 201,
                'message' => 'Service process step created',
                'data' => $created
            ]);
        } catch (Exception $e) {
            log_message('error', 'ServiceProcessStep create error: ' . $e->getMessage());
            return $this->failServerErrors();
        }
    }

    public function uploadIcon()
    {

        try {
            $validation = \Config\Services::validation();
            $validation->setRules([
                'icon' => 'uploaded[icon]|is_image[icon]|mime_in[icon,image/png,image/jpg,image/jpeg,image/webp]|max_size[icon,2048]',
            ]);

            if (! $validation->withRequest($this->request)->run()) {
                return $this->respond([
                    'status' => 400,
                    'message' => 'Validation failed',
                    'errors' => $validation->getErrors(),
                ], 400);
            }

            $iconFile = $this->request->getFile('icon');
            if (! $iconFile || ! $iconFile->isValid()) {
                return $this->respond([
                    'status' => 400,
                    'message' => 'Invalid icon upload',
                ], 400);
            }

            $uploadDir = FCPATH . 'public/uploads/service-process-steps/';
            $webpName = $this->imageProcessingService->uploadAndConvertToWebp(
                $iconFile,
                $uploadDir,
                'step-icon-' . uniqid()
            );

            $iconPath = 'public/uploads/service-process-steps/' . $webpName;

            return $this->respondCreated([
                'status' => 201,
                'message' => 'Icon uploaded',
                'data' => ['icon' => $iconPath],
            ]);
        } catch (Exception $e) {
            log_message('error', 'ServiceProcessStep icon upload error: ' . $e->getMessage());
            return $this->failServerErrors();
        }
    }

    public function deleteIcon($id = null)
    {
        try {
            $step = null;
            if ($id !== null) {
                $step = $this->stepModel->find($id);
                if (! $step) {
                    return $this->respond([
                        'status' => 404,
                        'message' => 'Service process step not found',
                    ], 404);
                }
            }

            $payload = $this->request->getJSON(true) ?? [];
            $requestIcon = $this->request->getVar('icon') ?: ($payload['icon'] ?? null);
            $iconPath = $requestIcon ?? ($step['icon'] ?? null);

            if (empty($iconPath)) {
                return $this->respond([
                    'status' => 400,
                    'message' => 'Icon path is required to delete the file',
                ], 400);
            }

            $unlinkResult = $this->safeUnlinkIcon($iconPath);
            if ($unlinkResult === null) {
                log_message('warning', 'Attempted to delete icon outside permitted path: ' . $iconPath);
                return $this->respond([
                    'status' => 400,
                    'message' => 'Invalid icon path',
                ], 400);
            }

            if ($unlinkResult === false) {
                return $this->respond([
                    'status' => 500,
                    'message' => 'Failed to delete icon file',
                ], 500);
            }

            if ($step && ($step['icon'] ?? null) === $iconPath) {
                $this->stepModel->update($id, ['icon' => null]);
            }

            return $this->respond([
                'status' => 200,
                'message' => 'Icon deleted',
            ]);
        } catch (Exception $e) {
            log_message('error', 'ServiceProcessStep icon delete error: ' . $e->getMessage());
            return $this->failServerErrors();
        }
    }

    private function resolveIconFullPath(string $relativePath): ?string
    {
        $normalizedRelative = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, ltrim($relativePath, '/'));
        $fullPath = FCPATH . $normalizedRelative;
        $directory = dirname($fullPath);
        $realDirectory = realpath($directory);
        $realFcPath = realpath(FCPATH);

        if ($realDirectory === false || $realFcPath === false) {
            return null;
        }

        $prefix = rtrim($realFcPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (strpos($realDirectory, $prefix) !== 0) {
            return null;
        }

        return $fullPath;
    }

    private function safeUnlinkIcon(string $relativePath): ?bool
    {
        $fullPath = $this->resolveIconFullPath($relativePath);
        if ($fullPath === null) {
            return null;
        }

        if (! file_exists($fullPath)) {
            return true;
        }

        return @unlink($fullPath);
    }

    public function update($id)
    {
        try {
            $existing = $this->stepModel->find($id);

            if (!$existing) {
                return $this->respond([
                    'status' => 404,
                    'message' => 'Service process step not found'
                ], 404);
            }

            $data = $this->request->getJSON(true) ?? $this->request->getVar();

            $validation = \Config\Services::validation();
            $validation->setRules([
                'service_id' => 'permit_empty|integer',
                'step_title' => 'permit_empty|string|max_length[255]',
                'step_description' => 'permit_empty|string',
                'step_order' => 'permit_empty|integer',
                'estimated_time' => 'permit_empty|string|max_length[100]',
                'icon' => 'permit_empty|string|max_length[255]',
                'status' => 'permit_empty|in_list[active,inactive]',
            ]);

            if (!$validation->withRequest($this->request)->run()) {
                return $this->respond([
                    'status' => 400,
                    'message' => 'Validation failed',
                    'errors' => $validation->getErrors()
                ], 400);
            }

            if (empty($data)) {
                return $this->respond([
                    'status' => 400,
                    'message' => 'No data provided for update'
                ], 400);
            }

            if (!$this->stepModel->update($id, $data)) {
                return $this->respond([
                    'status' => 400,
                    'message' => 'Failed to update service process step',
                    'errors' => $this->stepModel->errors()
                ], 400);
            }

            $updated = $this->stepModel->find($id);

            return $this->respondUpdated([
                'status' => 200,
                'message' => 'Service process step updated',
                'data' => $updated
            ]);
        } catch (Exception $e) {
            log_message('error', 'ServiceProcessStep update error: ' . $e->getMessage());
            return $this->failServerErrors();
        }
    }

    public function delete($id)
    {
        try {
            $step = $this->stepModel->find($id);

            if (!$step) {
                return $this->respond([
                    'status' => 404,
                    'message' => 'Service process step not found'
                ], 404);
            }

            if (! empty($step['icon'])) {
                $unlinkResult = $this->safeUnlinkIcon($step['icon']);
                if ($unlinkResult === null) {
                    log_message('warning', 'Attempted to delete icon outside permitted path during step delete: ' . $step['icon']);
                }
            }

            if (!$this->stepModel->delete($id)) {
                return $this->respond([
                    'status' => 400,
                    'message' => 'Failed to delete service process step'
                ], 400);
            }

            return $this->respondDeleted([
                'status' => 200,
                'message' => 'Service process step deleted'
            ]);
        } catch (Exception $e) {
            log_message('error', 'ServiceProcessStep delete error: ' . $e->getMessage());
            return $this->failServerErrors();
        }
    }
}
