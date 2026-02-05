<?php

namespace App\Models;

use CodeIgniter\Model;

class PartnerJobPresenceLogModel extends Model
{
    protected $table      = 'partner_job_presence_logs';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';

    protected $allowedFields = [
        'partner_job_id',
        'partner_id',
        'event_type',
        'event_time',
        'source',
        'lat',
        'lng',
        'accuracy',
        'note',
        'created_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';
    protected $dateFormat    = 'datetime';

    protected $validationRules = [
        'partner_job_id' => 'required|is_natural_no_zero',
        'partner_id' => 'required|is_natural_no_zero',
        'event_type' => 'required|in_list[onsite,left,pause,resume]',
        'event_time' => 'permit_empty|valid_date',
        'source' => 'permit_empty|in_list[app,admin,system]',
        'lat' => 'permit_empty|decimal',
        'lng' => 'permit_empty|decimal',
        'accuracy' => 'permit_empty|decimal',
        'note' => 'permit_empty|string',
    ];
}
