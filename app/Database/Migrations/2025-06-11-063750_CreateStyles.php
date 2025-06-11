<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateStyles extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'               => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'name'             => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'styles_category'  => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'image'            => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'status'           => [
                'type'       => 'ENUM',
                'constraint' => ['active', 'inactive'],
                'default'    => 'active',
            ],
            'created_at'       => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at'       => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('styles_category', 'styles_category', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('styles');
    }

    public function down()
    {
        $this->forge->dropTable('styles');
    }
}
