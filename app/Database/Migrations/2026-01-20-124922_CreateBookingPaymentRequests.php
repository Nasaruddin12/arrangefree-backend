<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBookingPaymentRequests extends Migration
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

            'requested_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'comment'    => 'Amount requested from customer',
            ],

            'currency' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'default'    => 'INR',
            ],

            'payment_gateway' => [
                'type'       => 'ENUM',
                'constraint' => ['razorpay', 'manual'],
                'comment'    => 'Payment collection mode',
            ],

            'razorpay_order_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'comment'    => 'Present only for razorpay requests',
            ],

            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'paid', 'expired', 'cancelled'],
                'default'    => 'pending',
            ],

            'requested_by' => [
                'type'       => 'ENUM',
                'constraint' => ['admin', 'system'],
                'default'    => 'system',
            ],

            'requested_at' => [
                'type' => 'DATETIME',
            ],

            'expires_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'For payment link expiry',
            ],

            'created_at' => [
                'type' => 'DATETIME',
            ],
        ]);

        // KEYS
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('booking_id');
        $this->forge->addKey('razorpay_order_id');
        $this->forge->addKey('user_id');

        $this->forge->addForeignKey(
            'user_id',
            'af_customers',
            'id',
            'RESTRICT',
            'CASCADE'
        );

        // FK (STRICT — requests should never orphan)
        $this->forge->addForeignKey(
            'booking_id',
            'bookings',
            'id',
            'RESTRICT',
            'CASCADE'
        );

        $this->forge->createTable('booking_payment_requests');
    }

    public function down()
    {
        $this->forge->dropTable('booking_payment_requests', true);
    }
}
