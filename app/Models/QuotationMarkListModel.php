<?php

namespace App\Models;

use CodeIgniter\Model;

class QuotationMarkListModel extends Model
{
    protected $table = 'quotation_mark_list';
    protected $primaryKey = 'id';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'quotation_id',
        'master_id',
        'subcategory_id',
    ];

    /**
     * Fetch the mark list for a specific quotation.
     *
     * @param int $quotationId
     * @return array
     */
    public function getMarkListByQuotation($quotationId)
    {
        return $this->where('quotation_id', $quotationId)->findAll();
    }
}
