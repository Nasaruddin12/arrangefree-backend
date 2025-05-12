<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateServiceAddonsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'auto_increment' => true],
            'service_id'    => ['type' => 'INT'],
            'group_name'    => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'is_required'   => ['type' => 'BOOLEAN', 'default' => false],
            'name'          => ['type' => 'VARCHAR', 'constraint' => 100],
            'price_type'    => ['type' => 'ENUM', 'constraint' => ['unit', 'square_feet'], 'default' => 'unit'],
            'qty'           => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 1],
            'price'         => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'description'   => ['type' => 'TEXT', 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('service_addons');
    }

    public function down()
    {
        $this->forge->dropTable('service_addons');
    }
}
