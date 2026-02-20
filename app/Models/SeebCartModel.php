<?php

namespace App\Models;

use CodeIgniter\Model;

class SeebCartModel extends Model
{
    protected $table            = 'seeb_cart';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $allowedFields    = [
        'user_id',
        'parent_cart_id',
        'service_id',
        'addon_id',
        'service_type_id',
        'room_id',
        'quantity',
        'unit',
        'base_rate',
        'selling_rate',
        'offer_id',
        'offer_discount',
        'final_amount',
        'initial_offer_id',
        'initial_selling_rate',
        'initial_final_amount',
        'room_length',
        'room_width',
        'description',
        'reference_image'
    ];

    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
    protected $dateFormat       = 'datetime';

    protected $validationRules = [];
    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];
    private $hasInitialSnapshotColumns = null;

    private function supportsInitialSnapshotColumns(): bool
    {
        if ($this->hasInitialSnapshotColumns !== null) {
            return $this->hasInitialSnapshotColumns;
        }

        $fields = $this->db->getFieldNames($this->table);
        $this->hasInitialSnapshotColumns =
            in_array('initial_offer_id', $fields, true) &&
            in_array('initial_selling_rate', $fields, true) &&
            in_array('initial_final_amount', $fields, true);

        return $this->hasInitialSnapshotColumns;
    }

    public function getCartsWithRoom()
    {
        return $this->select('seeb_cart.*, rooms.name')
            ->join('rooms', 'rooms.id = seeb_cart.room_id', 'left')
            ->findAll();
    }

    /**
     * Get cart items grouped by service with add-ons nested inside.
     * During fetch, sync cart pricing fields with latest active offer.
     */
    public function getCartItemsHierarchical($user_id = null)
    {
        $offerModel = new \App\Models\ServiceOfferModel();
        $round2 = static fn($value) => round((float) $value, 2);

        $query = $this->select('
            seeb_cart.*,
            services.id as service_id,
            services.image as service_image,
            services.name as service_name,
            services.rate as service_base_rate,
            services.service_type_id as service_type_id,
            rooms.name as room_name,
            service_addons.id as addon_id,
            service_addons.name as addon_name,
            service_addons.price as addon_price
        ')
            ->join('services', 'services.id = seeb_cart.service_id', 'left')
            ->join('rooms', 'rooms.id = seeb_cart.room_id', 'left')
            ->join('service_addons', 'service_addons.id = seeb_cart.addon_id', 'left')
            ->orderBy('seeb_cart.parent_cart_id', 'ASC')
            ->orderBy('seeb_cart.created_at', 'DESC');

        if (!empty($user_id)) {
            $query->where('seeb_cart.user_id', $user_id);
        }

        $cartItems = $query->findAll();
        $grouped = [];

        foreach ($cartItems as $item) {
            if (empty($item['parent_cart_id'])) {
                $baseRate = floatval($item['service_base_rate'] ?? $item['base_rate'] ?? 0);
                $quantity = floatval($item['quantity'] ?? 1);

                $offer = $offerModel->getActiveOffer(
                    $item['service_id'],
                    $item['service_type_id']
                );

                $sellingRate = $round2($offerModel->applyDiscount($baseRate, $offer));
                $offerDiscountPerUnit = $round2(max($baseRate - $sellingRate, 0));
                $offerDiscount = $round2($offerDiscountPerUnit * $quantity);
                $finalAmount = $round2($sellingRate * $quantity);
                $newOfferId = isset($offer['id']) ? (int) $offer['id'] : null;
                $this->update($item['id'], [
                    'base_rate' => $round2($baseRate),
                    'selling_rate' => $sellingRate,
                    'offer_id' => $newOfferId,
                    'offer_discount' => $offerDiscount,
                    'final_amount' => $finalAmount,
                ]);

                $serviceKey = $item['id'];

                $grouped[$serviceKey] = [
                    'id' => $item['id'],
                    'user_id' => $item['user_id'],
                    'service_id' => $item['service_id'],
                    // 'service_type_id' => $item['service_type_id'],
                    'service_name' => $item['service_name'],
                    'service_image' => $item['service_image'],
                    'room_id' => $item['room_id'],
                    'room_name' => $item['room_name'],
                    'quantity' => $quantity,
                    'unit' => $item['unit'],
                    'base_rate' => $baseRate,
                    'selling_rate' => $sellingRate,
                    'offer_id' => $newOfferId,
                    'offer_discount' => $offerDiscount,
                    'final_amount' => $finalAmount,
                    'room_length' => $item['room_length'],
                    'room_width' => $item['room_width'],
                    'description' => $item['description'],
                    'reference_image' => $item['reference_image'],
                    'created_at' => $item['created_at'],
                    'updated_at' => $item['updated_at'],
                    'add_ons' => [],
                    'parent_offer' => $offer,
                    'offer_details' => $offer ? [
                        'id' => $offer['id'],
                        'title' => $offer['title'],
                        'discount_type' => $offer['discount_type'],
                        'discount_value' => $offer['discount_value'],
                        'start_date' => $offer['start_date'],
                        'end_date' => $offer['end_date'],
                    ] : null
                ];
            }
        }

        foreach ($cartItems as $item) {
            if (!empty($item['parent_cart_id']) && isset($grouped[$item['parent_cart_id']])) {
                $addonBase = floatval($item['addon_price'] ?? $item['base_rate'] ?? 0);
                $addonQty = floatval($item['quantity'] ?? 1);
                $parentOfferId = isset($grouped[$item['parent_cart_id']]['offer_id'])
                    ? (int) $grouped[$item['parent_cart_id']]['offer_id']
                    : null;
                $offer = $grouped[$item['parent_cart_id']]['parent_offer'] ?? null;

                $addonSellingRate = $round2($offerModel->applyDiscount($addonBase, $offer));
                $addonDiscountPerUnit = $round2(max($addonBase - $addonSellingRate, 0));
                $addonDiscount = $round2($addonDiscountPerUnit * $addonQty);
                $addonFinal = $round2($addonSellingRate * $addonQty);
                $this->update($item['id'], [
                    'base_rate' => $round2($addonBase),
                    'selling_rate' => $addonSellingRate,
                    'offer_id' => $parentOfferId,
                    'offer_discount' => $addonDiscount,
                    'final_amount' => $addonFinal,
                ]);

                $grouped[$item['parent_cart_id']]['add_ons'][] = [
                    'id' => $item['id'],
                    'parent_cart_id' => $item['parent_cart_id'],
                    'addon_id' => $item['addon_id'],
                    'addon_name' => $item['addon_name'],
                    'quantity' => $addonQty,
                    'base_rate' => $addonBase,
                    'selling_rate' => $addonSellingRate,
                    'offer_id' => $parentOfferId,
                    'offer_discount' => $addonDiscount,
                    'final_amount' => $addonFinal,
                    'created_at' => $item['created_at'],
                ];
            }
        }

        foreach ($grouped as &$serviceGroup) {
            unset($serviceGroup['parent_offer']);
        }
        unset($serviceGroup);

        return array_values($grouped);
    }
}
