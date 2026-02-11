<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class MetaData extends Seeder
{
    public function run()
    {
        $data = [
            [
                'id' => 1,
                'group_id' => 1,
                'title' => 'discount',
                'value' => 50,
            ],
            [
                'id' => 2,
                'group_id' => 2,
                'title' => 'Order Status',
                'value' => 'Order Confirmed',
            ],
            [
                'id' => 3,
                'group_id' => 2,
                'title' => 'Order Status',
                'value' => 'Shipping',
            ],
            [
                'id' => 4,
                'group_id' => 2,
                'title' => 'Order Status',
                'value' => 'Out for Delivery',
            ],
            [
                'id' => 5,
                'group_id' => 2,
                'title' => 'Order Status',
                'value' => 'Delivered',
            ],
            [
                'id' => 6,
                'group_id' => 1,
                'title' => 'increase_percent',
                'value' => 10,
            ],
        ];
        $this->db->table('meta_data')->insertBatch($data);
    }
}
