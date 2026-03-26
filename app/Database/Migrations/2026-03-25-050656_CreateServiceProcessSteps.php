<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateServiceProcessSteps extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'service_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'step_title' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'step_description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'step_order' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'estimated_time' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'icon' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['active', 'inactive'],
                'default'    => 'active',
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('step_order');

        // Optional FK (only if services table exists)
        $this->forge->addForeignKey('service_id', 'services', 'id', 'CASCADE', 'CASCADE');

        $this->forge->createTable('service_process_steps');
    }

    public function down()
    {
        $this->forge->dropTable('service_process_steps');
    }
}