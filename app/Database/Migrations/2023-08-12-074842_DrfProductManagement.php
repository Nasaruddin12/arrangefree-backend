<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DrfProductManagement extends Migration
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
            'user_id' => [
                'type' => 'INT',
                'unsigned' => true,
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);
    
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('drf_product_management');
    }

    public function down()
    {
        $this->forge->dropTable('drf_product_management');

    }
}
