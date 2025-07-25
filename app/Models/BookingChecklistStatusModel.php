<?php

namespace App\Models;

use CodeIgniter\Model;

class BookingChecklistStatusModel extends Model
{
    protected $table      = 'booking_assignment_checklist_status';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'booking_service_id', 'checklist_id', 'partner_id', 'is_done', 'note', 'image_url', 'updated_at'
    ];
    protected $useTimestamps = false;
}
