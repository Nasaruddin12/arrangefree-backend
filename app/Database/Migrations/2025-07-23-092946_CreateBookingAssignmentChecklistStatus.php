<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBookingAssignmentChecklistStatus extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'auto_increment' => true],
            'booking_service_id' => ['type' => 'INT', 'null' => false],
            'checklist_id'      => ['type' => 'INT', 'null' => false],
            'partner_id'        => ['type' => 'INT', 'null' => false],
            'is_done'           => ['type' => 'BOOLEAN', 'default' => false],
            'note'              => ['type' => 'TEXT', 'null' => true],
            'image_url'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('booking_assignment_checklist_status');
    }

    public function down()
    {
        $this->forge->dropTable('booking_assignment_checklist_status');
    }
}
