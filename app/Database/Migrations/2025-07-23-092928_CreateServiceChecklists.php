<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateServiceChecklists extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'auto_increment' => true],
            'service_id'  => ['type' => 'INT', 'null' => false],
            'title'       => ['type' => 'VARCHAR', 'constraint' => 255],
            'is_required' => ['type' => 'BOOLEAN', 'default' => true],
            'sort_order'  => ['type' => 'INT', 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('service_checklists');
    }

    public function down()
    {
        $this->forge->dropTable('service_checklists');
    }
}
