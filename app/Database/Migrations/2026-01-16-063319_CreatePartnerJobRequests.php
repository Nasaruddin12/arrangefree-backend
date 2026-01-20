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
                'unsigned'       => true,
                'auto_increment' => true,
            ],

            'partner_job_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],

            'booking_service_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],

            // Partner execution snapshot
            'partner_rate' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
            ],

            'partner_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
            ],

            'with_material' => [
                'type'    => 'BOOLEAN',
                'default' => 0,
            ],

            // Execution control
            'status' => [
                'type'       => 'ENUM',
                'constraint' => [
                    'pending',
                    'in_progress',
                    'completed',
                    'on_hold',
                    'cancelled'
                ],
                'default' => 'pending',
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
        $this->forge->addKey('partner_job_id');
        $this->forge->addKey('booking_service_id');

        $this->forge->addForeignKey(
            'booking_service_id',
            'booking_services',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->forge->createTable('partner_job_items');
    }

    public function down()
    {
        $this->forge->dropTable('partner_job_items');
    }
}

