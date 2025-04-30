<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePartnerBankDetails extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                   => ['type' => 'INT', 'auto_increment' => true],
            'partner_id'           => ['type' => 'INT'],
            'account_holder_name'  => ['type' => 'VARCHAR', 'constraint' => 100],
            'bank_name'            => ['type' => 'VARCHAR', 'constraint' => 100],
            'bank_branch'          => ['type' => 'VARCHAR', 'constraint' => 100],
            'account_number'       => ['type' => 'VARCHAR', 'constraint' => 50],
            'ifsc_code'            => ['type' => 'VARCHAR', 'constraint' => 20],
            'bank_document'        => ['type' => 'TEXT'],
            'status'               => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'verified', 'rejected'],
                'default'    => 'pending',
            ],
            'rejection_reason'  => ['type' => 'TEXT', 'null' => true],
            'verified_by'          => ['type' => 'INT', 'null' => true],
            'verified_at'          => ['type' => 'DATETIME', 'null' => true],
            'created_at'           => ['type' => 'DATETIME', 'null' => true],
            'updated_at'           => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('partner_id', 'partners', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('partner_bank_details');
    }

    public function down()
    {
        $this->forge->dropTable('partner_bank_details');
    }
}
