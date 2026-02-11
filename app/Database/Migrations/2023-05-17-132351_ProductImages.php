<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ProductImages extends Migration
{
    public function up()
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
            'path_1620x1620' => [
                'type' => 'VARCHAR',
                'constraint' => 101
            ],
            'path_580x580' => [
                'type' => 'VARCHAR',
                'constraint' => 101
            ],
            'path_360x360' => [
                'type' => 'VARCHAR',
                'constraint' => 101
            ],
            'image_index' => [
                'type' => 'INT',
                'constraint' => 11
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('af_product_images'); //dsfs
    }

    public function down()
    {
        $this->forge->dropTable('af_product_images');
    }
}
