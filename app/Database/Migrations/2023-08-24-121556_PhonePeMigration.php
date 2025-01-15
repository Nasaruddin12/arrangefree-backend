<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class PhonePeMigration extends Migration
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
            'merchantTransactionId' => [
                'type' => 'VARCHAR',
                'constraint' => 250,
                'collation' => 'utf8mb4_general_ci',
                'null' => true,
            ],
            'transactionId' => [
                'type' => 'VARCHAR',
                'constraint' => 250,
                'collation' => 'utf8mb4_general_ci',
            ],
            'payment_status' => [
                'type' => 'VARCHAR',
                'constraint' => 150,
                'collation' => 'utf8mb4_general_ci',
            ],
            'transation_status' => [
                'type' => 'VARCHAR',
                'constraint' => 250,
                'collation' => 'utf8mb4_general_ci',
            ],
            'amount' => [
                'type' => 'INT',
                'constraint' => 150,
            ],
            'form_json' => [
                'type' => 'VARCHAR',
                'constraint' => 3500,
                'collation' => 'utf8mb4_general_ci',
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('af_phonepe_transaction');
    }

    public function down()
    {
        $this->forge->dropTable('af_phonepe_transaction');
    }
}
