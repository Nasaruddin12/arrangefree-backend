<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AfInvoice extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'auto_increment' => true,
                'unsigned' => true,
            ],
            'invoice_number' => [
                'type' => 'VARCHAR',
                'constraint' => 51,
            ],
            'invoice_path' => [
                'type' => 'VARCHAR',
                'constraint' => 101,
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('af_invoices');
    }

    public function down()
    {
        $this->forge->dropTable('af_invoices');
    }
}
