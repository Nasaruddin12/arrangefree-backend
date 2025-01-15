<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DrfProducts extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'category_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'sub_category_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'vendor_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'actual_price' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
            ],
            'discounted_percent' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'null' => true,
            ],
            'height' => [
                'type' => 'float',
                'null' => true,
            ],
            'size' => [
                'type' => 'VARCHAR',
                'constraint' => 51,
                'null' => true,
            ],
            'warranty' => [
                'type' => 'VARCHAR',
                'constraint' => 51,
                'null' => true,
            ],
            'product_code' => [
                'type' => 'VARCHAR',
                'constraint' => 51,
                'null' => true,
            ],
            'features' => [
                'type' => 'VARCHAR',
                'constraint' => 999,
                'null' => true,
            ],
            'properties' => [
                'type' => 'VARCHAR',
                'constraint' => 999,
                'null' => true,
            ],
            'care_n_instructions' => [
                'type' => 'VARCHAR',
                'constraint' => 999,
                'null' => true,
            ],
            'quantity' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'warranty_details' => [
                'type' => 'VARCHAR',
                'constraint' => 999,
                'null' => true,
            ],
            'quality_promise' => [
                'type' => 'VARCHAR',
                'constraint' => 999,
                'null' => true,
            ],
            'status' => [
                'type' => 'SMALLINT',
                'null' => true,
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('drf_products');
    }


    public function down()
    {
        $this->forge->dropTable('drf_products');
    }
}
