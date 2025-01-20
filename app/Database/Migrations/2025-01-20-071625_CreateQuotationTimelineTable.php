<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateQuotationTimelineTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'quotation_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'task' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'days' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('quotation_id', 'quotations', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('quotation_timeline');
    }

    public function down()
    {
        $this->forge->dropTable('quotation_timeline');
    }
}
