<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCustomerAddresses extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true
            ],

            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],

            'house' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],

            'address' => [
                'type' => 'TEXT',
            ],

            'landmark' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],

            'city' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],

            'state' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],

            'pincode' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
            ],

            'latitude' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,7',
                'null'       => true,
            ],

            'longitude' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,7',
                'null'       => true,
            ],

            'address_label' => [
                'type'       => 'VARCHAR',
                'constraint' => 55,
                'default'    => 'Home',
            ],

            'is_default' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],

            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
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

        // KEYS
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('user_id');
        $this->forge->addKey('pincode');
        $this->forge->addKey('deleted_at');

        // FK (SAFE)
        $this->forge->addForeignKey(
            'user_id',
            'af_customers',
            'id',
            'RESTRICT',
            'CASCADE'
        );

        $this->forge->createTable('customer_addresses');
    }

    public function down()
    {
        $this->forge->dropTable('customer_addresses', true);
    }
}
