<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AfServiceRequest extends Migration
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
            ],
            'service' => [
                'type' => 'VARCHAR',
                'constraint' => 256,
            ],
            'message' => [
                'type' => 'MEDIUMTEXT',
            ],
            'status' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('af_service_request');
    }

    public function down()
    {
        $this->forge->dropTable('af_service_request');

    }
}
