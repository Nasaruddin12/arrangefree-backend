<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBookingAssignmentsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                        => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'booking_service_id'        => ['type' => 'INT', 'unsigned' => true],
            'partner_id'                => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'assigned_amount'           => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'helper_count'              => ['type' => 'INT', 'default' => 0],
            'status'                    => [
                'type'       => 'ENUM',
                'constraint' => ['unclaimed', 'assigned', 'in_progress', 'completed', 'rejected'],
                'default'    => 'unclaimed',
            ],
            'assigned_at'               => ['type' => 'DATETIME', 'null' => true],
            'accepted_at'               => ['type' => 'DATETIME', 'null' => true],
            'estimated_start_date'      => ['type' => 'DATE', 'null' => true],
            'estimated_completion_date' => ['type' => 'DATE', 'null' => true],
            'actual_completion_date'    => ['type' => 'DATE', 'null' => true],
            'admin_notes'               => ['type' => 'TEXT', 'null' => true],
            'created_at'                => ['type' => 'DATETIME', 'null' => true],
            'updated_at'                => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('booking_service_id', 'booking_services', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('partner_id', 'partners', 'id', 'CASCADE', 'CASCADE');

        $this->forge->createTable('booking_assignments');
    }

    public function down()
    {
        $this->forge->dropTable('booking_assignments');
    }
}
