<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateStaffTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'name'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'email'          => ['type' => 'VARCHAR', 'constraint' => 255, 'unique' => true, 'null' => false],
            'mobile_no'      => ['type' => 'VARCHAR', 'constraint' => 15, 'null' => false],
            'salary'         => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => false],
            'aadhar_no'      => ['type' => 'VARCHAR', 'constraint' => 12, 'unique' => true, 'null' => false],
            'pan_no'         => ['type' => 'VARCHAR', 'constraint' => 10, 'unique' => true, 'null' => false],
            'joining_date'   => ['type' => 'DATE', 'null' => false],
            'relieving_date' => ['type' => 'DATE', 'null' => true],
            'designation'    => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'pan_card'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'aadhar_card'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'photo'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'joining_letter' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status'         => ['type' => 'ENUM', 'constraint' => ['active', 'inactive'], 'default' => 'active'], // Status field
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);



        $this->forge->addKey('id', true);
        $this->forge->createTable('staff');
    }

    public function down()
    {
        $this->forge->dropTable('staff');
    }
}
