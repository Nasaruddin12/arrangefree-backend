<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePartnerReferrals extends Migration
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
            'referrer_id' => [
                'type'     => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'referee_id' => [
                'type'     => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'bonus_month' => [
                'type'       => 'CHAR',
                'constraint' => 7, // YYYY-MM
            ],
            'tasks_completed' => [
                'type'       => 'INT',
                'default' => 0,
            ],
            'required_tasks' => [
                'type'    => 'INT',
                'default' => 2,
            ],
            'is_eligible' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'bonus_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => 0,
            ],
            'bonus_status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'approved', 'paid', 'rejected'],
                'default'    => 'pending',
            ],
            'paid_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'paid_txn_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
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
        $this->forge->addKey('referrer_id');
        $this->forge->addKey('referee_id');
        $this->forge->addUniqueKey(['referee_id', 'bonus_month']);

        // Add FKs here (no raw ALTER TABLE needed)
        $this->forge->addForeignKey('referrer_id', 'partners', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('referee_id',  'partners', 'id', 'RESTRICT', 'CASCADE');

        $this->forge->createTable('partner_referrals', true, [
            'ENGINE' => 'InnoDB',
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('partner_referrals', true);
    }
}
