<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTransactionsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'quotation_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true, // Optional, in case not tied to a specific site
            ],
            'transaction_type' => [
                'type' => 'ENUM',
                'constraint' => ['Income', 'Expense'],
                'default' => 'Expense',
            ],
            'category' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true, // E.g., Material, Labour, Sales, Services
            ],
            'amount' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => false,
            ],
            'payment_method' => [
                'type' => 'ENUM',
                'constraint' => ['Cash', 'Online', 'Cheque', 'Other'],
                'default' => 'Other',
            ],
            'transaction_no' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'vendor_or_client' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true, // Stores vendor name for expenses or client name for income
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'date' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'remarks' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('quotation_id', 'quotations', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('interior_transactions');
    }

    public function down()
    {
        $this->forge->dropTable('interior_transactions');
    }
}
