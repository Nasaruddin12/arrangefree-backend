<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCustomerIdToCoupons extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('coupons') || $this->db->fieldExists('customer_id', 'coupons')) {
            return;
        }

        $this->forge->addColumn('coupons', [
            'customer_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
                'after'    => 'universal',
            ],
        ]);

        $this->db->query('ALTER TABLE `coupons` ADD INDEX `idx_coupons_customer_id` (`customer_id`)');
    }

    public function down()
    {
        if (!$this->db->tableExists('coupons') || !$this->db->fieldExists('customer_id', 'coupons')) {
            return;
        }

        $this->db->query('ALTER TABLE `coupons` DROP INDEX `idx_coupons_customer_id`');
        $this->forge->dropColumn('coupons', 'customer_id');
    }
}
