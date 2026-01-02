<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUserConfirmationWaitingStatus extends Migration
{
    public function up()
    {
        $this->db->query("
            ALTER TABLE bookings 
            MODIFY status ENUM(
                'pending',
                'user_confirmation_waiting',
                'confirmed',
                'cancelled',
                'completed',
                'in_progress',
                'failed_payment'
            ) DEFAULT 'pending'
        ");
    }

    public function down()
    {
        // ⚠️ Ensure no rows use the new status before rollback
        $this->db->query("
            UPDATE bookings 
            SET status = 'pending' 
            WHERE status = 'user_confirmation_waiting'
        ");

        $this->db->query("
            ALTER TABLE bookings 
            MODIFY status ENUM(
                'pending',
                'confirmed',
                'cancelled',
                'completed',
                'in_progress',
                'failed_payment'
            ) DEFAULT 'pending'
        ");
    }
}
