<?php

namespace App\Models;

use CodeIgniter\Model;

class SeebCartModel extends Model
{
    protected $table            = 'seeb_cart';
    protected $primaryKey       = 'id';
    protected $allowedFields    = [
        'user_id', 'service_id', 'service_type_id', 'room_id', 
        'rate_type', 'value',
        //  'rate', 'amount',
          'addons',
        'description', 'reference_image', 'created_at', 'updated_at'
    ];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    public function getCartsWithRoom()
    {
        return $this->select('cart.*, rooms.room_name')
                    ->join('rooms', 'rooms.id = cart.room_id', 'left')
                    ->findAll();
    }
}
