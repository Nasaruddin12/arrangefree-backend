<?php

namespace App\Models;

use CodeIgniter\Model;

class PromptModel extends Model
{
    protected $table      = 'prompts';
    protected $primaryKey = 'id';

    protected $useTimestamps = true;

    protected $allowedFields = [
        'style_id',
        'prompt',
        'image_path',
        'created_at',
        'updated_at',
    ];

    protected $returnType = 'array';

    // Optionally join with styles
    public function withStyle()
    {
        return $this->select('prompts.*, styles.name as style_name')
            ->join('styles', 'styles.id = prompts.style_id', 'left');
    }
}
