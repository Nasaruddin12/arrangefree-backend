<?php

namespace App\Models;

use CodeIgniter\Model;

class FaqCategoryModel extends Model
{
    protected $table      = 'faq_categories';
    protected $primaryKey = 'id';

    protected $allowedFields = ['name', 'status'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
