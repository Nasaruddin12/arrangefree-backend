<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTeamTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'auto_increment' => true],
            'name'             => ['type' => 'VARCHAR', 'constraint' => 255],
            'mobile'           => ['type' => 'VARCHAR', 'constraint' => 20],
            'age'              => ['type' => 'INT'],
            'work'             => ['type' => 'VARCHAR', 'constraint' => 255],
            'labour_count'     => ['type' => 'INT'],
            'area'             => ['type' => 'VARCHAR', 'constraint' => 255],
            'service_areas'    => ['type' => 'TEXT'],
            'aadhaar_no'       => ['type' => 'VARCHAR', 'constraint' => 20],
            'aadhaar_front'    => ['type' => 'VARCHAR', 'constraint' => 255],
            'aadhaar_back'     => ['type' => 'VARCHAR', 'constraint' => 255],
            'pan_no'           => ['type' => 'VARCHAR', 'constraint' => 20],
            'pan_file'         => ['type' => 'VARCHAR', 'constraint' => 255],
            'address_proof'    => ['type' => 'VARCHAR', 'constraint' => 255],
            'photo'            => ['type' => 'VARCHAR', 'constraint' => 255],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('teams');
    }

    public function down()
    {
        $this->forge->dropTable('teams');
    }
}
