<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateServiceRoomsTable extends Migration
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
            'service_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'room_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
        ]);

        // Add primary key
        $this->forge->addKey('id', true);

        // Add foreign keys
        $this->forge->addForeignKey('service_id', 'services', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('room_id', 'rooms', 'id', 'CASCADE', 'CASCADE');

        // Create table
        $this->forge->createTable('service_rooms');
    }

    public function down()
    {
        $this->forge->dropTable('service_rooms');
    }
}
