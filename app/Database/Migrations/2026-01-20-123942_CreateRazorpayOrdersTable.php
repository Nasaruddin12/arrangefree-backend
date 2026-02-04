<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRazorpayOrdersTable extends Migration
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

            'razorpay_order_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
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
                'constraint' => ['created', 'paid', 'failed'],
                'default'    => 'created',
            ],

            'receipt' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],

            'attempts' => [
                'type'       => 'INT',
                'constraint' => 5,
                'default'    => 0,
            ],

            'payment_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],

            'created_at' => [
                'type' => 'DATETIME',
            ],
        ]);

        // KEYS
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('razorpay_order_id');
        $this->forge->addKey('booking_id');
        $this->forge->addKey('user_id');

        $this->forge->addForeignKey(
            'user_id',
            'af_customers',
            'id',
            'RESTRICT',
            'CASCADE'
        );

        // FOREIGN KEY
        $this->forge->addForeignKey(
            'booking_id',
            'bookings',
            'id',
            'RESTRICT',
            'CASCADE'
        );

        $this->forge->createTable('razorpay_orders', false, [
            'ENGINE' => 'InnoDB',
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('razorpay_orders');
    }
}
