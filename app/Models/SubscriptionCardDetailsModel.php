<?php 
namespace App\Models;
use CodeIgniter\Model;
class SubscriptionCardDetailsModel extends Model
{
    protected $table = 'subscription_card_details';
    protected $primaryKey = 'id';
    
    protected $allowedFields = ['card_id', 'details','created_at','updated_at'];
}