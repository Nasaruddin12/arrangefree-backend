<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateChannelPartnersTable extends Migration
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
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'company_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'mobile' => [
                'type'       => 'VARCHAR',
                'constraint' => 15,
            ],
            'password' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'otp' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'otp_expires_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'email_verified' => [
                'type'    => 'BOOLEAN',
                'default' => false,
            ],
            'mobile_verified' => [
                'type'    => 'BOOLEAN',
                'default' => false,
            ],
            'is_logged_in' => [
                'type'    => 'BOOLEAN',
                'default' => false,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'active', 'inactive', 'blocked'],
                'default'    => 'pending',
            ],
            'fcm_token' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
            ],
            'last_login_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('email');
        $this->forge->addUniqueKey('mobile');
        $this->forge->createTable('channel_partners');
    }

    public function down()
    {
        $this->forge->dropTable('channel_partners', true);
    }
}
