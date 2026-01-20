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
            ],

            'service_type_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],

            'room_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],

            'quantity' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => 0.00,
            ],

            'unit' => [
                'type'       => 'ENUM',
                'constraint' => ['sqft', 'running_feet', 'running_meter', 'unit', 'point'],
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
        $this->forge->addKey('parent_booking_service_id');
        $this->forge->addKey('service_id');
        $this->forge->addKey('service_type_id');
        $this->forge->addKey('room_id');
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
            'SET NULL',
            'CASCADE'
        );

        $this->forge->createTable('booking_services');
    }

    public function down()
    {
        $this->forge->dropTable('booking_services');
    }
}
