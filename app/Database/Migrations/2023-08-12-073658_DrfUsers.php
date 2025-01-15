<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DrfUsers extends Migration
{
    public function up()
    {
        $this->forge->addField([

            'id' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type' => 'VARCHAR',
                'constraint' => 101,
                // 'unsigned' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 101,
            ],
            'password' => [
                'type' => 'VARCHAR',
                'constraint' => 101,
            ],
            'role_id' => [
                'type' => 'INT',
                'constraint' => 11  // Assuming 1-digit roles
                // 'default' => 2,     // Default role for users
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('drf_users');
    }

    public function down()
    {
        $this->forge->dropTable('drf_users');

    }
}
