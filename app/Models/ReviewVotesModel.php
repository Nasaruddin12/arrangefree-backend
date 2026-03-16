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
        'guest_token',
        'vote',
        'created_at'
    ];

    protected $useTimestamps = false;
    protected $validationRules = [
        'review_id'  => 'required|is_natural_no_zero',
        'user_id'    => 'permit_empty|is_natural_no_zero',
        'guest_token' => 'permit_empty|max_length[100]',
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

    public function getGuestVote(int $reviewId, string $guestToken): ?array
    {
        $vote = $this->where('review_id', $reviewId)
            ->where('guest_token', $guestToken)
            ->first();

        return $vote ?: null;
    }

    public function saveUserVote(array $data): bool
    {
        $reviewId = (int) ($data['review_id'] ?? 0);
        $userId = (int) ($data['user_id'] ?? 0);
        $guestToken = trim((string) ($data['guest_token'] ?? ''));

        if ($userId > 0) {
            $existingVote = $this->getUserVote($reviewId, $userId);
        } elseif ($guestToken !== '') {
            $existingVote = $this->getGuestVote($reviewId, $guestToken);
        } else {
            return false;
        }

        if ($existingVote !== null) {
            return $this->update($existingVote['id'], [
                'vote' => $data['vote'],
            ]);
        }

        return $this->insert($data) !== false;
    }
}
