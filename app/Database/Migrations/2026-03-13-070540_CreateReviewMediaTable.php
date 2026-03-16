<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateReviewMediaTable extends Migration
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

            'media_type' => [
                'type'       => 'ENUM',
                'constraint' => ['image', 'video'],
                'default'    => 'image',
            ],

            'media_url' => [
                'type'       => 'VARCHAR',
                'constraint' => 512,
            ],

            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],

        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('review_id');

        $this->forge->addForeignKey(
            'review_id',
            'reviews',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->forge->createTable('review_media');
    }

    public function down()
    {
        $this->forge->dropTable('review_media');
    }
}