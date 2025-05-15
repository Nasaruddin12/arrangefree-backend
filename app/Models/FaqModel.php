<?php

namespace App\Models;

use CodeIgniter\Model;

class FaqModel extends Model
{
    protected $table      = 'faqs';
    protected $primaryKey = 'id';
    protected $allowedFields = ['category_id', 'service_id', 'question', 'answer', 'status', 'created_at', 'updated_at'];
    protected $useTimestamps = true;

    public function getFaqsWithCategory()
    {
        return $this->select('faqs.*, faq_categories.name as category_name')
            ->join('faq_categories', 'faq_categories.id = faqs.category_id', 'left')
            ->where('faqs.service_id', null)
            ->findAll();
    }

    public function getFaqsByService($serviceId)
    {
        return $this->where('service_id', $serviceId)->where('category_id', null)->findAll();
    }

    public function getFaqsByCategory($categoryId)
    {
        return $this->where('category_id', $categoryId)->where('service_id', null)->findAll();
    }
}
