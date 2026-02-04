<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePartnerAddressesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
                'constraint'     => 11,
            ],
            'partner_id' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'null'       => false,
                'constraint' => 11,
            ],
            'address_line_1' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'address_line_2' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'landmark' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'pincode' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
            ],
            'city' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'state' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'country' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'default'    => 'India',
            ],
            'is_primary' => [
                'type'    => 'BOOLEAN',
                'default' => false,
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
        $this->forge->addForeignKey('partner_id', 'partners', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('partner_addresses');
    }

    public function down()
    {
        $this->forge->dropTable('partner_addresses');
    }
}
