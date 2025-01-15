<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AfProductVariations extends Migration
{
    public function up()
    {
        {
            $this->forge->addField([
                'id' => [
                    'type' => 'INT',
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'product_id' => [
                    'type' => 'INT',
                    'unsigned' => true,
                ],
                'variation_type' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                ],
                'actual_price' => [
                    'type' => 'INT',
                    'constraint' => '101',
                ],
                'discount' => [
                    'type' => 'INT',
                    'constraint' => '101',
                ],
                'quantity_left' => [
                    'type' => 'INT',
                    'unsigned' => true,
                ],
                'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
                'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            ]);
            $this->forge->addPrimaryKey('id');
            // $this->forge->addForeignKey('product_id', 'products', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('af_product_variations');
        }
    }

    public function down()
    {
        $this->forge->dropTable('af_product_variations');

    }
}
