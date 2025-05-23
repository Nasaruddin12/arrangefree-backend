<?php

namespace App\Models;

use CodeIgniter\Model;

class TicketModel extends Model
{
    protected $table      = 'tickets';
    protected $primaryKey = 'id';
    protected $allowedFields = ['ticket_uid', 'user_id', 'subject', 'file', 'status', 'created_at', 'updated_at'];

    // protected $beforeInsert = ['generateTicketUID'];

    public function generateTicketUID()
    {
        $yearPrefix = date('y'); // Get last two digits of the year (e.g., 24 for 2024)

        // Find the last ticket with the same year prefix
        $lastTicket = $this->like('ticket_uid', "TCKT-$yearPrefix", 'after')
            ->orderBy('id', 'DESC')
            ->first();

        // Extract the numeric part and increment
        if ($lastTicket && preg_match('/TCKT-\d{2}(\d{4})/', $lastTicket['ticket_uid'], $matches)) {
            $nextNumber = (int)$matches[1] + 1; // Increment last number
        } else {
            $nextNumber = 1; // Start from 0001 if no ticket exists for this year
        }

        // Format the new ticket UID (e.g., TCKT-240001)
        return 'TCKT-' . $yearPrefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
