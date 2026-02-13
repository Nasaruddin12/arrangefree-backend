<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AfBanners extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'home_zone_appliances_id' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'path' => [
                'type' => 'VARCHAR',
                'constraint' => 256
            ],
            'device' => [
                'type' => 'VARCHAR',
                'constraint' => 256
            ],
            'image_index' => [
                'type' => 'INT',
                'constraint' => 11
            ],
            'link' => [
                'type' => 'VARCHAR',
                'constraint' => 512,
                'null' => true,
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('banners');
    }

    public function down()
    {
        $this->forge->dropTable('banners');

    }
}
