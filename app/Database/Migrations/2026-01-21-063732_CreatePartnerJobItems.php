<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePartnerJobItems extends Migration
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

            'partner_job_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],

            'parent_item_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'Parent item for addons',
            ],

            'service_source' => [
                'type'       => 'ENUM',
                'constraint' => [
                    'booking_service',
                    'additional_service',
                    'manual',
                    'addon'
                ],
            ],

            'source_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
                'comment'    => 'booking_services.id or booking_additional_services.id',
            ],

            'room_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'Room reference (same as booking service)',
            ],
            'with_material' => [
                'type'    => 'BOOLEAN',
                'default' => false,
                'null'    => false,
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],

            'quantity' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => 0.00,
            ],

            'unit' => [
                'type'       => 'ENUM',
                'constraint' => [
                    'sqft',
                    'points',
                    'unit',
                    'running_feet',
                    'running_meter',
                    'square_feet'
                ],
            ],

            'rate' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => 0.00,
                'comment'    => 'Partner rate snapshot',
            ],

            'amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => 0.00,
                'comment'    => 'quantity × rate',
            ],

            'status' => [
                'type'       => 'ENUM',
                'constraint' => [
                    'pending',
                    'completed',
                    'cancelled'
                ],
                'default' => 'pending',
            ],

            'checklist_status' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Service based checklist like started, material_collected, qc_done',
            ],

            'cancelled_by' => [
                'type'       => 'ENUM',
                'constraint' => ['partner', 'admin', 'customer'],
                'null'       => true,
            ],

            'cancel_reason' => [
                'type' => 'TEXT',
                'null' => true,
            ],

            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('room_id');

        $this->forge->addForeignKey(
            'partner_job_id',
            'partner_jobs',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->forge->addForeignKey(
            'room_id',
            'rooms',
            'id',
            'SET NULL',
            'CASCADE'
        );

        $this->forge->createTable('partner_job_items', true);
    }

    public function down()
    {
        $this->forge->dropTable('partner_job_items', true);
    }
}
