<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AfProductsAttributes extends Migration
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
            'product_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
            ],
            'attribute_title' => [
                'type' => 'VARCHAR',
                'constraint' => 51,
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('af_products_attributes');
    }

    public function down()
    {
        $this->forge->dropTable('af_products_attributes');
    }
}
