<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ApptextheaderValues extends Migration
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
            'header_id' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'value' => [
                'type' => 'VARCHAR',
                'constraint' => 1500,
                'collation' => 'utf8mb4_unicode_ci',
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('af_app_text_header_value');
    }

    public function down()
    {
        $this->forge->dropTable('af_app_text_header_value');
    }
}
