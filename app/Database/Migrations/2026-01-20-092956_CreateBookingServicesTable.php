<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBookingServicesTable extends Migration
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
                'comment'    => 'Links addon to main service',
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
                'comment'    => 'quantity × rate',
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

            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],

            'reference_image' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'JSON or comma-separated image URLs',
            ],

            'is_job_created' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'comment'    => '0 = not created, 1 = job created',
            ],

            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['active', 'cancelled'],
                'default'    => 'active',
            ],

            'cancelled_by' => [
                'type'       => 'ENUM',
                'constraint' => ['admin', 'customer'],
                'null'       => true,
            ],

            'cancelled_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],

            'cancel_reason' => [
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
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey(
            'booking_id',
            'bookings',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->forge->addForeignKey(
            'parent_booking_service_id',
            'booking_services',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->forge->addForeignKey('service_id', 'services', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('service_type_id', 'service_types', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('room_id', 'rooms', 'id', 'SET NULL', 'CASCADE');


        $this->forge->createTable('booking_services', false, [
            'ENGINE' => 'InnoDB',
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('booking_services');
    }
}
