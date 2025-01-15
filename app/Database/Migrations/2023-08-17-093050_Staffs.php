<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Staffs extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'username'    => ['type' => 'VARCHAR', 'constraint' => 250],
            'password'    => ['type' => 'VARCHAR', 'constraint' => 250],
            'name'        => ['type' => 'VARCHAR', 'constraint' => 250],
            'phone'       => ['type' => 'VARCHAR', 'constraint' => 150],
            'email'       => ['type' => 'VARCHAR', 'constraint' => 250],
            'aadhaar_no'  => ['type' => 'VARCHAR', 'constraint' => 250, 'null' => true],
            'pan_no'      => ['type' => 'VARCHAR', 'constraint' => 250, 'null' => true],
            'adhaar_file' => ['type' => 'VARCHAR', 'constraint' => 250, 'null' => true],
            'pan_file'    => ['type' => 'VARCHAR', 'constraint' => 250, 'null' => true],
            'status'      => ['type' => 'INT', 'constraint' => 11, 'default' => 1, 'comment' => '1 Active, 2 Inactive'],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('drf_staff');
    }

    public function down()
    {
        $this->forge->dropTable('drf_staff');
    }
}
