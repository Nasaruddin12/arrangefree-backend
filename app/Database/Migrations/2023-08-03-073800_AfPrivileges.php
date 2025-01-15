<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AfPrivileges extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'role_id' => [
                'type' => 'INT',
            ],
            'section_id' =>
            [
                'type' => 'INT',
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('af_privileges');
    }

    public function down()
    {
        $this->forge->dropTable('af_privileges');
    }
}
