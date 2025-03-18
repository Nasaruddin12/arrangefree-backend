<?php

namespace App\Models;

use CodeIgniter\Model;

class FaqCategoryModel extends Model
{
    protected $table      = 'faq_categories';
    protected $primaryKey = 'id';

    protected $allowedFields = ['name', 'created_at', 'updated_at'];
    protected $useTimestamps = true;
}
