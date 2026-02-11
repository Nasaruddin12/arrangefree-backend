<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePaymentDisputesTable extends Migration
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
            'payment_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ],
            'booking_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['created', 'won', 'lost', 'closed'],
                'null'       => false,
            ],
            'reason' => [
                'type'       => 'TEXT',
                'null'       => true,
            ],
            'payload' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('payment_id');

        $this->forge->addForeignKey('booking_id', 'bookings', 'id', 'RESTRICT', 'CASCADE');

        $this->forge->createTable('payment_disputes');
    }

    public function down()
    {
        $this->forge->dropTable('payment_disputes');
    }
}
