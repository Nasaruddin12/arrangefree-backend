<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePartnerJobPayments extends Migration
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

            'partner_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],

            'earning_type' => [
                'type'       => 'ENUM',
                'constraint' => [
                    'job',
                    'referral',
                    'visit',
                    'incentive',
                    'manual'
                ],
            ],

            'source_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
                'comment'    => 'job_id / referral_id / visit_id etc',
            ],

            'amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
            ],

            'status' => [
                'type'       => 'ENUM',
                'constraint' => [
                    'pending',
                    'approved',
                    'rejected'
                ],
                'default' => 'pending',
            ],

            'approved_by' => [
                'type'       => 'ENUM',
                'constraint' => ['admin', 'system'],
                'null'       => true,
            ],

            'approved_by_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],

            'approved_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],

            'note' => [
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
            'partner_id',
            'partners',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->forge->createTable('partner_job_payments', true);
    }

    public function down()
    {
        $this->forge->dropTable('partner_job_payments', true);
    }
}
