<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBookingAssignmentRequestsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'booking_service_id'=> ['type' => 'INT', 'unsigned' => true],
            'partner_id'        => ['type' => 'INT', 'unsigned' => true],
            'status'            => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'accepted', 'expired', 'rejected'],
                'default'    => 'pending'
            ],
            'sent_at'           => ['type' => 'DATETIME', 'null' => true],
            'accepted_at'       => ['type' => 'DATETIME', 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('booking_service_id', 'booking_services', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('partner_id', 'partners', 'id', 'CASCADE', 'CASCADE');

        $this->forge->createTable('booking_assignment_requests');
    }

    public function down()
    {
        $this->forge->dropTable('booking_assignment_requests');
    }
}
