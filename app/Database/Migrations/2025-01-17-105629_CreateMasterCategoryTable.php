<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMasterCategoryTable extends Migration
{
    public function up()
    {
        // Create 'master_category' table
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'title' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('master_category');
    }

    public function down()
    {
        $this->forge->dropTable('master_category');
    }
}
