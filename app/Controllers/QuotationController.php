<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\QuotationModel;
use App\Models\QuotationItemModel;
use App\Models\QuotationInstallmentModel;
use App\Models\QuotationTimelineModel;
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
            if (!$jsonData) {
                throw new Exception('Invalid JSON input');
            }

            // Save Quotation
            $quotationModel = new QuotationModel();
            $quotationData = [
                'customer_name'   => $jsonData['customer_name'] ?? null,
                'phone'           => $jsonData['phone'] ?? null,
                'address'         => $jsonData['address'] ?? null,
                'total'           => $jsonData['total'] ?? 0,
                'discount'        => $jsonData['discount'] ?? 0,
                'discount_amount' => $jsonData['discountAmount'] ?? 0,
                'discount_desc'   => $jsonData['discountDesc'] ?? null,
                'sgst'            => $jsonData['sgst'] ?? 0,
                'cgst'            => $jsonData['cgst'] ?? 0,
                'grand_total'     => $jsonData['grandTotal'] ?? 0,
                'mark_list'       => $jsonData['mark_list'],
                'created_by'      => $jsonData['created_by'] ?? null,
                'created_at'      => date('Y-m-d H:i:s'),
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
                    ]);

                    if (!$parentId) {
                        throw new Exception('Failed to save parent item: ' . json_encode($quotationItemModel->errors()));
                    }
                }

                foreach ($item['subfiled'] as $subItem) {
                    $subItemId = $quotationItemModel->insert([
                        'quotation_id' => $quotationId,
                        'description'  => $subItem['description'] ?? null,
                        'details'      => $subItem['details'] ?? null,
                        'size'         => $subItem['size'] ?? null,
                        'quantity'     => $subItem['quantity'] ?? 0,
                        'type'         => $subItem['type'] ?? null,
                        'rate'         => $subItem['rate'] ?? 0,
                        'amount'       => $subItem['amount'] ?? 0,
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
                    'label'        => $installment['label'] ?? null,
                    'percentage'   => $installment['percentage'] ?? 0,
                    'amount'       => $installment['amount'] ?? 0,
                    'due_date'     => $installment['due_date'] ?? null,
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
                    'task'         => $timeline['task'] ?? null,
                    'days'         => $timeline['days'] ?? 0,
                ]);

                if (!$timelineId) {
                    throw new Exception('Failed to save timeline: ' . json_encode($quotationTimelineModel->errors()));
                }
            }

            $db->transComplete(); // Complete the transaction

            if ($db->transStatus() === false) {
                throw new Exception('Transaction failed');
            }

            return $this->respondCreated([
                'status'  => 201,
                'message' => 'Quotation stored successfully',
            ]);
        } catch (Exception $e) {
            $db->transRollback(); // Rollback the transaction on error
            return $this->respond([
                'status'  => 500,
                'message' => $e->getMessage(),
            ], 500);
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
                $quotation['items'] = $quotationItemModel->getItemsByQuotation($id);

                // Fetch related installments
                $quotationInstallmentModel = new QuotationInstallmentModel();
                $quotation['installments'] = $quotationInstallmentModel->where('quotation_id', $quotation['id'])->findAll();

                // Fetch related timelines
                $quotationTimelineModel = new QuotationTimelineModel();
                $quotation['timelines'] = $quotationTimelineModel->where('quotation_id', $quotation['id'])->findAll();

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
    public function update($quotationId)
    {
        $db = \Config\Database::connect();
        $db->transStart(); // Start a transaction

        try {
            // Input JSON data
            $jsonData = json_decode($this->request->getBody(), true);

            // Validate that the quotation exists
            $quotationModel = new QuotationModel();
            $quotation = $quotationModel->find($quotationId);
            if (!$quotation) {
                throw new Exception('Quotation not found.');
            }

            // Update Quotation Data
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
                'mark_list'     => $jsonData['mark_list'],
                'created_by'    => $jsonData['created_by'],
            ];

            $quotationModel->update($quotationId, $quotationData);

            // Sync Items
            $quotationItemModel = new QuotationItemModel();
            $existingItems = $quotationItemModel->where('quotation_id', $quotationId)->findAll(); // Fetch existing items
            $incomingItems = $jsonData['items'];

            // Sync items (both parent and subfiled)
            $this->syncItems($quotationId, $existingItems, $incomingItems, $quotationItemModel);

            // Sync Installments
            $quotationInstallmentModel = new QuotationInstallmentModel();
            $existingInstallments = $quotationInstallmentModel->where('quotation_id', $quotationId)->findAll(); // Fetch existing installments
            $incomingInstallments = $jsonData['installment'];

            $this->syncInstallments($quotationId, $existingInstallments, $incomingInstallments, $quotationInstallmentModel);

            // Sync Timeline
            $quotationTimelineModel = new QuotationTimelineModel();
            $existingTimelines = $quotationTimelineModel->where('quotation_id', $quotationId)->findAll(); // Fetch existing timelines
            $incomingTimelines = $jsonData['time_line'];

            $this->syncTimelines($quotationId, $existingTimelines, $incomingTimelines, $quotationTimelineModel);

            // Commit the transaction
            $db->transComplete();

            if ($db->transStatus() === false) {
                $error = $db->error();
                throw new Exception("Transaction failed: " . $error['message']);
            }

            return $this->response->setJSON([
                'status'  => 200,
                'message' => 'Quotation updated successfully',
            ]);
        } catch (\Exception $e) {
            // Rollback the transaction on error
            $db->transRollback();

            // Log the error message for debugging (optional)
            log_message('error', $e->getMessage());
            return $this->response->setJSON([
                'status'  => 500,
                'message' => 'Error updating quotation',
                'error'   => $e->getMessage(),
            ]);
        }
    }

    private function syncItems($quotationId, $existingItems, $incomingItems, $quotationItemModel)
    {
        // Map existing items by their ID for easy comparison
        $existingMap = [];
        foreach ($existingItems as $item) {
            $existingMap[$item['id']] = $item;
        }

        // Map existing subfiled items by their parent_id for easy comparison
        $existingSubfiledMap = [];
        foreach ($existingItems as $item) {
            if ($item['parent_id'] !== null) {
                $existingSubfiledMap[$item['parent_id']][$item['id']] = $item;
            }
        }

        foreach ($incomingItems as $item) {
            // Ensure 'id' exists before processing
            if (isset($item['id']) && isset($existingMap[$item['id']])) {
                // Update the existing item (parent item)
                $quotationItemModel->update($item['id'], [
                    'quotation_id' => $quotationId,
                    'title'        => $item['title'],
                    'description'  => $item['description'] ?: null,
                    'details'      => $item['details'] ?: null,
                    'size'         => $item['size'] ?: null,
                    'quantity'     => $item['quantity'] ?: null,
                    'type'         => $item['type'],
                    'rate'         => $item['rate'] ?: null,
                    'amount'       => $item['amount'] ?: null,
                    'parent_id'    => $item['parent_id'] ?: null,
                ]);

                // Handle subfiled items (if any) for updates, inserts, or deletes
                if (!empty($item['subfiled'])) {
                    foreach ($item['subfiled'] as $subItem) {
                        if (isset($subItem['id']) && isset($existingSubfiledMap[$item['id']][$subItem['id']])) {
                            // Update existing subfiled item
                            $quotationItemModel->update($subItem['id'], [
                                'quotation_id' => $quotationId,
                                'description'  => $subItem['description'],
                                'details'      => $subItem['details'],
                                'size'         => $subItem['size'],
                                'quantity'     => $subItem['quantity'],
                                'type'         => $subItem['type'],
                                'rate'         => $subItem['rate'],
                                'amount'       => $subItem['amount'],
                                'parent_id'    => $item['id'],
                            ]);
                        } else {
                            // Insert new subfiled item
                            $quotationItemModel->insert([
                                'quotation_id' => $quotationId,
                                'description'  => $subItem['description'],
                                'details'      => $subItem['details'],
                                'size'         => $subItem['size'],
                                'quantity'     => $subItem['quantity'],
                                'type'         => $subItem['type'],
                                'rate'         => $subItem['rate'],
                                'amount'       => $subItem['amount'],
                                'parent_id'    => $item['id'],
                            ]);
                        }
                    }

                    // After processing subfiled items, mark them as processed
                    foreach ($item['subfiled'] as $subItem) {
                        unset($existingSubfiledMap[$item['id']][$subItem['id']]);
                    }
                }

                // After processing the parent item, mark it as processed
                unset($existingMap[$item['id']]);
            } else {
                // Insert new parent item if no existing item with the same id
                $parentId = $quotationItemModel->insert([
                    'quotation_id' => $quotationId,
                    'title'        => $item['title'],
                    'description'  => null,
                    'details'      => null,
                    'size'         => null,
                    'quantity'     => null,
                    'type'         => null,
                    'rate'         => null,
                    'amount'       => null,
                    'parent_id'    => null,
                ]);

                // Insert subfiled items
                if (!empty($item['subfiled'])) {
                    foreach ($item['subfiled'] as $subItem) {
                        $quotationItemModel->insert([
                            'quotation_id' => $quotationId,
                            'description'  => $subItem['description'],
                            'details'      => $subItem['details'],
                            'size'         => $subItem['size'],
                            'quantity'     => $subItem['quantity'],
                            'type'         => $subItem['type'],
                            'rate'         => $subItem['rate'],
                            'amount'       => $subItem['amount'],
                            'parent_id'    => $parentId,
                        ]);
                    }
                }
            }
        }

        // Delete any existing items not found in the incoming data (parents)
        foreach ($existingMap as $existingItem) {
            $quotationItemModel->delete($existingItem['id']);
        }

        // Delete any remaining subfiled items that were not processed
        foreach ($existingSubfiledMap as $parentId => $subfiledItems) {
            foreach ($subfiledItems as $existingSubfiledItem) {
                $quotationItemModel->delete($existingSubfiledItem['id']);
            }
        }
    }


    private function syncInstallments($quotationId, $existingInstallments, $incomingInstallments, $model)
    {
        // Map existing installments by their ID for easy comparison
        $existingMap = [];
        foreach ($existingInstallments as $installment) {
            $existingMap[$installment['id']] = $installment;
        }

        foreach ($incomingInstallments as $installment) {
            // Check if the installment exists in the existing data
            if (isset($installment['id']) && isset($existingMap[$installment['id']])) {
                // Update the existing installment
                $model->update($installment['id'], [
                    'quotation_id' => $quotationId,
                    'label'        => $installment['label'],
                    'percentage'   => $installment['percentage'],
                    'amount'       => $installment['amount'],
                    'due_date'     => $installment['due_date'] ?: null, // Assuming 'due_date' might be nullable
                ]);

                // Mark as processed by unsetting it from the existing map
                unset($existingMap[$installment['id']]);
            } else {
                // Insert a new installment
                $model->insert([
                    'quotation_id' => $quotationId,
                    'label'        => $installment['label'],
                    'percentage'   => $installment['percentage'],
                    'amount'       => $installment['amount'],
                    'due_date'     => $installment['due_date'] ?: null, // Assuming 'due_date' might be nullable
                ]);
            }
        }

        // Delete any existing installments not found in the incoming data
        foreach ($existingMap as $existingInstallment) {
            $model->delete($existingInstallment['id']);
        }
    }
    private function syncTimelines($quotationId, $existingTimelines, $incomingTimelines, $model)
    {
        // Map existing timelines by their ID for easy comparison
        $existingMap = [];
        foreach ($existingTimelines as $timeline) {
            $existingMap[$timeline['id']] = $timeline;
        }

        foreach ($incomingTimelines as $timeline) {
            // Check if the timeline exists in the existing data
            if (isset($timeline['id']) && isset($existingMap[$timeline['id']])) {
                // Update the existing timeline
                $model->update($timeline['id'], [
                    'quotation_id' => $quotationId,
                    'task'         => $timeline['task'],
                    'days'         => $timeline['days'],
                ]);

                // Mark as processed by unsetting it from the existing map
                unset($existingMap[$timeline['id']]);
            } else {
                // Insert a new timeline
                $model->insert([
                    'quotation_id' => $quotationId,
                    'task'         => $timeline['task'],
                    'days'         => $timeline['days'],
                ]);
            }
        }

        // Delete any existing timelines not found in the incoming data
        foreach ($existingMap as $existingTimeline) {
            $model->delete($existingTimeline['id']);
        }
    }
}
