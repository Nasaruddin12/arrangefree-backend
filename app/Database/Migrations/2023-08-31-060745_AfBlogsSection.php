<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AfBlogsSection extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'blog_id' => [
                'type' => 'INT',
                'unsigned' => true,
            ],
            'title' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'description' => [
                'type' => 'TEXT',
            ],
            'banner_image' => [
                'type' => 'VARCHAR',
                'constraint' => 256
            ],
            'section_link' => [
                'type' => 'VARCHAR',
                'constraint' => 256
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);
    
        $this->forge->addKey('id', true);
        $this->forge->createTable('af_blogs_section');
    }

    public function down()
    {
        $this->forge->dropTable('af_blogs_section');
    }
}
