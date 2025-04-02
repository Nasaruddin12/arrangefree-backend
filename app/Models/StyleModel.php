<?php

namespace App\Models;

use CodeIgniter\Model;

class StyleModel extends Model
{
    protected $table      = 'styles';  
    protected $primaryKey = 'id';      
    protected $allowedFields = ['name', 'created_at', 'updated_at'];  

    protected $useTimestamps = true; 
}
