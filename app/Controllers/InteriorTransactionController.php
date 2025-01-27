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

            // Query the database for transactions within the specified date range
            // Assuming 'interior_transactions' has a 'quotation_id' field to join with the 'quotations' table
            $transactions = $this->transactionModel
                ->select('interior_transactions.*, quotations.customer_name') // Select fields from both tables
                ->join('quotations', 'interior_transactions.quotation_id = quotations.id', 'left') // Join the quotations table
                ->where('interior_transactions.date >=', $startDate)
                ->where('interior_transactions.date <=', $endDate)
                ->findAll();

            if (empty($transactions)) {
                return $this->failNotFound('No transactions found for the given date range');
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

    /**
     * Create a new transaction
     */
    public function create()
    {
        $data = $this->request->getJSON(true);

        // Validate incoming data
        if (!$this->transactionModel->validate($data)) {
            return $this->failValidationErrors($this->transactionModel->errors());
        }

        try {
            // Insert data into the database
            if (!$this->transactionModel->insert($data)) {
                return $this->fail($this->transactionModel->errors(), 400);
            }

            return $this->respondCreated(['message' => 'Transaction created successfully', 'status' => 201]);
        } catch (DatabaseException $e) {
            return $this->failServerError('Database error: ' . $e->getMessage());
        } catch (Exception $e) {
            return $this->failServerError('An unexpected error occurred: ' . $e->getMessage());
        }
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
