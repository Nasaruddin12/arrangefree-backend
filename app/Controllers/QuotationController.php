<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\QuotationModel;
use App\Models\QuotationItemModel;
use App\Models\QuotationInstallmentModel;
use App\Models\QuotationTimelineModel;
use App\Models\QuotationMarkListModel;
use CodeIgniter\API\ResponseTrait;
use Exception;

class QuotationController extends BaseController
{
    use ResponseTrait;
    public function store()
    {
        $db = \Config\Database::connect();
        $db->transStart(); // Start a transaction

        try {
            // Input JSON data
            $jsonData = json_decode($this->request->getBody(), true);

            // Save Quotation
            $quotationModel = new QuotationModel();
            $quotationData = [
                'customer_name' => $jsonData['customer_name'],
                'phone'         => $jsonData['phone'],
                'address'       => $jsonData['address'],
                'total'         => $jsonData['total'],
                'discount'      => $jsonData['discount'],
                'discount_amount' => $jsonData['discountAmount'],
                'discount_desc' => $jsonData['discountDesc'],
                'sgst'          => $jsonData['sgst'],
                'cgst'          => $jsonData['cgst'],
                'grand_total'   => $jsonData['grandTotal'],
                'created_by'    => $jsonData['created_by'],
                'created_at'    => date('Y-m-d H:i:s'),
            ];
            $quotationId = $quotationModel->insert($quotationData);
            if (!$quotationId) {
                throw new Exception('Failed to save quotation: ' . json_encode($quotationModel->errors()));
            }

            // Save Quotation Items
            $quotationItemModel = new QuotationItemModel();
            $items = json_decode($jsonData['items'], true);
            foreach ($items as $item) {
                $parentId = null;
                if (!empty($item['title'])) {
                    $parentId = $quotationItemModel->insert([
                        'quotation_id' => $quotationId,
                        'title'        => $item['title'],
                        'description'  => null,
                        'size'         => null,
                        'quantity'     => null,
                        'type'         => null,
                        'rate'         => null,
                        'amount'       => null,
                        'parent_id'    => null,
                    ]);
                    if (!$parentId) {
                        throw new Exception('Failed to save parent item: ' . json_encode($quotationItemModel->errors()));
                    }
                }

                foreach ($item['subfiled'] as $subItem) {
                    $subItemId = $quotationItemModel->insert([
                        'quotation_id' => $quotationId,
                        'title'        => null,
                        'description'  => $subItem['description'],
                        'size'         => $subItem['size'],
                        'quantity'     => $subItem['quantity'],
                        'type'         => $subItem['type'],
                        'rate'         => $subItem['rate'],
                        'amount'       => $subItem['amount'],
                        'parent_id'    => $parentId,
                    ]);
                    if (!$subItemId) {
                        throw new Exception('Failed to save sub-item: ' . json_encode($quotationItemModel->errors()));
                    }
                }
            }

            // Save Installments
            $quotationInstallmentModel = new QuotationInstallmentModel();
            $installments = json_decode($jsonData['installment'], true);
            foreach ($installments as $installment) {
                $installmentId = $quotationInstallmentModel->insert([
                    'quotation_id' => $quotationId,
                    'label'        => $installment['label'],
                    'percentage'   => $installment['percentage'],
                    'amount'       => $installment['amount'],
                    'due_date'     => $installment['date'] ?: null,
                ]);
                if (!$installmentId) {
                    throw new Exception('Failed to save installment: ' . json_encode($quotationInstallmentModel->errors()));
                }
            }

            // Save Timeline
            $quotationTimelineModel = new QuotationTimelineModel();
            $timelines = json_decode($jsonData['time_line'], true);
            foreach ($timelines as $timeline) {
                $timelineId = $quotationTimelineModel->insert([
                    'quotation_id' => $quotationId,
                    'task'         => $timeline['task'],
                    'days'         => $timeline['days'],
                ]);
                if (!$timelineId) {
                    throw new Exception('Failed to save timeline: ' . json_encode($quotationTimelineModel->errors()));
                }
            }

            // Save Mark List
            $quotationMarkListModel = new QuotationMarkListModel();
            $markList = json_decode($jsonData['mark_list'], true);
            foreach ($markList as $masterId => $subcategoryIds) {
                foreach ($subcategoryIds as $subcategoryId) {
                    $markListId = $quotationMarkListModel->insert([
                        'quotation_id'   => $quotationId,
                        'master_id'      => $masterId,
                        'subcategory_id' => $subcategoryId,
                    ]);
                    if (!$markListId) {
                        throw new Exception('Failed to save mark list: ' . json_encode($quotationMarkListModel->errors()));
                    }
                }
            }

            $db->transComplete(); // Complete the transaction

            if ($db->transStatus() === false) {
                $error = $db->error();
                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => 'Transaction failed',
                    'error'   => $error,
                ]);
            }

            return $this->response->setJSON(['status' => 201, 'message' => 'Quotation stored successfully'], 201);
        } catch (\Exception $e) {
            $db->transRollback(); // Rollback the transaction on error
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }
    public function getAll()
    {
        try {
            // Load the Quotation Model
            $quotationModel = new QuotationModel();

            // Retrieve all quotations
            $quotations = $quotationModel->findAll();

            // Check if quotations are found
            if ($quotations) {
                return $this->respond([
                    'status'  => 200,
                    'message' => 'Quotations retrieved successfully',
                    'data'    => $quotations
                ], 200);
            } else {
                throw new \Exception('No quotations found', 404);
            }
        } catch (\Exception $e) {
            return $this->respond([
                'status'  => $e->getCode(),
                'message' => $e->getMessage()
            ], $e->getCode());
        }
    }
    public function getById($id = null)
    {
        try {
            // Load the Quotation Model
            $quotationModel = new QuotationModel();

            // Retrieve quotation by ID
            $quotation = $quotationModel->find($id);

            // Check if quotation is found
            if ($quotation) {
                // Fetch related items
                $quotationItemModel = new QuotationItemModel();
                $quotation['items'] = $quotationItemModel->getItemsByQuotation($quotation);

                // Fetch related installments
                $quotationInstallmentModel = new QuotationInstallmentModel();
                $quotation['installments'] = $quotationInstallmentModel->where('quotation_id', $quotation['id'])->findAll();

                // Fetch related timelines
                $quotationTimelineModel = new QuotationTimelineModel();
                $quotation['timelines'] = $quotationTimelineModel->where('quotation_id', $quotation['id'])->findAll();

                // Fetch related mark lists
                $quotationMarkListModel = new QuotationMarkListModel();
                $quotation['mark_list'] = $quotationMarkListModel->where('quotation_id', $quotation['id'])->findAll();

                return $this->respond([
                    'status'  => 200,
                    'message' => 'Quotation retrieved successfully',
                    'data'    => $quotation
                ], 200);
            } else {
                throw new \Exception('Quotation not found', 404);
            }
        } catch (\Exception $e) {
            return $this->respond([
                'status'  => $e->getCode(),
                'message' => $e->getMessage()
            ], $e->getCode());
        }
    }
}
