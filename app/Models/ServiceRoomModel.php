<?php

namespace App\Models;

use CodeIgniter\Model;

class ServiceRoomModel extends Model
{
    protected $table = 'service_rooms';
    protected $primaryKey = 'id';
    protected $allowedFields = ['service_id', 'room_id'];

    public function updateServiceRooms($service_id, $roomIds)
    {
        $db = \Config\Database::connect();
        $builder = $db->table($this->table);

        // Begin Transaction
        $db->transStart();

        // Step 1: Delete existing rooms for the work_type_id only if new rooms exist
        if (!empty($roomIds)) {
            $builder->where('service_id', $service_id)->delete();

            // Step 2: Insert new room_ids
            $data = [];
            foreach ($roomIds as $roomId) {
                $data[] = [
                    'service_id' => $service_id,
                    'room_id' => $roomId
                ];
            }

            if (!empty($data)) {
                $builder->insertBatch($data); // Batch insert for efficiency
            }
        }

        // Commit Transaction
        $db->transComplete();

        // Ensure transaction was successful
        return $db->transStatus();
    }
}
