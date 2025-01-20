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
        return $this->db->table('quotation_mark_list')
            ->select('quotation_mark_list.id, quotation_mark_list.quotation_id, quotation_mark_list.master_id, quotation_mark_list.subcategory_id, master_category.title AS master_category_title, master_subcategory.title AS master_subcategory_title')
            ->join('master_category', 'master_category.id = quotation_mark_list.master_id', 'left')
            ->join('master_subcategory', 'master_subcategory.id = quotation_mark_list.subcategory_id', 'left')
            ->where('quotation_mark_list.quotation_id', $quotationId)
            ->get()
            ->getResultArray();
    }
}
