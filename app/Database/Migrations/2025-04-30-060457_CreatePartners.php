<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePartners extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                   => ['type' => 'INT', 'auto_increment' => true],
            'name'                 => ['type' => 'VARCHAR', 'constraint' => 100],
            'mobile'               => ['type' => 'VARCHAR', 'constraint' => 15],
            'mobile_verified'      => ['type' => 'BOOLEAN', 'default' => false],
            'dob'                  => ['type' => 'DATE'],
            'work'                 => ['type' => 'VARCHAR', 'constraint' => 100],
            'labour_count'         => ['type' => 'INT'],
            'area'                 => ['type' => 'VARCHAR', 'constraint' => 100],
            'service_areas'        => ['type' => 'VARCHAR', 'constraint' => 255],
            'aadhaar_no'           => ['type' => 'VARCHAR', 'constraint' => 20],
            'pan_no'               => ['type' => 'VARCHAR', 'constraint' => 20],
            'documents_verified'   => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'verified', 'rejected'],
                'default'    => 'pending',
            ],
            'bank_verified'        => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'verified', 'rejected'],
                'default'    => 'pending',
            ],
            'verified_by'          => ['type' => 'INT', 'null' => true],
            'verified_at'          => ['type' => 'DATETIME', 'null' => true],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'active', 'blocked', 'terminated', 'resigned', 'rejected'],
                'default'    => 'pending',
            ],
            'created_at'           => ['type' => 'DATETIME', 'null' => true],
            'updated_at'           => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('partners');
    }

    public function down()
    {
        $this->forge->dropTable('partners');
    }
}
