<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTicketMessages extends Migration
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
            'ticket_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            'sender_type' => [
                'type'       => 'ENUM',
                'constraint' => ['customer', 'partner', 'admin'],
                'null'       => false,
            ],
            'sender_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            'message' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'file' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'is_read_by_admin' => [
                'type' => 'BOOLEAN',
                'default' => false,
            ],

            'is_read_by_user' => [
                'type' => 'BOOLEAN',
                'default' => false,
            ],
            'message_type' => [
                'type' => 'ENUM',
                'constraint' => ['text', 'file', 'system'],
                'default' => 'text',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('ticket_id', 'tickets', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('ticket_messages');
    }

    public function down()
    {
        $this->forge->dropTable('ticket_messages');
    }
}
