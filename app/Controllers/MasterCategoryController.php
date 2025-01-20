<?php

namespace App\Controllers;

use App\Models\MasterCategoryModel;
use App\Models\MasterSubCategoryModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\Validation\Exceptions\ValidationException;

class MasterCategoryController extends ResourceController
{
    use ResponseTrait;

    protected $categoryModel;
    protected $subCategoryModel;

    public function __construct()
    {
        $this->categoryModel = new MasterCategoryModel();
        $this->subCategoryModel = new MasterSubCategoryModel();
    }

    // Get all categories (Read)
    public function index()
    {
        try {
            $categories = $this->categoryModel->findAll();
            $response = [
                'status' => 200,
                'data' => $categories,
            ];
        } catch (\Exception $e) {
            $response = [
                'status' => 500,
                'error' => $e->getMessage(),
            ];
        }

        return $this->respond($response, $response['status']);
    }

    // Get category by ID (Read)
    public function show($id = null)
    {
        try {
            $category = $this->categoryModel->find($id);
            if ($category) {
                $response = [
                    'status' => 200,
                    'data' => $category,
                ];
            } else {
                throw new \Exception('Category not found', 404);
            }
        } catch (\Exception $e) {
            $response = [
                'status' => $e->getCode(),
                'error' => $e->getMessage(),
            ];
        }

        return $this->respond($response, $response['status']);
    }

    // Create a new category (Create)
    public function create()
    {
        $categoryModel = new MasterCategoryModel();

        // Get the input data
        $data = [
            'title' => $this->request->getVar('title'),
        ];

        // Validate the input
        if (!$this->validate([
            'title' => 'required|string|max_length[255]',
        ])) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        // Attempt to insert the category
        $inserted = $categoryModel->insert($data);

        // Check if the insert was successful
        if ($inserted) {
            return $this->respond([
                'status' => 201,
                'message' => 'Category created successfully',
                'data' => $data
            ]);
        } else {
            return $this->failServerError('Failed to create category');
        }
    }


    // Update category (Update)
    public function update($id = null)
    {
        $categoryModel = new MasterCategoryModel();

        // Get the input data
        $data = [
            'title' => $this->request->getVar('title'),
        ];

        // Validate the input
        if (!$this->validate([
            'title' => 'required|string|max_length[255]',
        ])) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        // Attempt to update the category
        $updated = $categoryModel->update($id, $data);

        // Check if the update was successful
        if ($updated) {
            return $this->respond(['status' => 200, 'message' => 'Category updated successfully']);
        } else {
            return $this->failNotFound('Category not found');
        }
    }
    // Delete category (Delete)
    public function delete($id = null)
    {
        try {
            if ($this->categoryModel->delete($id)) {
                $response = [
                    'status' => 200,
                    'message' => 'Category deleted successfully.',
                ];
            } else {
                throw new \Exception('Category not found or failed to delete.', 404);
            }
        } catch (\Exception $e) {
            $response = [
                'status' => $e->getCode(),
                'error' => $e->getMessage(),
            ];
        }

        return $this->respond($response, $response['status']);
    }
    // CRUD for MasterSubCategory

    public function getSubCategories($categoryId = null)
    {
        try {
            // Check if category ID is provided
            if ($categoryId === null) {
                throw new \Exception('Category ID is required.', 400);
            }
    
            // Retrieve subcategories for the given category ID
            $subCategories = $this->subCategoryModel->where('master_category_id', $categoryId)->findAll();
    
            // Check if subcategories are found
            if ($subCategories) {
                return $this->respond([
                    'status' => 200,
                    'message' => 'Subcategories retrieved successfully',
                    'data' => $subCategories,
                ], 200);
            } else {
                throw new \Exception('Subcategories not found for the given category.', 404);
            }
        } catch (\Exception $e) {
            return $this->respond([
                'status' => $e->getCode(),
                'message' => $e->getMessage(),
            ], $e->getCode());
        }
    }
    
    public function createSubCategory()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules([
                'master_category_id' => 'required|integer',
                'title' => 'required|string|max_length[255]',
            ]);

            if (!$this->validate($validation->getRules())) {
                throw new ValidationException(implode(', ', $validation->getErrors()), 400);
            }

            $data = [
                'master_category_id' => $this->request->getVar('master_category_id'),
                'title' => $this->request->getVar('title'),
            ];

            if ($this->subCategoryModel->insert($data)) {
                $response = [
                    'status' => 201,
                    'data' => $data,
                    'message' => 'SubCategory created successfully.',
                ];
            } else {
                throw new \Exception('Failed to create subcategory.', 500);
            }
        } catch (ValidationException $e) {
            $response = [
                'status' => 400,
                'error' => $e->getMessage(),
            ];
        } catch (\Exception $e) {
            $response = [
                'status' => $e->getCode(),
                'error' => $e->getMessage(),
            ];
        }
        return $this->respond($response, $response['status']);
    }

    public function updateSubCategory($id = null)
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules([
                'master_category_id' => 'required|integer',
                'title' => 'required|string|max_length[255]',
            ]);

            if (!$this->validate($validation->getRules())) {
                throw new ValidationException(implode(', ', $validation->getErrors()), 400);
            }

            $data = [
                'master_category_id' => $this->request->getVar('master_category_id'),
                'title' => $this->request->getVar('title'),
            ];

            if ($this->subCategoryModel->update($id, $data)) {
                $response = [
                    'status' => 200,
                    'data' => $data,
                    'message' => 'SubCategory updated successfully.',
                ];
            } else {
                throw new \Exception('Failed to update subcategory or subcategory not found.', 404);
            }
        } catch (ValidationException $e) {
            $response = [
                'status' => 400,
                'error' => $e->getMessage(),
            ];
        } catch (\Exception $e) {
            $response = [
                'status' => $e->getCode(),
                'error' => $e->getMessage(),
            ];
        }
        return $this->respond($response, $response['status']);
    }

    public function deleteSubCategory($id = null)
    {
        try {
            if ($this->subCategoryModel->delete($id)) {
                $response = [
                    'status' => 200,
                    'message' => 'SubCategory deleted successfully.',
                ];
            } else {
                throw new \Exception('SubCategory not found or failed to delete.', 404);
            }
        } catch (\Exception $e) {
            $response = [
                'status' => $e->getCode(),
                'error' => $e->getMessage(),
            ];
        }
        return $this->respond($response, $response['status']);
    }
    public function getAllCategoriesWithSubCategories()
    {
        try {
            // Fetch all categories
            $categoryModel = new MasterCategoryModel();
            $categories = $categoryModel->findAll();

            // Fetch subcategories for each category
            $subcategoryModel = new MasterSubCategoryModel();
            foreach ($categories as &$category) {
                // Get the subcategories of the current category
                $category['subcategories'] = $subcategoryModel->where('master_category_id', $category['id'])->findAll();
            }

            if (!empty($categories)) {
                $response = [
                    'status' => 200,
                    'data' => $categories,
                ];
            } else {
                throw new \Exception('No categories found', 404);
            }
        } catch (\Exception $e) {
            $response = [
                'status' => $e->getCode(),
                'error' => $e->getMessage(),
            ];
        }

        return $this->respond($response, $response['status']);
    }
}
