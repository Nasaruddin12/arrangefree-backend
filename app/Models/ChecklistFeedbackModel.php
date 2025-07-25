<?php

namespace App\Models;

use CodeIgniter\Model;

class ChecklistFeedbackModel extends Model
{
    protected $table            = 'checklist_feedback';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'assignment_checklist_id',
        'question_id',
        'rating',
        'comment',
        'created_at',
        'updated_at'
    ];
}
