<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePartnerDocuments extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'auto_increment' => true, 'constraint' => 11, 'unsigned' => true],
            'partner_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'type'         => ['type' => 'VARCHAR', 'constraint' => 50], // e.g. aadhar_front, pan_card
            'file_path'    => ['type' => 'TEXT'],
            'status'       => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'verified', 'rejected'],
                'default'    => 'pending',
            ],
            'rejection_reason'  => ['type' => 'TEXT', 'null' => true],
            'reviewed_by'  => ['type' => 'INT', 'null' => true],
            'reviewed_at'  => ['type' => 'DATETIME', 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('partner_id', 'partners', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('partner_documents');
    }

    public function down()
    {
        $this->forge->dropTable('partner_documents');
    }
}
