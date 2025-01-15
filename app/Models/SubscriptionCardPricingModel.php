<?php

namespace App\Models;

use CodeIgniter\Model;

class SubscriptionCardPricingModel extends Model
{
    protected $table = 'subscription_card_pricing';
    protected $primaryKey = 'id';
    protected $allowedFields = ['card_id', 'price', 'size', 'created_at', 'updated_at'];
}
