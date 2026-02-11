<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePartnerWithdrawalRequests extends Migration
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

            'requested_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
            ],

            'status' => [
                'type'       => 'ENUM',
                'constraint' => [
                    'pending',
                    'approved',
                    'rejected',
                    'paid'
                ],
                'default' => 'pending',
            ],

            'requested_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],

            'approved_by_admin_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],

            'approved_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],

            'rejected_reason' => [
                'type' => 'TEXT',
                'null' => true,
            ],

            'note' => [
                'type' => 'TEXT',
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

        $this->forge->addForeignKey(
            'approved_by_admin_id',
            'admins',
            'id',
            'SET NULL',
            'CASCADE'
        );

        $this->forge->createTable('partner_withdrawal_requests', true);
    }

    public function down()
    {
        $this->forge->dropTable('partner_withdrawal_requests', true);
    }
}
