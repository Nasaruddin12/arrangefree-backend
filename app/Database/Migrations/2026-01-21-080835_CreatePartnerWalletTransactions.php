<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePartnerWalletTransactions extends Migration
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

            'source_type' => [
                'type'       => 'ENUM',
                'constraint' => [
                    'earning',
                    'payout',
                    'manual'
                ],
            ],

            'source_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
                'comment'    => 'partner_earnings.id or payout.id',
            ],

            'amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
            ],

            'is_credit' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'comment'    => '1 = credit, 0 = debit',
            ],

            'note' => [
                'type' => 'TEXT',
                'null' => true,
            ],

            'created_at' => [
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

        $this->forge->createTable('partner_wallet_transactions', true);
    }

    public function down()
    {
        $this->forge->dropTable('partner_wallet_transactions', true);
    }
}
