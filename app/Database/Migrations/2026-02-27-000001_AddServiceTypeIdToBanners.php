<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddServiceTypeIdToBanners extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('service_type_id', 'banners')) {
            $this->forge->addColumn('banners', [
                'service_type_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 0,
                    'after' => 'service_id',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('service_type_id', 'banners')) {
            $this->forge->dropColumn('banners', 'service_type_id');
        }
    }
}

