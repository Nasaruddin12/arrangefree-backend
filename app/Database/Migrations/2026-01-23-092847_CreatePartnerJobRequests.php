<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePartnerJobRequests extends Migration
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

            'partner_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],

            'status' => [
                'type'       => 'ENUM',
                'constraint' => [
                    'requested',
                    'accepted',
                    'rejected',
                    'expired'
                ],
                'default' => 'requested',
            ],

            'requested_by' => [
                'type'       => 'ENUM',
                'constraint' => ['admin', 'system'],
            ],

            'requested_by_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],

            'responded_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],

            'response_note' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Partner rejection reason or note',
            ],

            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);

        // A partner should not have multiple active requests for same job
        $this->forge->addUniqueKey(['partner_job_id', 'partner_id']);

        $this->forge->addForeignKey(
            'partner_job_id',
            'partner_jobs',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->forge->addForeignKey(
            'partner_id',
            'partners',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->forge->createTable('partner_job_requests', true);
    }

    public function down()
    {
        $this->forge->dropTable('partner_job_requests', true);
    }
}
