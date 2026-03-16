<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateReviewVotesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([

            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],

            'review_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],

            'user_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],

            'vote' => [
                'type'       => 'ENUM',
                'constraint' => ['helpful', 'not_helpful'],
                'default'    => 'helpful',
            ],

            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);

        $this->forge->addKey('review_id');
        $this->forge->addKey('user_id');
        $this->forge->addUniqueKey(['review_id', 'user_id'], 'uniq_review_user_vote');

        $this->forge->addForeignKey(
            'review_id',
            'reviews',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->forge->createTable('review_votes');
    }

    public function down()
    {
        $this->forge->dropTable('review_votes');
    }
}
