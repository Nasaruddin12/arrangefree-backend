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

    public function findByServiceTypeAndRoom($service_type_id, $roomId)
    {
        // Step 1: Get all matching services
        $services = $this->select('services.*')
            ->join('service_rooms', 'service_rooms.service_id = services.id', 'inner')
            ->where('services.service_type_id', $service_type_id)
            ->where('service_rooms.room_id', $roomId)
            ->where('services.status', 1)
            ->findAll();

        // Step 2: Attach addons for each service
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
