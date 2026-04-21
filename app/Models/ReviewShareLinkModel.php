<?php

namespace App\Models;

use CodeIgniter\Model;

class ReviewShareLinkModel extends Model
{
    protected $table            = 'review_share_links';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $allowedFields = [
        'booking_id',
        'user_id',
        'token_hash',
        'code',
        'created_by_admin_id',
        'expires_at',
        'used_at',
        'revoked_at',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $cleanValidationRules = true;

    protected $validationRules = [
        'booking_id'           => 'required|is_natural_no_zero',
        'user_id'              => 'required|is_natural_no_zero',
        'token_hash'           => 'required|exact_length[64]',
        'code'                 => 'permit_empty|max_length[24]|alpha_numeric',
        'created_by_admin_id'  => 'permit_empty|is_natural_no_zero',
        'expires_at'           => 'permit_empty|valid_date[Y-m-d H:i:s]',
        'used_at'              => 'permit_empty|valid_date[Y-m-d H:i:s]',
        'revoked_at'           => 'permit_empty|valid_date[Y-m-d H:i:s]',
    ];
}
