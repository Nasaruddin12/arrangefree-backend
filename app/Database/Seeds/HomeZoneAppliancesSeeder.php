<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class HomeZoneAppliancesSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
            'id' => 1,
            'title' => 'Sofa',
            ],
            [
                'id' => 2,
            'title' => 'OutDoor Furniture',
            ],
            [
                'id' => 3,
            'title' => 'Center Table',
            ],
            [
                'id' => 4,
            'title' => 'Dinning Table',
            ],
            [
                'id' => 5,
            'title' => 'Rug Carpets',
            ],
            [
                'id' => 6,
            'title' => 'BedRoom Furniture',
            ],
            [
                'id' => 7,
            'title' => 'Furniture',
            ],
            [
                'id' => 8,
            'title' => 'Mattresses',
            ],
            ];

            helper('slug');
        foreach($data as $key => $single) {
            $data[$key]['slug'] = slugify($single['title']);
        }
        $this->db->table('af_home_zone_appliances')->insertBatch($data);
    }
}
