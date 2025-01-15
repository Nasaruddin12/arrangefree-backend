<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Apptextheader extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'int',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 150,
                'null' => false,
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'deleted_at DATETIME',
        ]);

        $this->forge->addPrimaryKey('id');

        $this->forge->createTable('af_app_text_header');
    }

    public function down()
    {
        $this->forge->dropTable('af_app_text_header');
    }
}
