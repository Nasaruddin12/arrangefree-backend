<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AfAdmin extends Migration
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
                'constraint' => 11,
                'unsigned' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 101,
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => 101,
            ],
            'mobile_no' => [
                'type' => 'VARCHAR',
                'constraint' => 15,
                'unique' => true,
            ],
            'password' => [
                'type' => 'VARCHAR',
                'constraint' => 256,
            ],
            
            'is_logged_in' => [
                'type' => 'boolean',
                'null' => false,
                'default' => 0,
            ],
            'otp' => [
                'type' => 'VARCHAR',
                'constraint' => '101',
            ],
            'status' => [
                'type' => 'TINYINT',
                'null' => false,
                'default' => -1,
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('af_admins');
    }

    public function down()
    {
        $this->forge->dropTable('af_admins');

    }
}
