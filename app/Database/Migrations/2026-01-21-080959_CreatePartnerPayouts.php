<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePartnerPayouts extends Migration
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

            'amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
            ],

            'payout_mode' => [
                'type'       => 'ENUM',
                'constraint' => [
                    'bank_transfer',
                    'upi'
                ],
            ],

            'transaction_reference' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'comment'    => 'UTR / Bank reference (ONLY here)',
            ],

            'status' => [
                'type'       => 'ENUM',
                'constraint' => [
                    'pending',
                    'completed',
                    'failed'
                ],
                'default' => 'pending',
            ],

            'initiated_by' => [
                'type'       => 'ENUM',
                'constraint' => [
                    'admin',
                    'system'
                ],
            ],

            'initiated_by_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],

            'initiated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],

            'completed_at' => [
                'type' => 'DATETIME',
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

        $this->forge->createTable('partner_payouts', true);
    }

    public function down()
    {
        $this->forge->dropTable('partner_payouts', true);
    }
}
