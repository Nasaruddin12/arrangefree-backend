<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBookingUpdates extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'auto_increment' => true],
            'booking_service_id' => ['type' => 'INT', 'null' => false],
            'partner_id'        => ['type' => 'INT', 'null' => false],
            'message'           => ['type' => 'TEXT', 'null' => true],
            'status'            => ['type' => 'VARCHAR', 'constraint' => '50', 'null' => true], // e.g. started, in_progress
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('booking_updates');
    }

    public function down()
    {
        $this->forge->dropTable('booking_updates');
    }
}
