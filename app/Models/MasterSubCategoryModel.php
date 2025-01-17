<?php

namespace App\Models;

use CodeIgniter\Model;

class MasterSubCategoryModel extends Model
{
    protected $table = 'master_subcategory';   // Name of the table
    protected $primaryKey = 'id';              // Primary key
    protected $allowedFields = ['master_category_id', 'title'];  // Fields that can be inserted/updated

    protected $useTimestamps = true;           // Use timestamps (created_at, updated_at)
    protected $returnType = 'array';           // Return data as an array
    protected $useSoftDeletes = false;         // Disable soft deletes if not needed

    // Validation rules (optional, add if needed)
    protected $validationRules = [
        'master_category_id' => 'required|is_natural_no_zero',
        'title' => 'required|max_length[255]',
    ];

    // Custom method to insert subcategory
    public function insertSubCategory($data)
    {
        return $this->insert($data);
    }

    // Custom method to get subcategory by ID
    public function getSubCategoryById($id)
    {
        return $this->where('id', $id)->first();
    }

    // Custom method to get subcategories by master category ID
    public function getSubCategoriesByCategory($categoryId)
    {
        return $this->where('master_category_id', $categoryId)->findAll();
    }
}
