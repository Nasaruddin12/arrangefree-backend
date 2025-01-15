<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AfSubcribedUser extends Migration
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
            'user_id' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'transaction_id' => [
                'type' => 'VARCHAR',
                'constraint' => 101,
            ],
            'start_date' => [
                'type' => 'VARCHAR',
                'constraint' => 21,
            ],
            'renewal_date' => [
                'type' => 'VARCHAR',
                'constraint' => 21,
            ],
            'subscription_amount' => [
                'type' => 'VARCHAR',
                'constraint' => 11,
            ],
            'subscription_type' => [
                'type' => 'VARCHAR',
                'constraint' => 101,
            ],
            'card_number' => [
                'type' => 'INT',
                'constraint' => 21,
            ],
            'status' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('subscribed_users');
    }

    public function down()
    {
        $this->forge->dropTable('subscribed_users');

    }
}
