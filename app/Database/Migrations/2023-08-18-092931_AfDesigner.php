<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AfDesigner extends Migration
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
            'employee_id' => [
                'type' => 'VARCHAR',
                'constraint' => 300,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 300,
            ],
            'pan_number' => [
                'type' => 'VARCHAR',
                'constraint' => 300,
            ],
            'adhaar_number' => [
                'type' => 'VARCHAR',
                'constraint' => 300,
            ],
            'pan_card' => [
                'type' => 'VARCHAR',
                'constraint' => 300,
            ],
            'adhaar_card' => [
                'type' => 'VARCHAR',
                'constraint' => 300,
            ],
            'agreement' => [
                'type' => 'VARCHAR',
                'constraint' => 300,
            ],
            'status' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 1,
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'deleted_at DATETIME',
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('af_designer');
    }

    public function down()
    {
        $this->forge->dropTable('af_designer');
    }
}
