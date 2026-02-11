<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AfOffers extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'offer_group' => [
                'type' => 'VARCHAR',
                'constraint' => '101',
            ],
            'offer_title' => [
                'type' => 'VARCHAR',
                'constraint' => '256',
            ],
            'offer_link' => [
                'type' => 'VARCHAR',
                'constraint' => '256',
            ],
            'offer_start_date' => [
                'type' => 'VARCHAR',
                'constraint' => '101',
            ],
            'offer_end_date' => [
                'type' => 'VARCHAR',
                'constraint' => '101',
            ],
            'offer_mobile_path' => [
                'type' => 'VARCHAR',
                'constraint' => '256',
            ],
            'offer_web_path' => [
                'type' => 'VARCHAR',
                'constraint' => '256',
            ],
            'offer_index' => [
                'type' => 'INT',
                'constraint' => '11',
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('offers');
    }

    public function down()
    {
        $this->forge->dropTable('offers');

    }
}
