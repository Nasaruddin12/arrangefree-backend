<?php

namespace App\Models;

use CodeIgniter\Model;

class QuotationTimelineModel extends Model
{
    protected $table = 'quotation_timeline';
    protected $primaryKey = 'id';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'quotation_id',
        'task',
        'days',
    ];

    /**
     * Fetch all timeline tasks for a specific quotation.
     *
     * @param int $quotationId
     * @return array
     */
    public function getTimelineByQuotation($quotationId)
    {
        return $this->where('quotation_id', $quotationId)->findAll();
    }
}
