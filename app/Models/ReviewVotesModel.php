<?php

namespace App\Models;

use CodeIgniter\Model;

class ReviewVotesModel extends Model
{
    protected $table      = 'review_votes';
    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $allowedFields = [
        'review_id',
        'user_id',
        'vote',
        'created_at'
    ];

    protected $useTimestamps = false;
    protected $validationRules = [
        'review_id'  => 'required|is_natural_no_zero',
        'user_id'    => 'required|is_natural_no_zero',
        'vote'       => 'required|in_list[helpful,not_helpful]',
        'created_at' => 'permit_empty|valid_date[Y-m-d H:i:s]',
    ];
    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    public function getUserVote(int $reviewId, int $userId): ?array
    {
        $vote = $this->where('review_id', $reviewId)
            ->where('user_id', $userId)
            ->first();

        return $vote ?: null;
    }

    public function saveUserVote(array $data): bool
    {
        $existingVote = $this->getUserVote((int) $data['review_id'], (int) $data['user_id']);

        if ($existingVote !== null) {
            return $this->update($existingVote['id'], [
                'vote' => $data['vote'],
            ]);
        }

        return $this->insert($data) !== false;
    }
}
