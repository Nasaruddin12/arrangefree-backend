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
                'unsigned'       => true,
                'auto_increment' => true,
            ],

            // Human readable job id (SE000341)
            'job_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'unique'     => true,
            ],

            'booking_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],

            'partner_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
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

            // Used when job stops after start
            'stopped_by' => [
                'type'       => 'ENUM',
                'constraint' => ['partner', 'admin', 'customer', 'system'],
                'null'       => true,
            ],

            'stop_reason' => [
                'type' => 'TEXT',
                'null' => true,
            ],

            // Aggregated settlement amount
            'total_partner_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => true,
            ],

            'estimated_start_date' => [
                'type' => 'DATE',
                'null' => true,
            ],

            'estimated_completion_date' => [
                'type' => 'DATE',
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
        $this->forge->addKey('job_id');
        $this->forge->addKey('booking_id');
        $this->forge->addKey('partner_id');

        $this->forge->createTable('partner_jobs');
    }

    public function down()
    {
        $this->forge->dropTable('partner_jobs');
    }
}
