<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBookingsTable extends Migration
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

            'booking_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'unique'     => true,
            ],

            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],

            'slot_date' => [
                'type' => 'DATE',
            ],

            'subtotal_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => 0.00,
            ],

            'total_discount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => 0.00,
            ],
            'total_offer_discount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => 0.00,
            ],
            'total_coupon_discount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => 0.00,
            ],
            'coupon_id' => [
                'type'       => 'INT',
                'null'       => true,
            ],

            'cgst' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => 0.00,
            ],

            'sgst' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => 0.00,
            ],

            'cgst_rate' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'default'    => 0.00,
            ],

            'sgst_rate' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'default'    => 0.00,
            ],

            'final_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => 0.00,
            ],

            'payment_type' => [
                'type'       => 'ENUM',
                'constraint' => ['pay_later', 'online'],
                'default'    => 'pay_later',
            ],

            'payment_status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'partial', 'completed', 'failed', 'refunded'],
                'default'    => 'pending',
            ],

            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'],
                'default'    => 'pending',
            ],

            'applied_coupon' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],

            'pricing_locked' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'comment'    => '1 = pricing frozen',
            ],

            'cancelled_by' => [
                'type'       => 'ENUM',
                'constraint' => ['admin', 'customer', 'system'],
                'null'       => true,
            ],

            'cancelled_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],

            'cancellation_reason' => [
                'type' => 'TEXT',
                'null' => true,
            ],

            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],

            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],

        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addForeignKey(
            'user_id',
            'customers',
            'id',
            'RESTRICT',
            'CASCADE'
        );

        $this->forge->createTable('bookings',  false, [
            'ENGINE' => 'InnoDB',
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('bookings');
    }
}
