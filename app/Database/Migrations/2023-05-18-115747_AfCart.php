<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AfCart extends Migration
{
    public function up()
    {
        $this->forge->addField('id');
        $this->forge->addField([
            'product_id' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'customer_id' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'quantity' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('af_cart');
    }

    public function down()
    {
        $this->forge->dropTable('af_cart');
    }
}
