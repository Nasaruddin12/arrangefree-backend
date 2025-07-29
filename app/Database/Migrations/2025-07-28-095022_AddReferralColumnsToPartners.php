<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddReferralColumnsToPartners extends Migration
{
    public function up()
    {
        // Step 1: Add the two new columns using Forge
        $this->forge->addColumn('partners', [
            'referral_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 12,
                'null'       => true,
                'unique'     => true,
                'after'      => 'status', // adjust if needed
            ],
            'referred_by_partner_id' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'referral_code',
            ],
        ]);

        // Step 2: Add foreign key using raw SQL (required here)
        $this->db->query("
            ALTER TABLE `partners`
            ADD CONSTRAINT `fk_partners_referred_by`
            FOREIGN KEY (`referred_by_partner_id`) REFERENCES `partners`(`id`)
            ON DELETE SET NULL ON UPDATE CASCADE
        ");
    }

    public function down()
    {
        // Drop foreign key first
        $this->db->query("ALTER TABLE `partners` DROP FOREIGN KEY `fk_partners_referred_by`");

        // Then drop the columns
        $this->forge->dropColumn('partners', ['referral_code', 'referred_by_partner_id']);
    }
}
