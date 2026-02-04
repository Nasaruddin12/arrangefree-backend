<?php

namespace App\Models;

use CodeIgniter\Model;

class PromptModel extends Model
{
    protected $table = 'prompts';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $dateFormat = 'datetime';
    protected $allowedFields = [
        'style_id',
        'prompt',
        'image_path'
    ];

    protected $validationRules = [];
    protected $validationMessages = [];
    protected $skipValidation = false;

    protected $callbacks = [
        'beforeInsert' => [],
        'afterInsert' => [],
        'beforeUpdate' => [],
        'afterUpdate' => [],
        'beforeDelete' => [],
        'afterDelete' => [],
    ];

    // Optionally join with styles
    public function withStyle()
    {
        return $this->select('prompts.*, styles.name as style_name')
            ->join('styles', 'styles.id = prompts.style_id', 'left');
    }
}
