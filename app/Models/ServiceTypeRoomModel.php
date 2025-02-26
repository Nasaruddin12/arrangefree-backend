<?php

namespace App\Models;

use CodeIgniter\Model;

class ServiceTypeRoomModel extends Model
{
    protected $table = 'service_type_rooms'; // Table name
    protected $primaryKey = 'id'; // Primary key
    protected $allowedFields = ['service_type_id', 'room_id']; // Allowed fields for mass assignment
    public $timestamps = false; // Disable timestamps if not needed

    /**
     * Updates the room associations for a service.
     *
     * @param int $serviceId
     * @param array $roomIds
     * @return bool
     */
    public function updateServiceTypeRooms($service_type_id, $roomIds)
    {
        $db = \Config\Database::connect();
        $builder = $db->table($this->table);

        // Start transaction
        $db->transStart();

        // Step 1: Delete existing rooms for this service
        $builder->where('service_type_id', $service_type_id)->delete();

        // Step 2: Insert new room associations
        $data = [];
        foreach ($roomIds as $roomId) {
            $data[] = [
                'service_type_id' => $service_type_id,
                'room_id' => $roomId
            ];
        }

        if (!empty($data)) {
            $builder->insertBatch($data); // Batch insert for efficiency
        }

        // Commit transaction
        $db->transComplete();

        return $db->transStatus();
    }
}
