<?php

namespace App\Models;

use CodeIgniter\Model;

class ReviewsModel extends Model
{
    protected $table            = 'reviews';
    protected $primaryKey       = 'id';

    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'review_type',
        'booking_id',
        'service_id',
        'partner_id',
        'user_id',
        'title',
        'rating',
        'review',
        'is_verified',
        'status',
        'created_at',
        'updated_at'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'review_type' => 'required|in_list[service,partner,booking]',
        'booking_id'  => 'required|is_natural_no_zero',
        'service_id'  => 'permit_empty|is_natural_no_zero',
        'partner_id'  => 'permit_empty|is_natural_no_zero',
        'user_id'     => 'required|is_natural_no_zero',
        'title'       => 'permit_empty|max_length[150]',
        'rating'      => 'required|decimal|greater_than_equal_to[1]|less_than_equal_to[5]',
        'review'      => 'permit_empty|string',
        'is_verified' => 'permit_empty|in_list[0,1]',
        'status'      => 'permit_empty|in_list[pending,approved,rejected]',
    ];

    protected $validationMessages = [
        'rating' => [
            'greater_than_equal_to' => 'Rating must be at least 1.',
            'less_than_equal_to'    => 'Rating must not be greater than 5.',
        ],
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    public function createReview(array $data): bool
    {
        return $this->insert($data) !== false;
    }

    public function getApprovedServiceReviews(int $serviceId): array
    {
        return $this->where('service_id', $serviceId)
            ->where('status', 'approved')
            ->orderBy('id', 'DESC')
            ->findAll();
    }

    public function getApprovedPartnerReviews(int $partnerId): array
    {
        return $this->where('partner_id', $partnerId)
            ->where('status', 'approved')
            ->orderBy('id', 'DESC')
            ->findAll();
    }

    public function hasDuplicateReview(array $data): bool
    {
        $builder = $this->where('booking_id', (int) ($data['booking_id'] ?? 0))
            ->where('user_id', (int) ($data['user_id'] ?? 0))
            ->where('review_type', (string) ($data['review_type'] ?? 'service'));

        if (!empty($data['service_id'])) {
            $builder->where('service_id', (int) $data['service_id']);
        }

        if (!empty($data['partner_id'])) {
            $builder->where('partner_id', (int) $data['partner_id']);
        }

        return $builder->countAllResults() > 0;
    }
}
