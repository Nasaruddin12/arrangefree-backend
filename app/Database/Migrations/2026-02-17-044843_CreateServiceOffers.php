<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateServiceOffers extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],

            'discount_type' => [
                'type'       => 'ENUM',
                'constraint' => ['percentage', 'flat'],
            ],

            'discount_value' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
            ],

            'start_date' => [
                'type' => 'DATETIME',
                'null' => true,
            ],

            'end_date' => [
                'type' => 'DATETIME',
                'null' => true,
            ],

            'priority' => [
                'type'       => 'INT',
                'constraint' => 5,
                'default'    => 1,
            ],

            'is_active' => [
                'type'    => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],

            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP',
            'deleted_at DATETIME NULL',
        ]);

        $this->forge->addKey('id', true);

        // Optional indexes for performance
        $this->forge->addKey('service_id');
        $this->forge->addKey('category_id');
        $this->forge->addKey('is_active');

        $this->forge->createTable('service_offers');
    }

    public function down()
    {
        $this->forge->dropTable('service_offers');
    }
}
