<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateServiceAddonsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'auto_increment' => true, 'constraint' => 11, 'unsigned' => true],
            'service_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'group_name'    => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'is_required'   => ['type' => 'BOOLEAN', 'default' => false],
            'name'          => ['type' => 'VARCHAR', 'constraint' => 100],
            'price_type'    => ['type' => 'ENUM', 'constraint' => ['unit', 'square_feet'], 'default' => 'unit'],
            'qty'           => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 1],
            'price'         => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'partner_price'  => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true],
            'description'   => ['type' => 'TEXT', 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('service_id');
        $this->forge->addForeignKey('service_id', 'services', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('service_addons');
    }

    public function down()
    {
        // $this->forge->dropForeignKey('service_addons', 'service_id');
        $this->forge->dropTable('service_addons');
    }
}
