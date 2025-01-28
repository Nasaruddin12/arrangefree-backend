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
    public function index($quotation_id)
    {
        try {
            $transactions = $this->transactionModel->where('quotation_id', $quotation_id)->findAll();

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
            // Get start_date and end_date from the request (use input->getVar() to handle incoming parameters)
            $startDate = $this->request->getVar('start_date');
            $endDate = $this->request->getVar('end_date');

            // Get pagination parameters (page and perPage)
            $page = $this->request->getVar('page') ?? 1; // Default to page 1 if not provided
            $perPage = $this->request->getVar('perPage') ?? 10; // Default to 10 items per page if not provided

            // If no dates are provided, default to the current month (start and end date)
            if (!$startDate || !$endDate) {
                // Get the first day of the current month
                $startDate = date('Y-m-01');

                // Get the last day of the current month
                $endDate = date('Y-m-t');
            } else {
                // Convert date to correct format if needed (e.g., 'Y-m-d')
                $startDate = date('Y-m-d', strtotime($startDate));
                $endDate = date('Y-m-d', strtotime($endDate));
            }

            // Calculate the offset based on the current page and items per page
            $offset = ($page - 1) * $perPage;

            // Query the database for transactions within the specified date range
            // Assuming 'interior_transactions' has a 'quotation_id' field to join with the 'quotations' table
            $transactions = $this->transactionModel
                ->select('interior_transactions.*, quotations.customer_name')
                ->join('quotations', 'interior_transactions.quotation_id = quotations.id', 'left')
                ->where('interior_transactions.date >=', $startDate)
                ->where('interior_transactions.date <=', $endDate)
                ->limit($perPage, $offset)
                ->get();

            $transactions = $this->transactionModel
                ->select('interior_transactions.*, quotations.customer_name')
                ->join('quotations', 'interior_transactions.quotation_id = quotations.id', 'left')
                ->where('interior_transactions.date >=', $startDate)
                ->where('interior_transactions.date <=', $endDate)
                ->limit($perPage, $offset)
                ->get();

            // Check for data
            if ($transactions->getNumRows() > 0) {
                $result = $transactions->getResultArray();
            } else {
                $result = [];
            }
            // Count the total number of transactions for pagination
            $totalTransactions = $this->transactionModel
                ->where('interior_transactions.date >=', $startDate)
                ->where('interior_transactions.date <=', $endDate)
                ->countAllResults();

            $totalIncome = $this->transactionModel
                ->where('interior_transactions.date >=', $startDate)
                ->where('interior_transactions.date <=', $endDate)
                ->where('interior_transactions.transaction_type', 'Income') // Assuming 'type' field exists for income/expense
                ->selectSum('interior_transactions.amount') // Sum the 'amount' field
                ->get()
                ->getRow()->amount;

            $totalExpense = $this->transactionModel
                ->where('interior_transactions.date >=', $startDate)
                ->where('interior_transactions.date <=', $endDate)
                ->where('interior_transactions.transaction_type', 'Expense') // Assuming 'type' field exists for income/expense
                ->selectSum('interior_transactions.amount') // Sum the 'amount' field
                ->get()
                ->getRow()->amount;

            if (empty($result)) {
                return $this->failNotFound('No transactions found for the given date range');
            }

            return $this->respond([
                'status'    => 200,
                'message'   => 'Transactions retrieved successfully',
                'data'      => $result,
                'pagination' => [
                    'currentPage'  => $page,
                    'perPage'      => $perPage,
                    'totalPages'   => ceil($totalTransactions / $perPage),
                    'totalRecords' => $totalTransactions
                ],
                'totals' => [
                    'income' => $totalIncome ?? 0, // Default to 0 if null
                    'expense' => $totalExpense ?? 0 // Default to 0 if null
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
