<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\MasterCategoryModel;
use App\Models\MasterSubCategoryModel;
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
    public function quotationById($id = null)
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

                // Fetch and format mark_list
                $quotation['mark_list'] = $this->getMarkList($quotation['mark_list']);

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
    public function quotationByCustomerMobileNumber($mobileNumber = null)
    {
        try {
            // Validate the mobile number
            if (empty($mobileNumber)) {
                throw new \Exception('Mobile number is required', 400);
            }

            // Load the Quotation Model
            $quotationModel = new QuotationModel();

            // Retrieve quotation by customer mobile number (assuming 'customer_mobile' is the field name)
            $quotation = $quotationModel->where('phone', $mobileNumber)->findAll();

            // Check if quotation is found
            if ($quotation) {
                return $this->respond([
                    'status'  => 200,
                    'message' => 'Quotation retrieved successfully',
                    'data'    => $quotation
                ], 200);
            } else {
                throw new \Exception('Quotation not found for the given mobile number', 404);
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
        // Map existing parent items and subfiled items by their ID for easy comparison
        $existingMap = [];
        $existingSubfiledMap = [];

        // Build existing items and subfiled maps
        foreach ($existingItems as $item) {
            if ($item['parent_id'] === null) {
                // Parent item
                $existingMap[$item['id']] = $item;
            } else {
                // Subfiled item
                $existingSubfiledMap[$item['parent_id']][$item['id']] = $item;
            }
        }

        $processedParentIds = [];
        $processedSubfiledIds = [];

        // Loop through incoming items and process
        foreach ($incomingItems as $item) {
            $itemId = $item['id'] ?? null;

            if ($itemId) {
                $processedParentIds[] = $itemId;

                // Update existing parent item
                if (isset($existingMap[$itemId])) {
                    $quotationItemModel->update($itemId, [
                        'quotation_id' => $quotationId,
                        'title'        => $item['title'],
                        'description'  => $item['description'] ?? null,
                        'details'      => $item['details'] ?? null,
                        'size'         => $item['size'] ?? null,
                        'quantity'     => $item['quantity'] ?? null,
                        'type'         => $item['type'],
                        'rate'         => $item['rate'] ?? null,
                        'amount'       => $item['amount'] ?? null,
                        'parent_id'    => $item['parent_id'] ?? null,
                    ]);
                }
            } else {
                // Insert new parent item
                $itemId = $quotationItemModel->insert([
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
            }

            // Handle subfiled items
            if (!empty($item['subfiled'])) {
                foreach ($item['subfiled'] as $subItem) {
                    $subfiledId = $subItem['id'] ?? null;

                    if ($subfiledId) {
                        $processedSubfiledIds[] = $subfiledId;

                        if (isset($existingSubfiledMap[$itemId][$subfiledId])) {
                            $quotationItemModel->update($subfiledId, [
                                'quotation_id' => $quotationId,
                                'description'  => $subItem['description'],
                                'details'      => $subItem['details'],
                                'size'         => $subItem['size'],
                                'quantity'     => $subItem['quantity'],
                                'type'         => $subItem['type'],
                                'rate'         => $subItem['rate'],
                                'amount'       => $subItem['amount'],
                                'parent_id'    => $itemId,
                            ]);
                        }
                    } else {
                        $processedSubfiledIds[] = $quotationItemModel->insert([
                            'quotation_id' => $quotationId,
                            'description'  => $subItem['description'],
                            'details'      => $subItem['details'],
                            'size'         => $subItem['size'],
                            'quantity'     => $subItem['quantity'],
                            'type'         => $subItem['type'],
                            'rate'         => $subItem['rate'],
                            'amount'       => $subItem['amount'],
                            'parent_id'    => $itemId,
                        ]);
                    }
                }
            }
        }

        // Clean up orphaned parent items
        foreach ($existingMap as $existingId => $existingItem) {
            if (!in_array($existingId, $processedParentIds)) {
                $quotationItemModel->delete($existingId);
            }
        }

        // Clean up orphaned subfiled items
        foreach ($existingSubfiledMap as $parentId => $subfiledItems) {
            foreach ($subfiledItems as $subfiledId => $subfiledItem) {
                if (!in_array($subfiledId, $processedSubfiledIds)) {
                    $quotationItemModel->delete($subfiledId);
                }
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

    public function changeStatus($id)
    {
        try {
            $quotationModel = new QuotationModel();
            $newStatus = $this->request->getVar('status');

            $quotation = $quotationModel->find($id);

            if (!$quotation) {
                throw new Exception('Quotation not found', 404);
            }

            // Update the status
            $quotationModel->update($id, ['status' => $newStatus]);

            // Prepare success response
            $statusCode = 200;
            $response = [
                'message' => 'Quotation converted to sale successfully',
            ];
        } catch (Exception $e) {
            // Handle errors
            $statusCode = $e->getCode() === 404 ? 404 : 500;
            $response = [
                'error' => $e->getMessage(),
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    private function getMarkList($details)
    {
        $markList = [];
        $masterCategoryModel = new MasterCategoryModel();
        $masterSubCategoryModel = new MasterSubCategoryModel();

        // Decode the details string (e.g., {master_id:[master_subcategory_id]})
        $decodedDetails = json_decode($details, true);

        if ($decodedDetails && is_array($decodedDetails)) {
            foreach ($decodedDetails as $masterId => $subCategoryIds) {
                // Fetch the master category
                $masterCategory = $masterCategoryModel->getCategoryById($masterId);

                if ($masterCategory) {
                    // Fetch related subcategories
                    $subCategories = [];
                    foreach ($subCategoryIds as $subCategoryId) {
                        $subCategory = $masterSubCategoryModel->getSubCategoryById($subCategoryId);
                        if ($subCategory) {
                            $subCategories[] = $subCategory;
                        }
                    }

                    // Add master category with subcategories to the mark_list
                    $markList[] = [
                        'id'       => $masterId,
                        'title'    => $masterCategory['title'],
                        'children' => $subCategories,
                    ];
                }
            }
        }

        return $markList;
    }
    public function getAllSites()
    {
        try {
            // Load the Quotation Model
            $quotationModel = new QuotationModel();

            // Retrieve all quotations
            $quotations = $quotationModel->where('status', 'sale')->findAll();

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
}
