<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSeebCartTable extends Migration
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

            // 🔗 SELF REFERENCE (ADDON → MAIN SERVICE)
            'parent_cart_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],

            // MAIN SERVICE
            'service_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            // ADDON SERVICE
            'addon_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],

            'service_type_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],

            'room_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],

            'quantity' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
            ],

            'unit' => [
                'type'       => 'ENUM',
                'constraint' => ['sqft', 'running_feet', 'running_meter', 'unit', 'points', 'square_feet'],
            ],

            'base_rate' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
            ],

            'selling_rate' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
            ],

            'offer_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],

            'offer_discount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => true,
            ],

            'final_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
            ],

            'room_length' => [
                'type'       => 'DECIMAL',
                'constraint' => '8,2',
                'null'       => true,
            ],

            'room_width' => [
                'type'       => 'DECIMAL',
                'constraint' => '8,2',
                'null'       => true,
            ],

            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],

            'reference_image' => [
                'type' => 'TEXT',
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
        $this->forge->addKey('parent_cart_id');
        $this->forge->addKey('service_id');
        $this->forge->addKey('addon_id');
        $this->forge->addKey('offer_id');
        $this->forge->addKey('room_id');

        // FOREIGN KEYS
        $this->forge->addForeignKey(
            'user_id',
            'customers',
            'id',
            'CASCADE',
            'CASCADE'
        );

        // Optional (recommended)
        $this->forge->addForeignKey(
            'addon_id',
            'service_addons',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->forge->createTable('seeb_cart', false, [
            'ENGINE' => 'InnoDB',
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('seeb_cart');
    }
}
