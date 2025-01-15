<?php

namespace App\Models;

use CodeIgniter\Model;

class SubscriptionCardsModel extends Model
{
    protected $table = 'subscription_cards';
    protected $primaryKey = 'id';
    protected $allowedFields = ['title', 'description', 'benefits', "is_deleted", 'created_at', 'updated_at'];
}
