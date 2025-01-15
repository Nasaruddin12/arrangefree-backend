<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AfTransactions extends Migration
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
            'razorpay_payment_id' => [
                'type' => 'VARCHAR',
                'constraint' => 101
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 51,
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('af_transactions');
    }

    public function down()
    {
        $this->forge->dropTable('af_transactions');
    }
}