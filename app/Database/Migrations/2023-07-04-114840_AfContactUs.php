<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AfContactUs extends Migration
{
    public function up()
    {
        // Get the database connection
        $db = \Config\Database::connect();

        // Check if the table exists
        if (!$db->tableExists('af_contact_us')) {
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
                'email_id' => [
                    'type' => 'VARCHAR',
                    'constraint' => 101,
                ],
                'contact_number' => [
                    'type' => 'VARCHAR',
                    'constraint' => 15,
                ],
                'message' => [
                    'type' => 'VARCHAR',
                    'constraint' => 256,
                ],
                'remark' => [
                    'type'       => 'JSON',
                    'null'       => true,
                ],
                'city' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => true,
                ],
                'space_type' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => true,
                ],
                'status' => [
                    'type' => 'TINYINT',
                    'null' => false,
                    'default' => 1,
                ],
                'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
                'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            ]);
            $this->forge->addPrimaryKey('id');
            $this->forge->createTable('af_contact_us');
        } else {
            echo "Table 'af_contact_us' already exists. Skipping creation.\n";
        }
    }

    public function down()
    {
        // Drop the table only if it exists
        $db = \Config\Database::connect();
        if ($db->tableExists('af_contact_us')) {
            $this->forge->dropTable('af_contact_us');
        }
    }
}
