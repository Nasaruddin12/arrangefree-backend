<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AfGeneralOption extends Migration
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
            'group_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'title' => [
                'type' => 'VARCHAR',
                'constraint' => 101,
            ],
            'value' => [
                'type' => 'VARCHAR',
                'constraint' => 101,
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('af_general_options');
    }
    

    public function down()
    {
        $this->forge->dropTable('af_general_options');

    }
}
