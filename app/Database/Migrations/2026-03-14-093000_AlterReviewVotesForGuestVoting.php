<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterReviewVotesForGuestVoting extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('review_votes', [
            'user_id' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => true,
            ],
        ]);

        $this->forge->addColumn('review_votes', [
            'guest_token' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'after' => 'user_id',
            ],
        ]);

        $this->db->query('ALTER TABLE `review_votes` DROP INDEX `uniq_review_user_vote`');
        $this->db->query('ALTER TABLE `review_votes` ADD UNIQUE KEY `uniq_review_user_vote` (`review_id`, `user_id`)');
        $this->db->query('ALTER TABLE `review_votes` ADD UNIQUE KEY `uniq_review_guest_vote` (`review_id`, `guest_token`)');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE `review_votes` DROP INDEX `uniq_review_guest_vote`');
        $this->db->query('ALTER TABLE `review_votes` DROP INDEX `uniq_review_user_vote`');
        $this->db->query('ALTER TABLE `review_votes` DROP COLUMN `guest_token`');

        $this->forge->modifyColumn('review_votes', [
            'user_id' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => false,
            ],
        ]);

        $this->db->query('ALTER TABLE `review_votes` ADD UNIQUE KEY `uniq_review_user_vote` (`review_id`, `user_id`)');
    }
}
