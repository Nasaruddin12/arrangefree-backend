<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AfProductsAttributesValue extends Migration
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
            'attribute_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'attribute_value' => [
                'type' => 'VARCHAR',
                'constraint' => 101,
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('attribute_id', 'af_products_attributes', 'id', 'null', 'CASCADE', 'fk_attribute');
        $this->forge->createTable('af_products_attribute_value');
    }

    public function down()
    {
        $this->forge->dropTable('af_products_attribute_value');
    }
}
