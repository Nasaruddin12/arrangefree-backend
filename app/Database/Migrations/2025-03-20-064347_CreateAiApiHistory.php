<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAiApiHistory extends Migration
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
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'api_endpoint' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'request_data' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'response_data' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'status_code' => [
                'type'       => 'INT',
                'constraint' => 5,
                'null'       => true,
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

        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('user_id', 'customers', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('ai_api_history');
    }

    public function down()
    {
        $this->forge->dropTable('ai_api_history');
    }
}
