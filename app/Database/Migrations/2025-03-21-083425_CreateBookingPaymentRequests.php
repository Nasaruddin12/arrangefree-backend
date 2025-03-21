<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBookingPaymentRequests extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'booking_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'user_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'amount'         => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'request_status' => ['type' => 'ENUM', 'constraint' => ['pending', 'completed', 'cancelled'], 'default' => 'pending'],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('booking_id', 'bookings', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'af_customers', 'id', 'CASCADE', 'CASCADE');

        $this->forge->createTable('booking_payment_requests');
    }

    public function down()
    {
        $this->forge->dropTable('booking_payment_requests');
    }
}
