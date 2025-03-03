<?php

namespace App\Models;

use CodeIgniter\Model;

class CouponModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'af_coupons';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'coupon_category',
        'shop_keeper',
        'channel_partner',
        'area',
        'universal',
        'coupon_type',
        'coupon_type_name',
        'coupon_name',
        'description',
        'coupon_expiry',
        'cart_minimum_amount',
        'coupon_use_limit',
        'coupon_per_user_limit',
        'coupon_code',
        'terms_and_conditions',
                
    ];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];
}
