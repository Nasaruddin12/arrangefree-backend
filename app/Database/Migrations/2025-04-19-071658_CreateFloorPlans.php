<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class FloorPlans extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'room_name'       => ['type' => 'VARCHAR', 'constraint' => 255],
            'room_width'      => ['type' => 'FLOAT', 'null' => true], // in feet/meters
            'room_height'     => ['type' => 'FLOAT', 'null' => true],
            'room_length'     => ['type' => 'FLOAT', 'null' => true],
            'canvas_json'     => ['type' => 'TEXT', 'null' => true], // JSON for positions, rotations, etc.
            'file'            => ['type' => 'TEXT', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'af_customers', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('floor_plans');
    }

    public function down()
    {
        $this->forge->dropTable('floor_plans');
    }
}
