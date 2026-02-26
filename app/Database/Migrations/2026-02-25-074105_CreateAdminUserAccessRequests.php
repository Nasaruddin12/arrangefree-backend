<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAdminUserAccessRequests extends Migration
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
            'access_type' => [
                'type' => 'ENUM',
                'constraint' => ['cart', 'orders', 'full'],
                'default' => 'cart',
            ],
            'reason' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['pending', 'approved', 'rejected', 'expired'],
                'default' => 'pending',
            ],
            'expires_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'approved_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('admin_id');
        $this->forge->addKey('user_id');

        $this->forge->createTable('admin_user_access_requests');
    }

    public function down()
    {
        $this->forge->dropTable('admin_user_access_requests');
    }
}