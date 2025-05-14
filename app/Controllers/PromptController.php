<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\PromptModel;
use CodeIgniter\HTTP\ResponseInterface;
use Exception;

class PromptController extends BaseController
{
    protected $promptModel;

    public function __construct()
    {
        $this->promptModel = new PromptModel();
    }

    public function index()
    {
        try {
            $data = $this->promptModel->withStyle()->findAll();

            return $this->response->setJSON([
                'status'  => 200,
                'message' => 'Prompt list retrieved successfully',
                'data'    => $data,
            ]);
        } catch (Exception $e) {
            return $this->response->setJSON([
                'status'  => 500,
                'message' => 'Failed to fetch prompts',
                'error'   => $e->getMessage(),
            ])->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id)
    {
        try {
            $prompt = $this->promptModel->withStyle()->find($id);

            if (!$prompt) {
                return $this->response->setJSON([
                    'status'  => 404,
                    'message' => "Prompt with ID $id not found.",
                ])->setStatusCode(ResponseInterface::HTTP_NOT_FOUND);
            }

            return $this->response->setJSON([
                'status'  => 200,
                'message' => 'Prompt fetched successfully',
                'data'    => $prompt,
            ]);
        } catch (Exception $e) {
            return $this->response->setJSON([
                'status'  => 500,
                'message' => 'Error fetching prompt',
                'error'   => $e->getMessage(),
            ])->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function create()
    {
        try {
            $rules = [
                'style_id' => 'required|integer|is_not_unique[styles.id]',
                'prompt'   => 'required|string',
                'image'    => 'if_exist|uploaded[image]|is_image[image]|mime_in[image,image/jpeg,image/png,image/webp]|max_size[image,2048]',
            ];

            if (!$this->validate($rules)) {
                return $this->response->setJSON([
                    'status' => 422,
                    'message' => 'Validation failed',
                    'errors' => $this->validator->getErrors(),
                ])->setStatusCode(ResponseInterface::HTTP_UNPROCESSABLE_ENTITY);
            }

            $imagePath = null;
            $image = $this->request->getFile('image');

            if ($image && $image->isValid() && !$image->hasMoved()) {
                $imageName = $image->getRandomName();
                $image->move(FCPATH . 'uploads/prompts/', $imageName);
                $imagePath = 'uploads/prompts/' . $imageName;
            }

            $data = [
                'style_id'   => $this->request->getPost('style_id'),
                'prompt'     => $this->request->getPost('prompt'),
                'image_path' => $imagePath,
            ];

            $this->promptModel->save($data);

            return $this->response->setJSON([
                'status'  => 201,
                'message' => 'Prompt created successfully',
                'data'    => $data,
            ]);
        } catch (Exception $e) {
            return $this->response->setJSON([
                'status'  => 500,
                'message' => 'Error creating prompt',
                'error'   => $e->getMessage(),
            ])->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update($id)
    {
        try {
            $prompt = $this->promptModel->find($id);

            if (!$prompt) {
                return $this->response->setJSON([
                    'status'  => 404,
                    'message' => "Prompt with ID $id not found.",
                ])->setStatusCode(ResponseInterface::HTTP_NOT_FOUND);
            }

            $rules = [
                'style_id' => 'required|integer|is_not_unique[styles.id]',
                'prompt'   => 'required|string',
                'image'    => 'if_exist|uploaded[image]|is_image[image]|mime_in[image,image/jpeg,image/png,image/webp]|max_size[image,2048]',
            ];

            if (!$this->validate($rules)) {
                return $this->response->setJSON([
                    'status' => 422,
                    'message' => 'Validation failed',
                    'errors' => $this->validator->getErrors(),
                ])->setStatusCode(ResponseInterface::HTTP_UNPROCESSABLE_ENTITY);
            }

            $imagePath = $prompt['image_path'];
            $image = $this->request->getFile('image');

            if ($image && $image->isValid() && !$image->hasMoved()) {

                $imageName = $image->getRandomName();
                $image->move('public/uploads/prompts/', $imageName);
                $imagePath = 'public/uploads/prompts/' . $imageName;

                if ($prompt['image_path'] && file_exists($prompt['image_path'])) {
                    unlink(FCPATH . $prompt['image_path']);
                }
            }

            $data = [
                'id'         => $id,
                'style_id'   => $this->request->getPost('style_id'),
                'prompt'     => $this->request->getPost('prompt'),
                'image_path' => $imagePath,
            ];

            $this->promptModel->save($data);

            return $this->response->setJSON([
                'status'  => 200,
                'message' => 'Prompt updated successfully',
                'data'    => $data,
            ]);
        } catch (Exception $e) {
            return $this->response->setJSON([
                'status'  => 500,
                'message' => 'Error updating prompt',
                'error'   => $e->getMessage(),
            ])->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getByStyle($styleId)
    {
        try {
            $prompts = $this->promptModel
                ->withStyle()
                ->where('style_id', $styleId)
                ->findAll();

            return $this->response->setJSON([
                'status'  => 200,
                'message' => 'Prompts by style fetched successfully',
                'data'    => $prompts,
            ]);
        } catch (Exception $e) {
            return $this->response->setJSON([
                'status'  => 500,
                'message' => 'Failed to fetch prompts by style',
                'error'   => $e->getMessage(),
            ])->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function delete($id)
    {
        try {
            $prompt = $this->promptModel->find($id);

            if (!$prompt) {
                return $this->response->setJSON([
                    'status'  => 404,
                    'message' => "Prompt with ID $id not found.",
                ])->setStatusCode(ResponseInterface::HTTP_NOT_FOUND);
            }

            // Delete image file if exists
            if (!empty($prompt['image_path']) && file_exists(FCPATH . $prompt['image_path'])) {
                unlink(FCPATH . $prompt['image_path']);
            }

            $this->promptModel->delete($id);

            return $this->response->setJSON([
                'status'  => 200,
                'message' => "Prompt with ID $id deleted successfully.",
            ]);
        } catch (Exception $e) {
            return $this->response->setJSON([
                'status'  => 500,
                'message' => 'Error deleting prompt',
                'error'   => $e->getMessage(),
            ])->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
