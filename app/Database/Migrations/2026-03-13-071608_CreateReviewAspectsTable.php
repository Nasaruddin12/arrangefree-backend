<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateReviewAspectsTable extends Migration
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

            'aspect' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],

            'rating' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
            ],

            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('review_id');
        $this->forge->addUniqueKey(['review_id', 'aspect'], 'uniq_review_aspect');

        $this->forge->addForeignKey(
            'review_id',
            'reviews',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->forge->createTable('review_aspects');
    }

    public function down()
    {
        $this->forge->dropTable('review_aspects');
    }
}
