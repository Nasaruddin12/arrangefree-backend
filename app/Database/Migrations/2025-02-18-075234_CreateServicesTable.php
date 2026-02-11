<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateServicesTable extends Migration
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
            'service_type_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,   // IMPORTANT
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'image' => [
                'type'       => 'TEXT',
                'null'       => true,
            ],
            'rate' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => false,
            ],
            'rate_type' => [
                'type'       => 'ENUM',
                'constraint' => ['unit', 'square_feet','running_feet','running_meter','points', 'sqft'],
                'default'    => 'unit',
                'null'       => false,
            ],
            'description' => [
                'type'       => 'LONGTEXT',
                'null'       => true,
            ],
            'materials' => [
                'type'       => 'LONGTEXT',
                'null'       => true,
            ],
            'features' => [
                'type'       => 'LONGTEXT',
                'null'       => true,
            ],
            'care_instructions' => [
                'type'       => 'LONGTEXT',
                'null'       => true,
            ],
            'warranty_details' => [
                'type'       => 'LONGTEXT',
                'null'       => true,
            ],
            'quality_promise' => [
                'type'       => 'LONGTEXT',
                'null'       => true,
            ],
            'primary_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'secondary_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'partner_price' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => true,
            ],
            'with_material' => [
                'type'       => 'BOOLEAN',
                'default'    => false,
                'null'       => false,
            ],
            'slug' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'unique'     => true,
            ],
            'status' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1, // 1 = Active, 0 = Inactive
                'null'       => false,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],

        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey(
            'service_type_id',
            'service_types',
            'id',
            'SET NULL',   // ON DELETE
            'CASCADE'    // ON UPDATE
        );
        // Foreign key reference

        $this->forge->createTable('services');
    }

    public function down()
    {
        $this->forge->dropTable('services');
    }
}
