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
        'rate',
        'amount',
        'room_length',
        'room_width',
        'description',
        'reference_image'
    ];

    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
    protected $dateFormat       = 'datetime';

    // Validation
    protected $validationRules = [];
    protected $validationMessages = [];
    protected $skipValidation = false;
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

    public function getCartsWithRoom()
    {
        return $this->select('seeb_cart.*, rooms.name')
                    ->join('rooms', 'rooms.id = seeb_cart.room_id', 'left')
                    ->findAll();
    }

    /**
     * Get cart items grouped by service with add-ons nested inside
     * Structure: [service_id] => { service_info, add_ons: [addon1, addon2] }
     */
    public function getCartItemsHierarchical($user_id = null)
    {
        $query = $this->select('
                    seeb_cart.*,
                    services.id as service_id,
                    services.image as service_image,
                    services.name as service_name,
                    services.rate as current_rate,
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

        // Structure the data hierarchically
        $grouped = [];
        foreach ($cartItems as $item) {
            // Main service (where parent_cart_id is NULL)
            if (empty($item['parent_cart_id'])) {
                $serviceKey = $item['id'];
                $grouped[$serviceKey] = [
                    'id' => $item['id'],
                    'user_id' => $item['user_id'],
                    'service_id' => $item['service_id'],
                    'service_name' => $item['service_name'],
                    'service_image' => $item['service_image'],
                    'current_rate' => $item['current_rate'],
                    'room_id' => $item['room_id'],
                    'room_name' => $item['room_name'],
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'],
                    'rate' => $item['rate'],
                    'amount' => $item['amount'],
                    'room_length' => $item['room_length'],
                    'room_width' => $item['room_width'],
                    'description' => $item['description'],
                    'reference_image' => $item['reference_image'],
                    'created_at' => $item['created_at'],
                    'updated_at' => $item['updated_at'],
                    'add_ons' => []
                ];
            }
        }

        // Add add-ons to their parent services
        foreach ($cartItems as $item) {
            if (!empty($item['parent_cart_id']) && isset($grouped[$item['parent_cart_id']])) {
                $grouped[$item['parent_cart_id']]['add_ons'][] = [
                    'id' => $item['id'],
                    'parent_cart_id' => $item['parent_cart_id'],
                    'addon_id' => $item['addon_id'],
                    'addon_name' => $item['addon_name'],
                    'addon_price' => $item['addon_price'],
                    'quantity' => $item['quantity'],
                    'rate' => $item['rate'],
                    'amount' => $item['amount'],
                    'created_at' => $item['created_at'],
                ];
            }
        }

        return array_values($grouped); // Return as indexed array, not associative
    }
}