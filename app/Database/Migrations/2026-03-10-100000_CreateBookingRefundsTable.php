<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBookingRefundsTable extends Migration
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

            'booking_service_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],

            'booking_additional_service_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],

            'booking_adjustment_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],

            'payment_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],

            'refund_scope' => [
                'type'       => 'ENUM',
                'constraint' => ['booking', 'booking_service', 'additional_service'],
                'default'    => 'booking_service',
            ],

            'refund_type' => [
                'type'       => 'ENUM',
                'constraint' => ['full', 'partial'],
                'default'    => 'full',
            ],

            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'approved', 'processed', 'failed', 'cancelled'],
                'default'    => 'pending',
            ],

            'refund_method' => [
                'type'       => 'ENUM',
                'constraint' => ['original_source', 'wallet', 'bank_transfer', 'manual'],
                'null'       => true,
            ],

            'gateway_refund_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],

            'reason' => [
                'type' => 'TEXT',
                'null' => true,
            ],

            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],

            'base_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => 0.00,
            ],

            'discount_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => 0.00,
            ],

            'taxable_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
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

            'cgst_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => 0.00,
            ],

            'sgst_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => 0.00,
            ],

            'total_refund_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => 0.00,
            ],

            'requested_by_type' => [
                'type'       => 'ENUM',
                'constraint' => ['admin', 'customer', 'system'],
                'default'    => 'system',
            ],

            'requested_by_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],

            'processed_by_type' => [
                'type'       => 'ENUM',
                'constraint' => ['admin', 'system'],
                'null'       => true,
            ],

            'processed_by_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],

            'processed_at' => [
                'type' => 'DATETIME',
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
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('booking_id');
        $this->forge->addKey('booking_service_id');
        $this->forge->addKey('booking_additional_service_id');
        $this->forge->addKey('booking_adjustment_id');
        $this->forge->addKey('payment_id');
        $this->forge->addKey('status');
        $this->forge->addKey('refund_scope');

        $this->forge->addForeignKey('booking_id', 'bookings', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('booking_service_id', 'booking_services', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('booking_additional_service_id', 'booking_additional_services', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('booking_adjustment_id', 'booking_adjustments', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('payment_id', 'booking_payments', 'id', 'SET NULL', 'CASCADE');

        $this->forge->createTable('booking_refunds', false, [
            'ENGINE' => 'InnoDB',
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('booking_refunds', true);
    }
}
