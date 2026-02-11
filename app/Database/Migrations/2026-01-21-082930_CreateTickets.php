<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTickets extends Migration
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
            'ticket_uid' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'unique'     => true,
                'null'       => false,
                'comment'    => 'Unique Ticket Identifier',
            ],
            'user_type' => [
                'type'       => 'ENUM',
                'constraint' => ['customer', 'partner'],
                'default'    => 'customer',
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'Customer user_id',
            ],
            'partner_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'Only if partner is creating',
            ],
            'booking_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'partner_job_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'Related partner execution job',
            ],

            'subject' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'file' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['open', 'in_progress', 'closed'],
                'default'    => 'open',
                'null'       => false,
            ],
            'priority' => [
                'type'       => 'ENUM',
                'constraint' => ['low', 'medium', 'high'],
                'default'    => 'low',
                'null'       => false,
            ],
            'category' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
                'default'    => 'general',
            ],
            'assigned_admin_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'admin_unread' => [
                'type'    => 'BOOLEAN',
                'default' => true,
                'null'    => false,
                'comment' => "true means admin hasn't seen this ticket",
            ],
            'last_admin_viewed_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'When admin last opened the ticket',
            ],

            'closed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],

            'created_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
            ],
            'updated_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],

        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('status');
        $this->forge->addKey('priority');
        $this->forge->addKey('assigned_admin_id');
        $this->forge->addKey('created_at');

        $this->forge->addForeignKey('user_id', 'customers', 'id', 'SET NULL', 'SET NULL');
        $this->forge->addForeignKey('partner_id', 'partners', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('booking_id', 'bookings', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey(
            'partner_job_id',
            'partner_jobs',
            'id',
            'SET NULL',
            'CASCADE'
        );
        $this->forge->addForeignKey(
            'assigned_admin_id',
            'admins',
            'id',
            'SET NULL',
            'CASCADE'
        );

        // updated FK
        $this->forge->createTable('tickets');
    }

    public function down()
    {
        $this->forge->dropTable('tickets');
    }
}
