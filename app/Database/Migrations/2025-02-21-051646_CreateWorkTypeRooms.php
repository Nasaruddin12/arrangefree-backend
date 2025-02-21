<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateWorkTypeRooms extends Migration
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
            'work_type_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            'room_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ]
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('work_type_id', 'work_types', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('room_id', 'rooms', 'id', 'CASCADE', 'CASCADE');

        $this->forge->createTable('work_type_rooms');
    }

    public function down()
    {
        $this->forge->dropTable('work_type_rooms');
    }
}
