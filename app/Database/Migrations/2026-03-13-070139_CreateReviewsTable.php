<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateReviewsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([

            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],

            'review_type' => [
                'type'       => 'ENUM',
                'constraint' => ['service', 'partner', 'booking'],
                'default'    => 'service',
            ],

            'booking_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],

            'service_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],

            'partner_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],

            'user_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],

            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],

            'rating' => [
                'type'       => 'DECIMAL',
                'constraint' => '2,1',
            ],

            'review' => [
                'type' => 'TEXT',
                'null' => true,
            ],

            'is_verified' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],

            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'approved', 'rejected'],
                'default'    => 'pending',
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

        $this->forge->addKey('booking_id');
        $this->forge->addKey('service_id');
        $this->forge->addKey('partner_id');
        $this->forge->addKey('user_id');

        $this->forge->createTable('reviews');
    }

    public function down()
    {
        $this->forge->dropTable('reviews');
    }
}
