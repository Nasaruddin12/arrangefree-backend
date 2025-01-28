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
            // Get start_date and end_date from the request
            $startDate = $this->request->getVar('start_date');
            $endDate = $this->request->getVar('end_date');
    
            // Get pagination parameters (page and perPage)
            $page = $this->request->getVar('page') ?? 1;
            $perPage = $this->request->getVar('perPage') ?? 10;
    
            // Default to the current month's date range if no dates are provided
            if (!$startDate || !$endDate) {
                $startDate = date('Y-m-01');
                $endDate = date('Y-m-t');
            } else {
                $startDate = date('Y-m-d', strtotime($startDate));
                $endDate = date('Y-m-d', strtotime($endDate));
            }
    
            // Calculate the offset based on the current page and items per page
            $offset = ($page - 1) * $perPage;
    
            // Query the database for transactions within the specified date range
            $transactions = $this->transactionModel
                ->select('interior_transactions.*, quotations.customer_name')
                ->join('quotations', 'interior_transactions.quotation_id = quotations.id', 'left')
                ->where('interior_transactions.date >=', $startDate)
                ->where('interior_transactions.date <=', $endDate)
                ->limit($perPage, $offset)
                ->get();
    
            // Count the total number of transactions for pagination
            $totalTransactions = $this->transactionModel
                ->where('interior_transactions.date >=', $startDate)
                ->where('interior_transactions.date <=', $endDate)
                ->countAllResults();
    
            // If no transactions are found, return a 404 response
            if (empty($transactions)) {
                return $this->failNotFound('No transactions found for the given date range');
            }
    
            return $this->respond([
                'status'    => 200,
                'message'   => 'Transactions retrieved successfully',
                'data'      => $transactions,
                'pagination' => [
                    'currentPage'  => $page,
                    'perPage'      => $perPage,
                    'totalPages'   => ceil($totalTransactions / $perPage),
                    'totalRecords' => $totalTransactions
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
