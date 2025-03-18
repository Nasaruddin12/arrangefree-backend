<?php

namespace App\Models;

use CodeIgniter\Model;

class SelectedDesignModel extends Model
{
    protected $table = 'selected_designs';
    protected $primaryKey = 'id';
    protected $allowedFields = ['user_id', 'image_path', 'text'];
    protected $useTimestamps = true;
}
