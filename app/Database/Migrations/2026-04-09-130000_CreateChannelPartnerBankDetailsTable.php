<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateChannelPartnerBankDetailsTable extends Migration
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
            'account_holder_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'bank_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'bank_branch' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'account_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'ifsc_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'upi_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'bank_document' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'verified', 'rejected'],
                'default'    => 'pending',
            ],
            'rejection_reason' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'verified_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'verified_at' => [
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
        $this->forge->addUniqueKey('channel_partner_id');
        $this->forge->addForeignKey('channel_partner_id', 'channel_partners', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('channel_partner_bank_details');
    }

    public function down()
    {
        $this->forge->dropTable('channel_partner_bank_details', true);
    }
}
