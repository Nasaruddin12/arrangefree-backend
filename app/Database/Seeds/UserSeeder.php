<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'id' => 1,
                'name' => 'Sahil Khatik Customer',
                'email' => 'sahilhaps7@gmail.com',
                'mobile_no' => '7823098610',
                'password' => 'user@123',
                'is_logged_in' => 1,
                'status' => 1,
            ],
            [
                'id' => 2,
                'name' => 'Naumaan',
                'email' => 'naumaan@gmail.com',
                'mobile_no' => '1212121212',
                'password' => 'user@123',
                'is_logged_in' => 1,
                'status' => 1,
            ],
        ];

        $this->db->table('af_customers')->insertBatch($data);
    }
}
