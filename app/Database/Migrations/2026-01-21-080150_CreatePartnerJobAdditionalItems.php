<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePartnerJobAdditionalItems extends Migration
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

            'booking_additional_service_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],

            'service_source' => [
                'type'       => 'ENUM',
                'constraint' => [
                    'booking_service',
                    'additional_service',
                    'manual'
                ],
            ],

            'source_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
                'comment'    => 'booking_services.id or booking_additional_services.id',
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
                    'point',
                    'unit',
                    'running_feet',
                    'running_meter'
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
            ],

            'status' => [
                'type'       => 'ENUM',
                'constraint' => [
                    'requested',
                    'partner_accepted',
                    'rejected',
                    'completed'
                ],
                'default' => 'requested',
            ],

            'requested_by' => [
                'type'       => 'ENUM',
                'constraint' => ['admin', 'customer'],
            ],

            'requested_note' => [
                'type' => 'TEXT',
                'null' => true,
            ],

            'approved_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],

            'approved_at' => [
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

        $this->forge->addForeignKey(
            'partner_job_id',
            'partner_jobs',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->forge->addForeignKey(
            'approved_by',
            'af_admins',
            'id',
            'SET NULL',
            'CASCADE'
        );

        $this->forge->createTable('partner_job_additional_items', true);
    }

    public function down()
    {
        $this->forge->dropTable('partner_job_additional_items', true);
    }
}
