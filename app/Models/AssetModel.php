<?php

namespace App\Models;

use CodeIgniter\Model;

class AssetModel extends Model
{
    protected $table      = 'assets';
    protected $primaryKey = 'id';

    protected $allowedFields = ['title', 'tags', 'file', 'details', 'width', 'length', 'room_id', 'style_id', 'room_element_id', 'created_at', 'updated_at'];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    public function getAssetsWithRoomName()
    {
        return $this->select('assets.*, rooms.naem as room_name')
            ->join('room_elements', 'rooms.id = assets.room_id', 'left')
            ->findAll();
    }
}
