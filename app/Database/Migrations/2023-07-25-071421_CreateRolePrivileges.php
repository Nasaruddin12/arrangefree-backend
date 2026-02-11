<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AfRolePrivileges extends Migration
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
                'constraint' => '21',
            ],
            'section_access' => [
                'type' => 'VARCHAR',
                'constraint' => '501',
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('role_privileges');
    }

    public function down()
    {
        $this->forge->dropTable('role_privileges');
    }
}
