<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateQuotationMarkListTable extends Migration
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
            'master_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'subcategory_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('quotation_id', 'quotations', 'id', 'CASCADE', 'CASCADE');
        // $this->forge->addForeignKey('master_id', 'master_category', 'id', 'CASCADE', 'CASCADE');
        // $this->forge->addForeignKey('subcategory_id', 'master_subcategory', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('quotation_mark_list');
    }

    public function down()
    {
        $this->forge->dropTable('quotation_mark_list');
    }
}
