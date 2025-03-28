<?php

namespace App\Controllers;

use App\Models\BookingExpenseModel;
use CodeIgniter\RESTful\ResourceController;

class BookingExpenseController extends ResourceController
{
    protected $modelName = 'App\Models\BookingExpenseModel';
    protected $format    = 'json';

    // Add a new expense
    public function addExpense()
    {
        try {
            $data = $this->request->getJSON(true);

            // Validate required fields
            if (empty($data['booking_id']) || empty($data['amount']) || empty($data['category']) || empty($data['payment_method'])) {
                return $this->failValidationErrors('Booking ID, Amount, Category, and Payment Method are required.');
            }

            $expenseData = [
                'booking_id'     => $data['booking_id'],
                'amount'         => $data['amount'],
                'category'       => $data['category'],
                'payment_method' => $data['payment_method'],
                'transaction_id' => $data['transaction_id'] ?? '',
                'vendor_or_client' => $data['vendor_or_client'] ?? '',
                'description'    => $data['description'] ?? '',
                'created_at'     => date('Y-m-d H:i:s'),
            ];

            $this->model->insert($expenseData);

            return $this->respond([
                'status'  => 200,
                'message' => 'Expense added successfully.',
                'data'    => $expenseData
            ]);
        } catch (\Exception $e) {
            return $this->failServerError('Something went wrong: ' . $e->getMessage());
        }
    }

    // Fetch all expenses for a booking
    public function getExpenses($booking_id = null)
    {
        if (!$booking_id) {
            return $this->failValidationErrors('Booking ID is required.');
        }

        $expenses = $this->model->where('booking_id', $booking_id)->findAll();

        return $this->respond([
            'status'  => 200,
            'message' => 'Expenses fetched successfully.',
            'data'    => $expenses
        ]);
    }

    // Delete an expense
    public function deleteExpense($id = null)
    {
        if (!$id || !$this->model->find($id)) {
            return $this->failNotFound('Expense not found.');
        }

        $this->model->delete($id);

        return $this->respondDeleted([
            'status'  => 200,
            'message' => 'Expense deleted successfully.',
        ]);
    }
}
