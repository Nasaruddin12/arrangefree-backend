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
            'user_id' => [  // Foreign key to associate with users
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            'booking_id' => [  // Foreign key to associate with bookings
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true, // Nullable if not always associated with a booking
            ],
            'order_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'unique'     => true,
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
            'payment_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ]
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'af_customers', 'id', 'CASCADE', 'CASCADE'); // Foreign key to af_customers table
        $this->forge->addForeignKey('booking_id', 'bookings', 'id', 'SET NULL', 'CASCADE'); // Foreign key to af_bookings table
        $this->forge->createTable('razorpay_orders');
    }

    public function down()
    {
        $this->forge->dropTable('razorpay_orders');
    }
}
