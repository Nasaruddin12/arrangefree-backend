<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AfSubscriptionCards extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'title' => [
                'type' => 'VARCHAR',
                'constraint' => 250,
            ],
            'description' => [
                'type' => 'VARCHAR',
                'constraint' => 550,
            ],
            'benefits' => [
                'type' => 'MEDIUMTEXT',
            ],
            'is_deleted' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('subscription_cards');
    }

    public function down()
    {
        $this->forge->dropTable('subscription_cards');

    }

}
