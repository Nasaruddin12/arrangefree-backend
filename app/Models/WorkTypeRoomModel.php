<?php

namespace App\Models;

use CodeIgniter\Model;

class WorkTypeRoomModel extends Model
{
    protected $table = 'work_type_rooms';
    protected $primaryKey = 'id';
    protected $allowedFields = ['work_type_id', 'room_id'];

    public function updateWorkTypeRooms($workTypeId, $roomIds)
    {
        $db = \Config\Database::connect();
        $builder = $db->table($this->table);

        // Begin Transaction
        $db->transStart();

        // Step 1: Delete existing rooms for the work_type_id only if new rooms exist
        if (!empty($roomIds)) {
            $builder->where('work_type_id', $workTypeId)->delete();

            // Step 2: Insert new room_ids
            $data = [];
            foreach ($roomIds as $roomId) {
                $data[] = [
                    'work_type_id' => $workTypeId,
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
