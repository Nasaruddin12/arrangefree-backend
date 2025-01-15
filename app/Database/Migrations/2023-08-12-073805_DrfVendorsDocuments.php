<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DrfVendorsDocuments extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'vendor_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'sign' => [
                'type' => 'VARCHAR',
                'constraint' => 101,
                'null' => true,
            ],
            'aggrement' => [
                'type' => 'VARCHAR',
                'constraint' => 101,
                'null' => true,
            ],
            'pan_card' => [
                'type' => 'VARCHAR',
                'constraint' => 101,
                'null' => true,
            ],
            'aadhar_card' => [
                'type' => 'VARCHAR',
                'constraint' => 101,
                'null' => true,
            ],
            'shop_act' => [
                'type' => 'VARCHAR',
                'constraint' => 101,
                'null' => true,
            ],
            'shop_image' => [
                'type' => 'VARCHAR',
                'constraint' => 101,
                'null' => true,
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('drf_vendors_document');
    }

    public function down()
    {
        $this->forge->dropTable('drf_vendors_document');

    }
}
