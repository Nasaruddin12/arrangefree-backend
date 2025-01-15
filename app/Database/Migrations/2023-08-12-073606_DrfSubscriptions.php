<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DrfSubscriptions extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'vendor_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'subscription_amount' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'amount_payed' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            
            'subscription_date' => [
                'type' => 'VARCHAR',
                'constraint' => 101,
            ],
            'subscription_pdf' => [
                'type' => 'VARCHAR',
                'constraint' => 101,
            ],

            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('drf_subscriptions');
    }

    public function down()
    {
        $this->forge->dropTable('drf_subscriptions');

    }
}
