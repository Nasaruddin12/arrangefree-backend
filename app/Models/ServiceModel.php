<?php

namespace App\Models;

use CodeIgniter\Model;

class ServiceModel extends Model
{
    protected $table = 'services';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $useSoftDeletes = true; // Enable soft deletes
    protected $allowedFields = [
        'service_type_id',
        'name',
        'image',
        'rate',
        'rate_type',
        'description',
        'materials',
        'features',
        'care_instructions',
        'warranty_details',
        'quality_promise',
        'status',
        'primary_key',
        'secondary_key',
        'partner_price',
        'with_material',
        'slug'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    protected $validationRules = [
        'partner_price' => 'permit_empty|decimal',
    ];

    /**
     * Find services by numeric service_type_id and room_id (for backward compatibility with iOS/Android apps)
     */
    public function findByServiceTypeAndRoom($service_type_id, $room_id)
    {
        // Get all matching services by IDs
        $services = $this->select('services.*')
            ->join('service_rooms', 'service_rooms.service_id = services.id', 'inner')
            ->where('services.service_type_id', $service_type_id)
            ->where('service_rooms.room_id', $room_id)
            ->where('services.status', 1)
            ->findAll();

        // Attach addons for each service
        if (!empty($services)) {
            $addonModel = new \App\Models\ServiceAddonModel();

            foreach ($services as &$service) {
                $addons = $addonModel->where('service_id', $service['id'])->findAll();
                $service['addons'] = $addons;
            }
        }

        return $services;
    }

    /**
     * Find services by service_type_slug and room_slug (new slug-based endpoint)
     */
    // public function findByServiceTypeAndRoomSlug($service_type_slug, $room_slug)
    // {
    //     // Step 1: Get service_type_id from slug
    //     $db = \Config\Database::connect();
    //     $serviceType = $db->table('service_types')
    //         ->where('slug', $service_type_slug)
    //         ->get()
    //         ->getRow();

    //     if (!$serviceType) {
    //         return [];
    //     }

    //     // Step 2: Get room_id from slug
    //     $room = $db->table('rooms')
    //         ->where('slug', $room_slug)
    //         ->get()
    //         ->getRow();

    //     if (!$room) {
    //         return [];
    //     }

    //     // Step 3: Get all matching services
    //     $services = $this->select('services.*')
    //         ->join('service_rooms', 'service_rooms.service_id = services.id', 'inner')
    //         ->where('services.service_type_id', $serviceType->id)
    //         ->where('service_rooms.room_id', $room->id)
    //         ->where('services.status', 1)
    //         ->findAll();

    //     // Step 4: Attach addons for each service
    //     if (!empty($services)) {
    //         $addonModel = new \App\Models\ServiceAddonModel();

    //         foreach ($services as &$service) {
    //             $addons = $addonModel->where('service_id', $service['id'])->findAll();
    //             $service['addons'] = $addons;
    //         }
    //     }

    //     return $services;
    // } 
    public function findByServiceTypeAndRoomSlug($service_type_slug, $room_slug)
    {
        $db = \Config\Database::connect();
        $today = date('Y-m-d H:i:s');

        // 1️⃣ Get Service Type
        $serviceType = $db->table('service_types')
            ->where('slug', $service_type_slug)
            ->get()
            ->getRow();

        if (!$serviceType) {
            return [];
        }

        // 2️⃣ Get Room
        $room = $db->table('rooms')
            ->where('slug', $room_slug)
            ->get()
            ->getRow();

        if (!$room) {
            return [];
        }

        // 3️⃣ Get Services
        $services = $this->select('services.*')
            ->join('service_rooms', 'service_rooms.service_id = services.id', 'inner')
            ->where('services.service_type_id', $serviceType->id)
            ->where('service_rooms.room_id', $room->id)
            ->where('services.status', 1)
            ->findAll();

        if (empty($services)) {
            return [];
        }

        $addonModel = new \App\Models\ServiceAddonModel();
        $offerModel = new \App\Models\ServiceOfferModel();

        foreach ($services as &$service) {

            $originalPrice = (float) ($service['rate'] ?? $service['price'] ?? 0);

            // 4️⃣ Attach Addons
            $service['addons'] = $addonModel
                ->where('service_id', $service['id'])
                ->findAll();
            
            // 5️⃣ Get ALL applicable offers
            $offers = $offerModel
                ->select('service_offers.*')
                ->join('service_offer_targets', 'service_offer_targets.offer_id = service_offers.id')
                ->where('service_offers.is_active', 1)
                ->where('service_offers.start_date <=', $today)
                ->where('service_offers.end_date >=', $today)
                ->groupStart()
                ->where('service_offer_targets.service_id', $service['id'])
                ->orWhere('service_offer_targets.category_id', $service['service_type_id'])
                ->groupEnd()
                ->orderBy('service_offers.priority', 'DESC')
                ->orderBy('service_offers.id', 'DESC')
                ->findAll();

            $bestOffer = null;
            $bestDiscountedPrice = $originalPrice;

            // 6️⃣ Find Best Offer
            foreach ($offers as $offer) {

                $discounted = $originalPrice;

                if ($offer['discount_type'] === 'percentage') {
                    $discounted -= ($originalPrice * $offer['discount_value'] / 100);
                } else {
                    $discounted -= $offer['discount_value'];
                }

                if ($discounted < 0) {
                    $discounted = 0;
                }

                // Choose lowest final price
                if ($discounted < $bestDiscountedPrice) {
                    $bestDiscountedPrice = $discounted;
                    $bestOffer = $offer;
                }
            }

            // 7️⃣ Attach Result
            $service['original_price'] = $originalPrice;
            $service['discounted_rate'] = round($bestDiscountedPrice, 2);

            if ($bestOffer) {
                $service['offer'] = [
                    'id' => $bestOffer['id'],
                    'title' => $bestOffer['title'],
                    'discount_type' => $bestOffer['discount_type'],
                    'discount_value' => $bestOffer['discount_value'],
                    'priority' => $bestOffer['priority'],
                    'start_date' => $bestOffer['start_date'],
                    'end_date' => $bestOffer['end_date'],
                ];
            } else {
                $service['offer'] = null;
            }
        }

        return $services;
    }
}
