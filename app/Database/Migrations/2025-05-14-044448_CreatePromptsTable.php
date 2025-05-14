<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePromptsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'        => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'style_id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'null'           => false,
            ],
            'prompt'    => [
                'type'           => 'TEXT',
                'null'           => false,
            ],
            'image_path' => [
                'type'           => 'VARCHAR',
                'constraint'     => '255',
                'null'           => true,
                'comment'        => 'Path to the generated image',
            ],
            'created_at' => [
                'type'           => 'DATETIME',
                'null'           => true,
            ],
            'updated_at' => [
                'type'           => 'DATETIME',
                'null'           => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('style_id', 'styles', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('prompts');
    }

    public function down()
    {
        $this->forge->dropTable('prompts');
    }
}
