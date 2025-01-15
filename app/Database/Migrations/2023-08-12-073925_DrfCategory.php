<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DrfCategory extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],

            'title' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'description' => [
                'type' => 'VARCHAR',
                'constraint' => 999,
            ],
            'features' => [
                'type' => 'VARCHAR',
                'constraint' => 999,
            ],
            'slug' => [
                'type' => 'VARCHAR',
                'constraint' => 101,
            ],
            'image' => [
                'type' => 'VARCHAR',
                'constraint' => 256,
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('drf_category');
    }

    public function down()
    {
        $this->forge->dropTable('drf_category');

    }
}