<?php

namespace App\Models;

use CodeIgniter\Model;

class PartnerReviewModel extends Model
{
    protected $table            = 'partner_reviews';
    protected $primaryKey       = 'id';
    protected $allowedFields    = [
        'booking_service_id',
        'partner_id',
        'rating',
        'review',
        'created_at',
        'updated_at'
    ];
    protected $useTimestamps    = true;
}
