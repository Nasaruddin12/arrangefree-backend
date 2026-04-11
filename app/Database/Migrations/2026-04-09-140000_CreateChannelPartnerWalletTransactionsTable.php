<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateChannelPartnerWalletTransactionsTable extends Migration
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
            'channel_partner_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'source_type' => [
                'type'       => 'ENUM',
                'constraint' => ['earning', 'withdrawal', 'manual', 'refund'],
            ],
            'source_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
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
        $this->forge->addForeignKey('channel_partner_id', 'channel_partners', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('channel_partner_wallet_transactions', true);
    }

    public function down()
    {
        $this->forge->dropTable('channel_partner_wallet_transactions', true);
    }
}
