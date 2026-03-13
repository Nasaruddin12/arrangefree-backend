<?php

namespace App\Models;

use CodeIgniter\Model;

class ReviewAspectsModel extends Model
{
    protected $table      = 'review_aspects';
    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $allowedFields = [
        'review_id',
        'aspect',
        'rating',
        'created_at'
    ];

    protected $useTimestamps = false;
    protected $validationRules = [
        'review_id'   => 'required|is_natural_no_zero',
        'aspect'      => 'required|string|max_length[100]',
        'rating'      => 'required|is_natural_no_zero|less_than_equal_to[5]',
        'created_at'  => 'permit_empty|valid_date[Y-m-d H:i:s]',
    ];
    protected $validationMessages = [
        'rating' => [
            'less_than_equal_to' => 'Aspect rating must not be greater than 5.',
        ],
    ];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    public function getByReviewId(int $reviewId): array
    {
        return $this->where('review_id', $reviewId)->findAll();
    }

    public function replaceReviewAspects(int $reviewId, array $aspects): bool
    {
        $this->where('review_id', $reviewId)->delete();

        foreach ($aspects as $aspect) {
            $aspect['review_id'] = $reviewId;

            if ($this->insert($aspect) === false) {
                return false;
            }
        }

        return true;
    }
}
