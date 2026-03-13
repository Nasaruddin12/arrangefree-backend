<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateServiceReviewSummaryTable extends Migration
{
    public function up()
    {
        $this->forge->addField([

            'service_id' => [
                'type'       => 'INT',
                'unsigned'   => true,
            ],

            'avg_rating' => [
                'type'       => 'DECIMAL',
                'constraint' => '3,2',
                'default'    => 0,
            ],

            'total_reviews' => [
                'type'       => 'INT',
                'default'    => 0,
            ],

            'rating_1' => [
                'type' => 'INT',
                'default' => 0
            ],

            'rating_2' => [
                'type' => 'INT',
                'default' => 0
            ],

            'rating_3' => [
                'type' => 'INT',
                'default' => 0
            ],

            'rating_4' => [
                'type' => 'INT',
                'default' => 0
            ],

            'rating_5' => [
                'type' => 'INT',
                'default' => 0
            ],

            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true
            ],
        ]);

        $this->forge->addKey('service_id', true);

        $this->forge->addForeignKey(
            'service_id',
            'services',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->forge->createTable('service_review_summary');
    }

    public function down()
    {
        $this->forge->dropTable('service_review_summary');
    }
}