<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CampaignsData extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],

            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'customer_name' => [
                'type' => 'VARCHAR',
                'constraint' => 101
            ],
            'customer_mobile_no' => [
                'type' => 'INT',
                'constraint' => 101
            ],
            'insta_handle' => [
                'type' => 'VARCHAR',
                'constraint' => 101
            ],
            'score' => [
                'type' => 'INT',
                'constraint' => 101
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->createTable('af_campaigns_data');
    }

    public function down()
    {
        $this->forge->dropTable('af_campaigns_data');

    }
}