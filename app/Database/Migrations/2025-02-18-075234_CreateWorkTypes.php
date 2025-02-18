<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateWorkTypes extends Migration
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
            'service_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'image' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'rate' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => false,
            ],
            'rate_type' => [
                'type'       => 'ENUM',
                'constraint' => ['unit', 'per_square_feet'],
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
            ]
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('service_id', 'services', 'id', 'CASCADE', 'CASCADE'); // Foreign key reference

        $this->forge->createTable('work_types');
    }

    public function down()
    {
        $this->forge->dropTable('work_types');
    }
}
