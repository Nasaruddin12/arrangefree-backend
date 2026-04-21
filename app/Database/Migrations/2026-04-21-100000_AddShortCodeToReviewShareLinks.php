<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddShortCodeToReviewShareLinks extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('review_share_links') || $this->db->fieldExists('code', 'review_share_links')) {
            return;
        }

        $this->forge->addColumn('review_share_links', [
            'code' => [
                'type'       => 'VARCHAR',
                'constraint' => 24,
                'null'       => true,
                'after'      => 'token_hash',
            ],
        ]);

        $this->db->query('ALTER TABLE `review_share_links` ADD UNIQUE KEY `uniq_review_share_links_code` (`code`)');
    }

    public function down()
    {
        if (!$this->db->tableExists('review_share_links') || !$this->db->fieldExists('code', 'review_share_links')) {
            return;
        }

        $this->db->query('ALTER TABLE `review_share_links` DROP INDEX `uniq_review_share_links_code`');
        $this->forge->dropColumn('review_share_links', 'code');
    }
}
