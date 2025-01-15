<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AfOrderTable extends Migration
{
    public function up()
    {
        $this->forge->addField('id');
        $this->forge->addField([
            'user_id' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'order_number' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'total_amount' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
            ],
            'payment_method' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'shipping_address' => [
                'type' => 'TEXT',
            ],
            'billing_address' => [
                'type' => 'TEXT',
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('af_orderTable');
    }

    public function down()
    {
        $this->forge->dropTable('af_orderTable');

    }
}
