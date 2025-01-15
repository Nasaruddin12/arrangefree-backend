<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AfHomeZoneCategory extends Migration
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
                'unsigned' => true,
            ],
            'title' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'image' => [
                'type' => 'VARCHAR',
                'constraint' => 256,
            ],
            'slug' => [
                'type' => 'VARCHAR',
                'constraint' => 101,
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            
        ]);
        $this->forge->addPrimaryKey('id');
        // $this->forge->addForeignKey('home_zone_id', 'home_zones', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('af_home_zone_category');
    }

    public function down()
    {
        $this->forge->dropTable('af_home_zone_category');

    }
}
