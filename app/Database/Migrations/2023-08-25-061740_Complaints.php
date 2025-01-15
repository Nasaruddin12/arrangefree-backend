<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Complaints extends Migration
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
            'user_id' => [
                'type' => 'int',
                'constraint' => 11,
                'null' => false,
            ],
            'description' => [
                'type' => 'VARCHAR',
                'constraint' => 1500,
                'null' => false,
            ],
            'product_id' => [
                'type' => 'int',
                'constraint' => 11,
                'null' => false,
            ],
            'order_id' => [
                'type' => 'int',
                'constraint' => 11,
                'null' => false,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 151,
                'default' => "Pending",
                'comment' => "Pending, inProgress, Solved",
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);

        $this->forge->addPrimaryKey('id');

        $this->forge->createTable('af_complaints');
    }

    public function down()
    {
        $this->forge->dropTable('af_complaints');
    }
}
