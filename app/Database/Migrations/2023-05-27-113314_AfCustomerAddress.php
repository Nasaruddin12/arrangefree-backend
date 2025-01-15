<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AfCustomerAddress extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'customer_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'first_name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'last_name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'phone' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
            ],
            'street_address' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'city' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'state' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'country' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'pincode' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
            ],
            'address_label' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('af_customer_address');
    }

    public function down()
    {
        $this->forge->dropTable('af_customer_address');
    }
}
