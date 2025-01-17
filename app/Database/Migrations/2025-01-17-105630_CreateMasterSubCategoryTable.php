<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMasterSubCategoryTable extends Migration
{
    public function up()
    {
        // Create 'master_subcategory' table
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'master_category_id' => [
                'type' => 'INT',
                'unsigned' => true,
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
        $this->forge->addForeignKey('master_category_id', 'master_category', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('master_subcategory');
    }

    public function down()
    {
        $this->forge->dropTable('master_subcategory');
    }
}
