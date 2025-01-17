<?php

namespace App\Models;

use CodeIgniter\Model;

class MasterCategoryModel extends Model
{
    protected $table = 'master_category';   // Name of the table
    protected $primaryKey = 'id';           // Primary key
    protected $allowedFields = ['title'];   // Fields that can be inserted/updated

    protected $useTimestamps = true;        // Use timestamps (created_at, updated_at)
    protected $returnType = 'array';        // Return data as an array
    protected $useSoftDeletes = false;      // Disable soft deletes if not needed

    // Validation rules (optional, add if needed)
    protected $validationRules = [
        'title' => 'required|max_length[255]',
    ];

    // Custom method to insert category
    public function insertCategory($data)
    {
        return $this->insert($data);
    }

    // Custom method to get category by ID
    public function getCategoryById($id)
    {
        return $this->where('id', $id)->first();
    }
}
