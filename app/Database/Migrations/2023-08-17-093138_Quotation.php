<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Quotation extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'customer_name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'phone'         => ['type' => 'VARCHAR', 'constraint' => 150],
            'address'       => ['type' => 'VARCHAR', 'constraint' => 150],
            'items'         => ['type' => 'VARCHAR', 'constraint' => 2500],
            'mark_list'     => ['type' => 'VARCHAR', 'constraint' => 6500],
            'total_amount'  => ['type' => 'VARCHAR', 'constraint' => 100],
            'sgst'          => ['type' => 'VARCHAR', 'constraint' => 100],
            'cgst'          => ['type' => 'VARCHAR', 'constraint' => 100],
            'installment'   => ['type' => 'VARCHAR', 'constraint' => 3500],
            'time_line'     => ['type' => 'VARCHAR', 'constraint' => 350],
            'created_by'    => ['type' => 'VARCHAR', 'constraint' => 150],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('drf_quotation');
    }

    public function down()
    {
        $this->forge->dropTable('drf_quotation');
    }
}
