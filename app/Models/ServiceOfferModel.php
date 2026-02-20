<?php

namespace App\Models;

use CodeIgniter\Model;

class ServiceOfferModel extends Model
{
    protected $table = 'service_offers';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'service_id',
        'category_id',
        'title',
        'discount_type',
        'discount_value',
        'start_date',
        'end_date',
        'priority',
        'is_active'
    ];
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    public function getActiveOffer($serviceId, $categoryId = null)
    {
        return $this->select('service_offers.*')
            ->join('service_offer_targets', 'service_offer_targets.offer_id = service_offers.id')
            ->where('service_offers.is_active', 1)
            ->where('service_offers.discount_type', 'percentage')
            ->where('(service_offers.start_date IS NULL OR service_offers.start_date <= NOW())')
            ->where('(service_offers.end_date IS NULL OR service_offers.end_date >= NOW())')
            ->groupStart()
            ->where('service_offer_targets.service_id', $serviceId)
            ->orWhere('service_offer_targets.category_id', $categoryId)
            // ->orWhere('service_offer_targets.target_type', 'global')
            ->groupEnd()
            ->groupBy('service_offers.id')
            ->orderBy('service_offers.discount_value', 'DESC')
            ->orderBy('service_offers.id', 'DESC')
            ->first();
    }
    
    public function applyDiscount($price, $offer)
    {
        if (!$offer) {
            return $price;
        }

        if (($offer['discount_type'] ?? null) !== 'percentage') {
            return $price;
        }

        $price -= ($price * $offer['discount_value'] / 100);
        return max($price, 0);
    }
}
