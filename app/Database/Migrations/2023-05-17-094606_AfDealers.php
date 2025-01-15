<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AfDealers extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 101,
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => 101,
            ],
            'mobile_no' => [
                'type' => 'VARCHAR',
                'constraint' => 15,
                'unique' => true,
            ],
            'password' => [
                'type' => 'VARCHAR',
                'constraint' => 256,
            ],
            'company_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'is_logged_in' => [
                'type' => 'boolean',
                'null' => false,
                'default' => 0,
            ],
            'otp' => [
                'type' => 'VARCHAR',
                'constraint' => '101',
            ],
            'status' => [
                'type' => 'TINYINT',
                'null' => false,
                'default' => -1,
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('af_dealers');
    }

    public function down()
    {
        $this->forge->dropTable('af_dealers');
    }
}
