<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBookingServiceVersionsTable extends Migration
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
            'booking_version_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],

            'service_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'service_type_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'room_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'rate_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'value' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
            ],
            'rate' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
            ],
            'amount' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
            ],
            'addons' => [
                'type' => 'JSON',
                'null' => true,
            ],

            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('booking_version_id');
        $this->forge->createTable('booking_service_versions');
    }

    public function down()
    {
        $this->forge->dropTable('booking_service_versions');
    }
}
