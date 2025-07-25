<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePartnerPayouts extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'auto_increment' => true],
            'partner_id'         => ['type' => 'INT', 'null' => false],
            'booking_service_id' => ['type' => 'INT', 'null' => false],
            'amount'             => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => false],
            'status'             => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'pending'], // pending, released, failed
            'released_at'        => ['type' => 'DATETIME', 'null' => true],
            'notes'              => ['type' => 'TEXT', 'null' => true],
            'created_at'         => ['type' => 'DATETIME', 'null' => true],
            'updated_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('partner_payouts');
    }

    public function down()
    {
        $this->forge->dropTable('partner_payouts');
    }
}
