<?php

namespace App\Models;

use CodeIgniter\Model;

class ServiceModel extends Model
{
    protected $table = 'services';
    protected $primaryKey = 'id';
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
        'slug',
        'created_at',
        'updated_at'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

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
    public function findByServiceTypeAndRoomSlug($service_type_slug, $room_slug)
    {
        // Step 1: Get service_type_id from slug
        $db = \Config\Database::connect();
        $serviceType = $db->table('service_types')
            ->where('slug', $service_type_slug)
            ->get()
            ->getRow();
        
        if (!$serviceType) {
            return [];
        }
        
        // Step 2: Get room_id from slug
        $room = $db->table('rooms')
            ->where('slug', $room_slug)
            ->get()
            ->getRow();
        
        if (!$room) {
            return [];
        }
        
        // Step 3: Get all matching services
        $services = $this->select('services.*')
            ->join('service_rooms', 'service_rooms.service_id = services.id', 'inner')
            ->where('services.service_type_id', $serviceType->id)
            ->where('service_rooms.room_id', $room->id)
            ->where('services.status', 1)
            ->findAll();

        // Step 4: Attach addons for each service
        if (!empty($services)) {
            $addonModel = new \App\Models\ServiceAddonModel();

            foreach ($services as &$service) {
                $addons = $addonModel->where('service_id', $service['id'])->findAll();
                $service['addons'] = $addons;
            }
        }

        return $services;
    }
}
