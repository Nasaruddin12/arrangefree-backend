<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePartnerReferralInvites extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'auto_increment' => true,
                'unsigned'       => true,
            ],
            'referrer_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'friend_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'friend_mobile' => [
                'type'       => 'VARCHAR',
                'constraint' => 15,
            ],
            'referral_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'is_registered' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
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
        $this->forge->addForeignKey('referrer_id', 'partners', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('partner_referral_invites');
    }

    public function down()
    {
        $this->forge->dropTable('partner_referral_invites');
    }
}
