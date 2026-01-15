<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPartnerRateToBookingAssignmentsTable extends Migration
{
    public function up()
    {
        // Rename existing assigned_amount to amount (MariaDB syntax)
        $this->db->query("ALTER TABLE booking_assignments CHANGE assigned_amount amount DECIMAL(10,2)");

        // Add partner rate fields
        $this->forge->addColumn('booking_assignments', [
            'rate' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => true,
                'comment'    => 'Partner rate used (partner_price per sqft/unit/point)',
            ],
            'rate_type' => [
                'type'       => 'ENUM',
                'constraint' => ['square_feet', 'unit', 'points'],
                'null'       => true,
                'comment'    => 'How partner_amount is calculated (same as service rate_type)',
            ],
            'quantity' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'null'       => true,
                'comment'    => 'Quantity used for calculation (area in sqft or units or points)',
            ],
            'with_material' => [
                'type'       => 'BOOLEAN',
                'default'    => false,
                'comment'    => 'Does partner_rate include material cost? (from service)',
            ],
        ]);
    }

    public function down()
    {
        // Drop new columns
        $this->forge->dropColumn('booking_assignments', [
            'rate',
            'rate_type',
            'quantity',
            'with_material',
        ]);

        // Rename amount back to assigned_amount (MariaDB syntax)
        $this->db->query("ALTER TABLE booking_assignments CHANGE amount assigned_amount DECIMAL(10,2)");
    }
}
