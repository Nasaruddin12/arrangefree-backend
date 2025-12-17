<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLocationCacheTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'lat' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,6',
            ],
            'lng' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,6',
            ],
            'address' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'created_at' => [
                'type'    => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['lat', 'lng']);
        $this->forge->createTable('location_cache');
    }

    public function down()
    {
        $this->forge->dropTable('location_cache');
    }
}
