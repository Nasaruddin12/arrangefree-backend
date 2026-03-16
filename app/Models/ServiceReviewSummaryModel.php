<?php

namespace App\Models;

use CodeIgniter\Model;

class ServiceReviewSummaryModel extends Model
{
    protected $table = 'service_review_summary';
    protected $primaryKey = 'service_id';

    protected $returnType = 'array';

    protected $allowedFields = [
        'service_id',
        'avg_rating',
        'total_reviews',
        'rating_1',
        'rating_2',
        'rating_3',
        'rating_4',
        'rating_5',
        'updated_at'
    ];

    public $useTimestamps = false;
    protected $validationRules = [
        'service_id'     => 'required|is_natural_no_zero',
        'avg_rating'     => 'permit_empty|decimal|greater_than_equal_to[0]|less_than_equal_to[5]',
        'total_reviews'  => 'permit_empty|is_natural',
        'rating_1'       => 'permit_empty|is_natural',
        'rating_2'       => 'permit_empty|is_natural',
        'rating_3'       => 'permit_empty|is_natural',
        'rating_4'       => 'permit_empty|is_natural',
        'rating_5'       => 'permit_empty|is_natural',
        'updated_at'     => 'permit_empty|valid_date[Y-m-d H:i:s]',
    ];
    protected $validationMessages = [
        'avg_rating' => [
            'less_than_equal_to' => 'Average rating must not be greater than 5.',
        ],
    ];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    public function getByServiceId(int $serviceId): ?array
    {
        $summary = $this->find($serviceId);

        return $summary ?: null;
    }

    public function saveSummary(int $serviceId, array $data): bool
    {
        $data['service_id'] = $serviceId;

        $existingSummary = $this->find($serviceId);

        if ($existingSummary !== null) {
            return $this->update($serviceId, $data);
        }

        return $this->insert($data) !== false;
    }
}
