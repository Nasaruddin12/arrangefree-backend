<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBookingAddressesTable extends Migration
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

            'booking_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
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

            'created_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
            ],
            'updated_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
            ],

        ]);

        // KEYS
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('booking_id', false, true);  // one-to-one
        $this->forge->addKey('user_id');
        $this->forge->addKey('pincode');

        // FOREIGN KEYS (SAFE & INTENTIONAL)
        $this->forge->addForeignKey(
            'booking_id',
            'bookings',
            'id',
            'RESTRICT',
            'CASCADE'
        );

        $this->forge->addForeignKey(
            'user_id',
            'af_customers',
            'id',
            'RESTRICT',
            'CASCADE'
        );

        $this->forge->createTable('booking_addresses');
    }

    public function down()
    {
        $this->forge->dropTable('booking_addresses', true);
    }
}
