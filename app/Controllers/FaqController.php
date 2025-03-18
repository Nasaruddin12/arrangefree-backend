<?php

namespace App\Controllers;

use App\Models\FaqModel;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\Database\Exceptions\DataException;
use Exception;

class FaqController extends ResourceController
{
    protected $faqModel;

    public function __construct()
    {
        $this->faqModel = new FaqModel();
    }

    // Get all FAQs
    public function index()
    {
        try {
            $faqs = $this->faqModel->findAll();
            return $this->respond(['status' => 200, 'data' => $faqs]);
        } catch (Exception $e) {
            return $this->failServerError('Something went wrong: ' . $e->getMessage());
        }
    }

    // Get single FAQ
    public function show($id = null)
    {
        try {
            $faq = $this->faqModel->find($id);
            if (!$faq) {
                return $this->failNotFound('FAQ not found');
            }
            return $this->respond(['status' => 200, 'data' => $faq]);
        } catch (Exception $e) {
            return $this->failServerError('Something went wrong: ' . $e->getMessage());
        }
    }

    // Create FAQ
    public function create()
    {
        try {
            $data = $this->request->getJSON(true);

            // Validate data
            $validation = \Config\Services::validation();
            $validation->setRules([
                'category_id' => 'permit_empty|integer',
                'question'    => 'required|min_length[3]',
                'answer'      => 'required|min_length[3]',
                'status'      => 'required|in_list[0,1]'
            ]);

            if (!$validation->run($data)) {
                return $this->failValidationErrors($validation->getErrors());
            }

            if ($this->faqModel->insert($data)) {
                return $this->respondCreated(['status' => 201, 'message' => 'FAQ created successfully']);
            }
            return $this->fail('Failed to create FAQ');
        } catch (DataException $e) {
            return $this->failServerError('Database error: ' . $e->getMessage());
        } catch (Exception $e) {
            return $this->failServerError('Something went wrong: ' . $e->getMessage());
        }
    }

    // Update FAQ
    public function update($id = null)
    {
        try {
            $data = $this->request->getJSON(true);

            if (!$this->faqModel->find($id)) {
                return $this->failNotFound('FAQ not found');
            }

            // Validate data
            $validation = \Config\Services::validation();
            $validation->setRules([
                'category_id' => 'permit_empty|integer',
                'question'    => 'required|min_length[3]',
                'answer'      => 'required|min_length[3]',
                'status'      => 'required|in_list[0,1]'
            ]);

            if (!$validation->run($data)) {
                return $this->failValidationErrors($validation->getErrors());
            }

            if ($this->faqModel->update($id, $data)) {
                return $this->respond(['status' => 200, 'message' => 'FAQ updated successfully']);
            }
            return $this->fail('Failed to update FAQ');
        } catch (DataException $e) {
            return $this->failServerError('Database error: ' . $e->getMessage());
        } catch (Exception $e) {
            return $this->failServerError('Something went wrong: ' . $e->getMessage());
        }
    }

    // Delete FAQ
    public function delete($id = null)
    {
        try {
            if (!$this->faqModel->find($id)) {
                return $this->failNotFound('FAQ not found');
            }
            if ($this->faqModel->delete($id)) {
                return $this->respondDeleted(['status' => 200, 'message' => 'FAQ deleted successfully']);
            }
            return $this->fail('Failed to delete FAQ');
        } catch (DataException $e) {
            return $this->failServerError('Database error: ' . $e->getMessage());
        } catch (Exception $e) {
            return $this->failServerError('Something went wrong: ' . $e->getMessage());
        }
    }
    public function getFaqsByCategory($categoryId = null)
    {
        try {
            // Validate if category ID is provided
            if (!$categoryId) {
                return $this->failValidationErrors('Invalid category ID');
            }

            $faqs = $this->faqModel->where('category_id', $categoryId)->findAll();

            if (empty($faqs)) {
                return $this->failNotFound('No FAQs found for this category');
            }

            return $this->respond([
                'status' => 200,
                'data'   => $faqs
            ]);
        } catch (DataException $e) {
            return $this->failServerError('Database error: ' . $e->getMessage());
        } catch (Exception $e) {
            return $this->failServerError('Something went wrong: ' . $e->getMessage());
        }
    }
}
