<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AfContactTaskForce extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 101,
            ],
            'email_id' => [
                'type' => 'VARCHAR',
                'constraint' => 101,
            ],
            'contact_number' => [
                'type' => 'VARCHAR',
                'constraint' => 15,
            ],
            'message' => [
                'type' => 'VARCHAR',
                'constraint' => 256,
            ],
            'status' => [
                'type' => 'TINYINT',
                'null' => false,
                'default' => 1,
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('af_taskForce_contact_us');
    }

    public function down()
    {
        $this->forge->dropTable('af_taskForce_contact_us');

    }

}
