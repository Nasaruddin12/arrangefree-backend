<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DrfIndependenceCampaign extends Migration
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
            'constraint' => 101,
        ],
        'email' => [
            'type' => 'VARCHAR',
            'constraint' => 101,
        ],
        'mobile_no' => [
            'type' => 'VARCHAR',
            'constraint' => 15,
            'unique' => true,
        ],
        'address' => [
            'type' => 'VARCHAR',
            'constraint' => 255,
            'null' => true,
        ],
        'image' => [
            'type' => 'VARCHAR',
            'constraint' => 255,
            // 'null' => true,
        ],
        'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
        'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
    ]);

    $this->forge->addKey('id', true);
    $this->forge->createTable('drf_independence_campaign');
    }

    public function down()
    {
        $this->forge->dropTable('drf_independence_campaign');

    }
}
