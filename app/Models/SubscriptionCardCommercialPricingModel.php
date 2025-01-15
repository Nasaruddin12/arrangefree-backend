<?php

namespace App\Models;

use CodeIgniter\Model;

class SubscriptionCardCommercialPricingModel extends Model
{
    protected $table = 'subscription_card_commercial_pricing';
    protected $primaryKey = 'id';
    protected $allowedFields = ['card_id', 'sqft_start', 'sqft_end',    'price', 'created_at', 'updated_at'];
}
