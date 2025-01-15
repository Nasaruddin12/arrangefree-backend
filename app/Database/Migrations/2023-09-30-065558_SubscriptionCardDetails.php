<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SubscriptionCardDetails extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'card_id' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'details' => [
                'type' => 'VARCHAR',
                'constraint' => 101,
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('subscription_card_details');
    }

    public function down()
    {
        $this->forge->dropTable('subscription_card_details');

    }

}
