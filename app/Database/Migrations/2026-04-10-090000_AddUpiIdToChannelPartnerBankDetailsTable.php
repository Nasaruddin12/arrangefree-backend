<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUpiIdToChannelPartnerBankDetailsTable extends Migration
{
    public function up()
    {
        $fields = [];

        if (!$this->db->fieldExists('upi_id', 'channel_partner_bank_details')) {
            $fields['upi_id'] = [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'ifsc_code',
            ];
        }

        if (!empty($fields)) {
            $this->forge->addColumn('channel_partner_bank_details', $fields);
        }

        $this->forge->modifyColumn('channel_partner_bank_details', [
            'account_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'ifsc_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
        ]);
    }

    public function down()
    {
        if ($this->db->fieldExists('upi_id', 'channel_partner_bank_details')) {
            $this->forge->dropColumn('channel_partner_bank_details', 'upi_id');
        }

        $this->forge->modifyColumn('channel_partner_bank_details', [
            'account_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
            ],
            'ifsc_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => false,
            ],
        ]);
    }
}
