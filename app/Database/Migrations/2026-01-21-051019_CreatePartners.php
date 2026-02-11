<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePartners extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true, 'constraint' => 11],
            'name'               => ['type' => 'VARCHAR', 'constraint' => 100],
            'mobile'             => ['type' => 'VARCHAR', 'constraint' => 15],
            'email'              => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'mobile_verified'    => ['type' => 'BOOLEAN', 'default' => false],
            'dob'                => ['type' => 'DATE'],
            'gender'             => ['type' => 'VARCHAR', 'constraint' => 10],
            'emergency_contact'  => ['type' => 'VARCHAR', 'constraint' => 15],
            'profession'         => ['type' => 'VARCHAR', 'constraint' => 100],
            'team_size'          => ['type' => 'VARCHAR', 'constraint' => 15],
            'service_areas'      => ['type' => 'VARCHAR', 'constraint' => 255],
            'aadhaar_no'         => ['type' => 'VARCHAR', 'constraint' => 20],
            'pan_no'             => ['type' => 'VARCHAR', 'constraint' => 20],
            'documents_verified' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'verified', 'rejected'],
                'default'    => 'pending',
            ],
            'bank_verified' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'verified', 'rejected'],
                'default'    => 'pending',
            ],
            'verified_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'verified_at' => ['type' => 'DATETIME', 'null' => true],
            'status'      => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'active', 'blocked', 'terminated', 'resigned', 'rejected'],
                'default'    => 'pending',
            ],
            'referral_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 12,
                'null'       => true,
                'unique'     => true,
            ],
            'referred_by_partner_id' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'null'       => true,
            ],
            'fcm_token' => ['type' => 'TEXT', 'null' => true],
            'firebase_uid' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('referred_by_partner_id', 'partners', 'id', 'RESTRICT', 'CASCADE');
        // Optional: add this if you have an `admins` or `users` table later
        $this->forge->createTable('partners');
    }

    public function down()
    {
        $this->forge->dropTable('partners', true);
    }
}
