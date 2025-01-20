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
        // Query to fetch detailed information about the mark list
        return $this->db->table($this->table)
            ->select(
                'quotation_mark_list.id, 
                 quotation_mark_list.quotation_id, 
                 quotation_mark_list.master_id, 
                 quotation_mark_list.subcategory_id, 
                 master_category.title AS master_category_title, 
                 master_subcategory.title AS master_subcategory_title'
            )
            ->join('master_category', 'master_category.id = quotation_mark_list.master_id', 'left')
            ->join('master_subcategory', 'master_subcategory.id = quotation_mark_list.subcategory_id', 'left')
            ->where('quotation_mark_list.quotation_id', $quotationId)
            ->get()
            ->getResultArray();
    }

    /**
     * Fetch the grouped mark list for a specific quotation.
     *
     * @param int $quotationId
     * @return array
     */
    public function getGroupedMarkListByQuotation($quotationId)
    {
        // Fetch raw data from the database
        $result = $this->db->table($this->table)
            ->select('master_id, subcategory_id')
            ->where('quotation_id', $quotationId)
            ->get()
            ->getResultArray();

        // Initialize an empty array for grouping
        $groupedData = [];

        // Iterate through the result to group by `master_id`
        foreach ($result as $row) {
            $masterId = $row['master_id'];
            $subcategoryId = $row['subcategory_id'];

            // Initialize the array for `master_id` if it doesn't exist
            if (!array_key_exists($masterId, $groupedData)) {
                $groupedData[$masterId] = [];
            }

            // Add the `subcategory_id` to the respective `master_id`
            $groupedData[$masterId][] = $subcategoryId;
        }

        return $groupedData;
    }
}
