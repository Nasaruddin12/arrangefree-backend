<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AfBrands extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'slug' => [
                'type' => 'VARCHAR',
                'constraint' => '101',
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('af_brands');
    }

    public function down()
    {
        $this->forge->dropTable('af_brands');
    }
}
