<?php

namespace App\Controllers;

use App\Models\FaqCategoryModel;
use CodeIgniter\RESTful\ResourceController;
use Exception;

class FaqCategoryController extends ResourceController
{
    protected $faqCategoryModel;

    public function __construct()
    {
        $this->faqCategoryModel = new FaqCategoryModel();
    }

    // Get all categories
    public function index()
    {
        try {
            $categories = $this->faqCategoryModel->findAll();

            if (empty($categories)) {
                return $this->failNotFound('No FAQ categories found');
            }

            return $this->respond([
                'status' => 200,
                'data'   => $categories
            ]);
        } catch (Exception $e) {
            return $this->failServerError('Something went wrong: ' . $e->getMessage());
        }
    }

    // Get a single category
    public function show($id = null)
    {
        try {
            if (!$id) {
                return $this->failValidationErrors('Invalid category ID');
            }

            $category = $this->faqCategoryModel->find($id);

            if (!$category) {
                return $this->failNotFound('FAQ category not found');
            }

            return $this->respond([
                'status' => 200,
                'data'   => $category
            ]);
        } catch (Exception $e) {
            return $this->failServerError('Something went wrong: ' . $e->getMessage());
        }
    }

    // Create a new category
    public function create()
    {
        try {
            $data = $this->request->getJSON(true);

            if (!isset($data['name']) || empty(trim($data['name']))) {
                return $this->failValidationErrors('Category name is required');
            }

            if ($this->faqCategoryModel->insert($data)) {
                return $this->respondCreated([
                    'status'  => 201,
                    'message' => 'FAQ category created successfully'
                ]);
            }

            return $this->fail('Failed to create FAQ category');
        } catch (Exception $e) {
            return $this->failServerError('Something went wrong: ' . $e->getMessage());
        }
    }

    // Update a category
    public function update($id = null)
    {
        try {
            if (!$id) {
                return $this->failValidationErrors('Invalid category ID');
            }

            $data = $this->request->getJSON(true);

            if (!isset($data['name']) || empty(trim($data['name']))) {
                return $this->failValidationErrors('Category name is required');
            }

            if (!$this->faqCategoryModel->find($id)) {
                return $this->failNotFound('FAQ category not found');
            }

            if ($this->faqCategoryModel->update($id, $data)) {
                return $this->respond([
                    'status'  => 200,
                    'message' => 'FAQ category updated successfully'
                ]);
            }

            return $this->fail('Failed to update FAQ category');
        } catch (Exception $e) {
            return $this->failServerError('Something went wrong: ' . $e->getMessage());
        }
    }

    // Delete a category
    public function delete($id = null)
    {
        try {
            if (!$id) {
                return $this->failValidationErrors('Invalid category ID');
            }

            if (!$this->faqCategoryModel->find($id)) {
                return $this->failNotFound('FAQ category not found');
            }

            if ($this->faqCategoryModel->delete($id)) {
                return $this->respondDeleted([
                    'status'  => 200,       
                    'message' => 'FAQ category deleted successfully'
                ]);
            }

            return $this->fail('Failed to delete FAQ category');
        } catch (Exception $e) {
            return $this->failServerError('Something went wrong: ' . $e->getMessage());
        }
    }

    // List all categories with FAQ count
    public function listWithFaqCount()
    {
        try {
            $db = \Config\Database::connect();

            $categories = $db->table('faq_categories fc')
                ->select('fc.id, fc.name, fc.status, COUNT(f.id) AS faq_count')
                ->join('faqs f', 'f.category_id = fc.id AND f.service_id IS NULL AND f.status = 1', 'left')
                ->groupBy('fc.id')
                ->orderBy('fc.name', 'ASC')
                ->get()
                ->getResultArray();

            return $this->respond([
                'status' => 200,
                'message' => 'FAQ categories with FAQ count fetched successfully',
                'data' => $categories
            ]);
        } catch (Exception $e) {
            return $this->failServerError('Something went wrong: ' . $e->getMessage());
        }
    }
}
