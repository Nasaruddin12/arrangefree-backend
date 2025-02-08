<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\InteriorTransactionModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Database\Exceptions\DatabaseException;
use Exception;

class InteriorTransactionController extends BaseController
{
    use ResponseTrait;

    protected $transactionModel;

    public function __construct()
    {
        $this->transactionModel = new InteriorTransactionModel();
    }

    /**
     * Fetch transactions by quotation_id
     */
    public function index()
    {
        try {
            $quotation_id = $this->request->getVar('id');
            if (!$quotation_id) {
                return $this->failValidationError('Quotation ID is required.');
            }

            // Get filters and pagination parameters
            $startDate = $this->request->getVar('start_date');
            $endDate = $this->request->getVar('end_date');
            $page = (int) ($this->request->getVar('page') ?? 1);
            $perPage = (int) ($this->request->getVar('perPage') ?? 10);
            $offset = ($page - 1) * $perPage;
            $search = $this->request->getVar('search');

            // Base query
            $query = $this->transactionModel
                ->select('interior_transactions.*, quotations.customer_name')
                ->join('quotations', 'interior_transactions.quotation_id = quotations.id', 'left')
                ->where('interior_transactions.quotation_id', $quotation_id);

            // Apply date filters
            if ($startDate && $endDate) {
                $query->where('interior_transactions.date >=', date('Y-m-d', strtotime($startDate)))
                    ->where('interior_transactions.date <=', date('Y-m-d', strtotime($endDate)));
            }

            // Apply search filter
            if ($search) {
                $query->groupStart()
                    ->like('interior_transactions.description', $search)
                    ->orLike('interior_transactions.remark', $search)
                    ->groupEnd();
            }

            // Sort by created_at (Descending)
            $query->orderBy('interior_transactions.created_at', 'DESC');

            // Fetch paginated results
            $transactions = $query->limit($perPage, $offset)->get();
            $result = $transactions->getNumRows() > 0 ? $transactions->getResultArray() : [];

            // Count total records (excluding pagination)
            $totalRecords = $this->transactionModel
                ->where('quotation_id', $quotation_id);

            if ($startDate && $endDate) {
                $totalRecords->where('date >=', date('Y-m-d', strtotime($startDate)))
                    ->where('date <=', date('Y-m-d', strtotime($endDate)));
            }

            if ($search) {
                $totalRecords->groupStart()
                    ->like('description', $search)
                    ->orLike('remark', $search)
                    ->groupEnd();
            }

            $totalRecords = $totalRecords->countAllResults();

            // Calculate total income
            $totalIncome = $this->transactionModel
                ->selectSum('amount')
                ->where('quotation_id', $quotation_id)
                ->where('transaction_type', 'Income')
                ->get()
                ->getRow()
                ->amount ?? 0;

            // Calculate total expense
            $totalExpense = $this->transactionModel
                ->selectSum('amount')
                ->where('quotation_id', $quotation_id)
                ->where('transaction_type', 'Expense')
                ->get()
                ->getRow()
                ->amount ?? 0;

            if (empty($result)) {
                return $this->failNotFound('No transactions found.');
            }

            return $this->respond([
                'status'    => 200,
                'message'   => 'Transactions retrieved successfully',
                'data'      => $result,
                'pagination' => [
                    'currentPage'  => $page,
                    'perPage'      => $perPage,
                    'totalPages'   => ceil($totalRecords / $perPage),
                    'totalRecords' => $totalRecords
                ],
                'totals' => [
                    'income' => $totalIncome,
                    'expense' => $totalExpense
                ]
            ], 200);
        } catch (DatabaseException $e) {
            return $this->failServerError('Database error: ' . $e->getMessage());
        } catch (Exception $e) {
            return $this->failServerError('An unexpected error occurred: ' . $e->getMessage());
        }
    }


    public function getOfficeExpense()
    {
        try {
            $type = $this->request->getVar("type");
            if (!$type) {
                throw new \Exception('Type is required', 400);
            }

            $transactions = $this->transactionModel->where('quotation_id', null)
                ->where('type', $type)
                ->findAll();

            if (empty($transactions)) {
                return $this->failNotFound('No transactions found for the given quotation ID');
            }
            return $this->respond([
                'status'  => 200,
                'message' => 'Transactions retrieved successfully',
                'data'    => $transactions
            ], 200);
        } catch (DatabaseException $e) {
            return $this->failServerError('Database error: ' . $e->getMessage());
        } catch (Exception $e) {
            return $this->failServerError('An unexpected error occurred: ' . $e->getMessage());
        }
    }
    public function getAll()
    {
        try {
            $type = $this->request->getVar("type");
            if (!$type) {
                throw new \Exception('Type is required', 400);
            }

            // Get filters and pagination parameters
            $startDate = $this->request->getVar('start_date');
            $endDate = $this->request->getVar('end_date');
            $page = $this->request->getVar('page') ?? 1;
            $perPage = $this->request->getVar('perPage') ?? 10;
            $offset = ($page - 1) * $perPage;
            $search = $this->request->getVar('search');

            // Base query
            $query = $this->transactionModel
                ->select('interior_transactions.*, quotations.customer_name')
                ->join('quotations', 'interior_transactions.quotation_id = quotations.id', 'left')
                ->where('interior_transactions.type', $type);

            // Apply date filters
            if ($startDate && $endDate) {
                $query->where('interior_transactions.date >=', date('Y-m-d', strtotime($startDate)))
                    ->where('interior_transactions.date <=', date('Y-m-d', strtotime($endDate)));
            }

            // Apply search filter
            if ($search) {
                $query->groupStart()
                    ->like('interior_transactions.description', $search)
                    ->orLike('interior_transactions.remark', $search)
                    ->groupEnd();
            }

            // Sort by created_at (Descending)
            $query->orderBy('interior_transactions.created_at', 'DESC');

            // Fetch paginated results
            $transactions = $query->limit($perPage, $offset)->get();
            $result = $transactions->getNumRows() > 0 ? $transactions->getResultArray() : [];

            // Count total records (excluding pagination)
            $totalRecordsQuery = $this->transactionModel->where('interior_transactions.type', $type);

            if ($startDate && $endDate) {
                $totalRecordsQuery->where('interior_transactions.date >=', date('Y-m-d', strtotime($startDate)))
                    ->where('interior_transactions.date <=', date('Y-m-d', strtotime($endDate)));
            }

            if ($search) {
                $totalRecordsQuery->groupStart()
                    ->like('interior_transactions.description', $search)
                    ->orLike('interior_transactions.remark', $search)
                    ->groupEnd();
            }

            $totalRecords = $totalRecordsQuery->countAllResults();

            // Calculate total income
            $totalIncome = $this->transactionModel
                ->selectSum('interior_transactions.amount')
                ->where('interior_transactions.type', $type)
                ->where('interior_transactions.transaction_type', 'Income')
                ->get()
                ->getRow()
                ->amount ?? 0;

            // Calculate total expense
            $totalExpense = $this->transactionModel
                ->selectSum('interior_transactions.amount')
                ->where('interior_transactions.type', $type)
                ->where('interior_transactions.transaction_type', 'Expense')
                ->get()
                ->getRow()
                ->amount ?? 0;

            if (empty($result)) {
                return $this->failNotFound('No transactions found.');
            }

            return $this->respond([
                'status'    => 200,
                'message'   => 'Transactions retrieved successfully',
                'data'      => $result,
                'pagination' => [
                    'currentPage'  => $page,
                    'perPage'      => $perPage,
                    'totalPages'   => ceil($totalRecords / $perPage),
                    'totalRecords' => $totalRecords
                ],
                'totals' => [
                    'income' => $totalIncome,
                    'expense' => $totalExpense
                ]
            ], 200);
        } catch (DatabaseException $e) {
            return $this->failServerError('Database error: ' . $e->getMessage());
        } catch (Exception $e) {
            return $this->failServerError('An unexpected error occurred: ' . $e->getMessage());
        }
    }

    /**
     * Create a new transaction
     */
    public function create()
    {
        $data = $this->request->getJSON(true); // Get the JSON input as an array

        // Check if the input is an array
        if (!is_array($data)) {
            return $this->failValidationErrors('Invalid data format. Expected an array of transactions.');
        }

        $errors = [];
        $successCount = 0;

        foreach ($data as $transaction) {
            // Validate each transaction
            if (!$this->transactionModel->validate($transaction)) {
                $errors[] = [
                    'transaction' => $transaction,
                    'errors' => $this->transactionModel->errors(),
                ];
                continue;
            }

            try {
                // Insert the transaction into the database
                if ($this->transactionModel->insert($transaction)) {
                    $successCount++;
                } else {
                    $errors[] = [
                        'transaction' => $transaction,
                        'errors' => $this->transactionModel->errors(),
                    ];
                }
            } catch (DatabaseException $e) {
                $errors[] = [
                    'transaction' => $transaction,
                    'error' => 'Database error: ' . $e->getMessage(),
                ];
            } catch (Exception $e) {
                $errors[] = [
                    'transaction' => $transaction,
                    'error' => 'Unexpected error: ' . $e->getMessage(),
                ];
            }
        }

        // Build the response
        if (!empty($errors)) {
            return $this->respond([
                'message' => 'Some transactions failed to create',
                'status' => 207,
                'success_count' => $successCount,
                'errors' => $errors,
            ], 207); // 207 Multi-Status for partial success
        }

        return $this->respondCreated([
            'message' => 'All transactions created successfully',
            'status' => 201,
            'success_count' => $successCount,
        ]);
    }
    /**
     * Fetch a single transaction by ID
     */
    public function show($id = null)
    {
        try {
            $transaction = $this->transactionModel->find($id);

            if (!$transaction) {
                return $this->failNotFound('Transaction not found');
            }

            return $this->respond([
                'status'  => 200,
                'message' => 'Transactions retrieved successfully',
                'data'    => $transaction
            ], 200);
        } catch (Exception $e) {
            return $this->failServerError('An error occurred while fetching the transaction: ' . $e->getMessage());
        }
    }

    /**
     * Update a transaction by ID
     */
    public function update($id = null)
    {
        try {
            $transaction = $this->transactionModel->find($id);

            if (!$transaction) {
                return $this->failNotFound('Transaction not found');
            }

            $data = $this->request->getJSON(true);

            // Validate incoming data
            if (!$this->transactionModel->validate($data)) {
                return $this->failValidationErrors($this->transactionModel->errors());
            }

            // Update data in the database
            if (!$this->transactionModel->update($id, $data)) {
                return $this->fail($this->transactionModel->errors(), 400);
            }

            return $this->respond([
                'status'  => 200,
                'message' => 'Transactions updated successfully',
            ], 200);
        } catch (DatabaseException $e) {
            return $this->failServerError('Database error: ' . $e->getMessage());
        } catch (Exception $e) {
            return $this->failServerError('An unexpected error occurred: ' . $e->getMessage());
        }
    }

    /**
     * Delete a transaction by ID
     */
    public function delete($id = null)
    {
        try {
            $transaction = $this->transactionModel->find($id);

            if (!$transaction) {
                return $this->failNotFound('Transaction not found');
            }

            // Delete the transaction from the database
            if (!$this->transactionModel->delete($id)) {
                return $this->fail('Failed to delete transaction');
            }

            return $this->respond([
                'status'  => 200,
                'message' => 'Transactions deleted successfully',
            ], 200);
        } catch (DatabaseException $e) {
            return $this->failServerError('Database error: ' . $e->getMessage());
        } catch (Exception $e) {
            return $this->failServerError('An unexpected error occurred: ' . $e->getMessage());
        }
    }
}
