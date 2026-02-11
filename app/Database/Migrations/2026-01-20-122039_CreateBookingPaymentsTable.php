<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBookingPaymentsTable extends Migration
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

            'booking_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],

            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],

            'payment_gateway' => [
                'type'       => 'ENUM',
                'constraint' => ['razorpay', 'manual'],
            ],

            'payment_method' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'comment'    => 'razorpay: upi, card, netbanking, wallet | manual: cash, upi, bank_transfer',
            ],

            'gateway_payment_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'comment'    => 'razorpay_payment_id OR manual reference / upi txn id',
            ],

            'amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
            ],

            'currency' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'default'    => 'INR',
            ],

            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'success', 'failed', 'refunded', 'partial_refund'],
                'default'    => 'pending',
            ],

            'paid_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],

            'created_at' => [
                'type' => 'DATETIME',
            ],
        ]);

        // KEYS
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('booking_id');
        $this->forge->addKey('gateway_payment_id');
        $this->forge->addKey('user_id');
        // FOREIGN KEY (NO CASCADE DELETE ON MONEY)
        $this->forge->addForeignKey(
            'booking_id',
            'bookings',
            'id',
            'RESTRICT',
            'CASCADE'
        );

        $this->forge->addForeignKey(
            'user_id',
            'customers',
            'id',
            'RESTRICT',
            'CASCADE'
        );
        $this->forge->createTable('booking_payments', false, [
            'ENGINE' => 'InnoDB',
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('booking_payments', true);
    }
}
