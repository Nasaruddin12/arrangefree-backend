<?php

namespace App\Models;

use CodeIgniter\Model;

class ServiceOfferTargetModel extends Model
{
    protected $table = 'service_offer_targets';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'offer_id',
        'target_type',
        'service_id',
        'category_id'
    ];
}
