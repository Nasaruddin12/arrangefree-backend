<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePartnerOtps extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'auto_increment' => true
            ],
            'mobile' => [
                'type'       => 'VARCHAR',
                'constraint' => 15
            ],
            'otp' => [
                'type'       => 'VARCHAR',
                'constraint' => 6
            ],
            'expires_at' => [
                'type' => 'DATETIME'
            ],
            'otp_blocked_until' => [
                'type' => 'DATETIME',
                'null' => true
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true
            ]
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('partner_otps');
    }

    public function down()
    {
        $this->forge->dropTable('partner_otps');
    }
}
