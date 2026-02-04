<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePartnerJobs extends Migration
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

            'job_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'comment'    => 'Human readable job id e.g. SE000341',
            ],

            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'comment'    => 'Job title/label',
            ],

            'notes' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Internal notes',
            ],

            'booking_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],

            'partner_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],

            'assigned_by_admin_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'Admin who assigned the job',
            ],

            'status' => [
                'type'       => 'ENUM',
                'constraint' => [
                    'pending',
                    'assigned',
                    'accepted',
                    'in_progress',
                    'partially_completed',
                    'completed',
                    'cancelled'
                ],
                'default' => 'pending',
            ],

            'stopped_by' => [
                'type'       => 'ENUM',
                'constraint' => ['partner', 'admin', 'customer'],
                'null'       => true,
            ],

            'stop_reason' => [
                'type' => 'TEXT',
                'null' => true,
            ],

            'total_partner_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => 0.00,
            ],

            'estimated_start_date' => [
                'type' => 'DATE',
                'null' => true,
            ],

            'estimated_completion_date' => [
                'type' => 'DATE',
                'null' => true,
            ],

            'assigned_at' => [
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
        $this->forge->addUniqueKey('job_id');

        // Foreign Keys (SAFE: no delete cascade)
        $this->forge->addForeignKey(
            'booking_id',
            'bookings',
            'id',
            'RESTRICT',
            'CASCADE'
        );

        $this->forge->addForeignKey(
            'partner_id',
            'partners',
            'id',
            'SET NULL',
            'CASCADE'
        );

        $this->forge->createTable('partner_jobs', true);
    }

    public function down()
    {
        $this->forge->dropTable('partner_jobs', true);
    }
}
