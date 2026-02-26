<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAdminUserImpersonationSessions extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'admin_id' => [
                'type' => 'INT',
                'unsigned' => true,
            ],
            'user_id' => [
                'type' => 'INT',
                'unsigned' => true,
            ],
            'access_request_id' => [
                'type' => 'INT',
                'unsigned' => true,
            ],
            'token' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'expires_at' => [
                'type' => 'DATETIME',
            ],
            'is_active' => [
                'type' => 'BOOLEAN',
                'default' => true,
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('admin_id');
        $this->forge->addKey('user_id');
        $this->forge->addKey('token');

        $this->forge->createTable('admin_user_impersonation_sessions');
    }

    public function down()
    {
        $this->forge->dropTable('admin_user_impersonation_sessions');
    }
}