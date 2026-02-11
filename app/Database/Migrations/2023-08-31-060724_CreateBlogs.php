<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AfBlogs extends Migration
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
            'type' => 'TEXT',
        ],
        'status' => [
            'type' => 'TINYINT',
            'null' => false,
            'default' => -1,
        ],
        'blog_image' => [
            'type' => 'VARCHAR',
            'constraint' => 256
        ],
        'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
        'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
    ]);

    $this->forge->addKey('id', true);
    $this->forge->createTable('blogs');
    }

    public function down()
    {
        $this->forge->dropTable('blogs');
    }
}
