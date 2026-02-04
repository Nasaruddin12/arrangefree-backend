<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePartnerJobStatusLogs extends Migration
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

            'old_status' => [
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
            ],

            'new_status' => [
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
            ],

            'changed_by' => [
                'type'       => 'ENUM',
                'constraint' => ['admin', 'partner', 'system'],
            ],

            'changed_by_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],

            'note' => [
                'type' => 'TEXT',
                'null' => true,
            ],

            'created_at' => [
                'type' => 'DATETIME',
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

        $this->forge->createTable('partner_job_status_logs', true);
    }

    public function down()
    {
        $this->forge->dropTable('partner_job_status_logs', true);
    }
}
