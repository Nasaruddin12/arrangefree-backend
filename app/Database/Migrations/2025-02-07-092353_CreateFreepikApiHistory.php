<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFreepikApiHistory extends Migration
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
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'prompt' => [
                'type'       => 'TEXT',
                'null'       => false,
            ],
            'images' => [
                'type'       => 'TEXT',
                'null'       => false,
            ],
            'type' => [
                'type'       => 'ENUM',
                'constraint' => ['search', 'floorplan'],
                'default'    => 'search',
            ],
            'created_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('freepik_api_history');
    }

    public function down()
    {
        $this->forge->dropTable('freepik_api_history');
    }
}
