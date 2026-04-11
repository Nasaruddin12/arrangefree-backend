<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateChannelPartnerLeadsTable extends Migration
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
            'channel_partner_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'customer_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'mobile' => [
                'type'       => 'VARCHAR',
                'constraint' => 15,
            ],
            'address' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'requirement_title' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'space_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'budget' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['new', 'in_progress', 'contacted', 'converted', 'rejected'],
                'default'    => 'new',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('channel_partner_id');
        $this->forge->addKey('mobile');
        $this->forge->addKey('status');
        $this->forge->addForeignKey('channel_partner_id', 'channel_partners', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('channel_partner_leads', true);
    }

    public function down()
    {
        $this->forge->dropTable('channel_partner_leads', true);
    }
}
