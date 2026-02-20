<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBookingAdditionalServicesTable extends Migration
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

            'parent_booking_service_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'Links to original additional booking_service',
            ],

            'service_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'addon_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'service_type_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],

            'room_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],

            'quantity' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => 0.00,
                'comment'    => 'Added quantity only',
            ],

            'base_rate' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => true,
                'comment'    => 'Original rate per unit',
            ],

            'base_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'null'       => true,
                'comment'    => 'base_rate × quantity',
            ],

            'offer_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'Applied offer ID',
            ],

            'offer_discount' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => 0.00,
                'comment'    => 'Total offer discount amount',
            ],

            'unit' => [
                'type'       => 'ENUM',
                'constraint' => ['unit', 'square_feet', 'running_feet', 'running_meter', 'point', 'sqft'],
            ],

            'rate' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => 0.00,
            ],

            'amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => 0.00,
                'comment'    => 'quantity × rate (before tax)',
            ],

            'cgst_rate' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'default'    => 0.00,
                'comment'    => 'CGST rate %',
            ],

            'sgst_rate' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'default'    => 0.00,
                'comment'    => 'SGST rate %',
            ],

            'cgst_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => 0.00,
                'comment'    => 'CGST amount',
            ],

            'sgst_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => 0.00,
                'comment'    => 'SGST amount',
            ],

            'total_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => 0.00,
                'comment'    => 'amount + cgst_amount + sgst_amount',
            ],

            'room_length' => [
                'type'       => 'DECIMAL',
                'constraint' => '8,2',
                'null'       => true,
            ],

            'room_width' => [
                'type'       => 'DECIMAL',
                'constraint' => '8,2',
                'null'       => true,
            ],

            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'approved', 'rejected', 'cancelled'],
                'default'    => 'pending',
                'comment'    => 'Customer approval status',
            ],

            'is_payment_required' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'comment'    => '0 = free, 1 = payment required',
            ],

            'created_by' => [
                'type'       => 'ENUM',
                'constraint' => ['admin', 'partner'],
                'default'    => 'admin',
            ],

            'created_by_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],

            'approved_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],

            'approved_by' => [
                'type'       => 'ENUM',
                'constraint' => ['admin', 'customer'],
                'null'       => true,
            ],

            'approved_by_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
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
        $this->forge->addForeignKey('booking_id', 'bookings', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('parent_booking_service_id', 'booking_additional_services', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('service_id', 'services', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('service_type_id', 'service_types', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('room_id', 'rooms', 'id', 'SET NULL', 'CASCADE');

        $this->forge->createTable('booking_additional_services', false, [
            'ENGINE' => 'InnoDB',
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('booking_additional_services');
    }
}
