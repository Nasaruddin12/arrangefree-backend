<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DrfProductRawImage extends Migration
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
        'path' => [
            'type' => 'VARCHAR',
            'constraint' => 256
        ],
        'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
        'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
    ]);

    $this->forge->addPrimaryKey('id');
    $this->forge->createTable('drf_product_raw_image');
    }

    public function down()
    {
        $this->forge->dropTable('drf_product_raw_image');

    }
}
