<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AfSectionAccess extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'section_title' => [
                'type' => 'VARCHAR',
                'constraint' => 21,
            ],
            'access_key' =>
            [
                'type' => 'VARCHAR',
                'constraint' => 101,
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('section_access');
    }

    public function down()
    {
        $this->forge->dropTable('section_access');
    }
}
