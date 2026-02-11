<?php

namespace App\Database\Seeds;

use App\Models\AdminModel;
use CodeIgniter\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'id' => 1,
                'role_id' => 0,
                'name' => 'Sahil Khatik',
                'email' => 'sahilhaps7@gmail.com',
                'mobile_no' => '7823098610',
                'password' => 'admin@123',
                'is_logged_in' => 1,
                'status' => 1,
            ],
            [
                'id' => 2,
                'role_id' => 0,
                'name' => 'Naumaan',
                'email' => 'naumaan@gmail.com',
                'mobile_no' => '1212121212',
                'password' => 'admin@123',
                'is_logged_in' => 1,
                'status' => 1,
            ],
        ];

        $this->db->table('admins')->insertBatch($data);
    }
}
