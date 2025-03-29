<?php

namespace App\Controllers;

use App\Models\TicketModel;
use App\Models\TicketMessageModel;
use CodeIgniter\RESTful\ResourceController;
use Exception;

class TicketController extends ResourceController
{
    protected $ticketModel;
    protected $ticketMessageModel;

    public function __construct()
    {
        $this->ticketModel = new TicketModel();
        $this->ticketMessageModel = new TicketMessageModel();
    }

    // Create a new ticket
    public function createTicket()
{
    try {
        $data = $this->request->getJSON(true);

        if (empty($data['user_id']) || empty($data['subject'])) {
            return $this->failValidationError('User ID and Subject are required.');
        }

        // Generate Ticket UID
        $ticketUID = $this->ticketModel->generateTicketUID();

        $ticketData = [
            'ticket_uid' => $ticketUID,
            'user_id'    => $data['user_id'],
            'subject'    => $data['subject'],
            'status'     => 'open',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if (!$this->ticketModel->insert($ticketData)) {
            return $this->failServerError('Failed to create ticket.');
        }

        return $this->respondCreated([
            'status'  => 201,
            'message' => 'Ticket created successfully.',
            'data'    => $ticketData
        ]);
    } catch (Exception $e) {
        log_message('error', 'Create Ticket Error: ' . $e->getMessage());
        return $this->failServerError('Something went wrong.');
    }
}


    // Get all tickets
    public function getAllTickets()
    {
        try {
            $limit     = $this->request->getVar('limit') ?? 10; // Default 10 items per page
            $page      = $this->request->getVar('page') ?? 1; // Default page 1
            $startDate = $this->request->getVar('start_date'); // Start date filter
            $endDate   = $this->request->getVar('end_date');   // End date filter
            $userName  = $this->request->getVar('user_name'); // User name search filter
            $status    = $this->request->getVar('status'); // Status filter
            $ticketId  = $this->request->getVar('ticket_id'); // Ticket ID filter

            $query = $this->ticketModel
                ->select('tickets.*, af_customers.name as user_name')
                ->join('af_customers', 'af_customers.id = tickets.user_id', 'left');

            // Filter by ticket ID (Exact match)
            if (!empty($ticketId)) {
                $query->where('tickets.id', $ticketId);
            }

            // Filter by status (Only show "pending" tickets if requested)
            if ($status) {
                $query->where('tickets.status', $status);
            }

            // Filter by user_name if provided
            if (!empty($userName)) {
                $query->like('af_customers.name', $userName);
            }

            // Apply date range filtering
            if ($startDate && $endDate) {
                $query->where('tickets.created_at >=', $startDate)
                    ->where('tickets.created_at <=', $endDate);
            }

            // Apply pagination
            $tickets = $query->paginate($limit, 'default', $page);
            $pager   = $this->ticketModel->pager;

            return $this->respond([
                'status'  => 200,
                'message' => 'Tickets retrieved successfully.',
                'data'    => $tickets,
                'pagination' => [
                    'current_page' => $pager->getCurrentPage(),
                    'per_page'     => $limit,
                    'total'        => $pager->getTotal(),
                    'last_page'    => $pager->getPageCount(),
                ]
            ]);
        } catch (Exception $e) {
            log_message('error', 'Get Tickets Error: ' . $e->getMessage());
            return $this->failServerError('Failed to fetch tickets.');
        }
    }

    // Update ticket status
    public function updateStatus($ticketId)
    {
        try {
            $data = $this->request->getJSON(true);

            if (!isset($data['status']) || !in_array($data['status'], ['open', 'in_progress', 'closed'])) {
                return $this->failValidationError('Invalid status value.');
            }

            if (!$this->ticketModel->find($ticketId)) {
                return $this->failNotFound('Ticket not found.');
            }

            $this->ticketModel->update($ticketId, [
                'status' => $data['status'],
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            return $this->respond([
                'status'  => 200,
                'message' => 'Ticket status updated successfully.'
            ]);
        } catch (Exception $e) {
            log_message('error', 'Update Ticket Status Error: ' . $e->getMessage());
            return $this->failServerError('Failed to update ticket status.');
        }
    }

    // Add a message to a ticket
    public function addMessage()
    {
        try {
            $data = $this->request->getJSON(true);

            if (empty($data['ticket_id']) || empty($data['sender_id']) || empty($data['message'])) {
                return $this->failValidationError('Ticket ID, Sender ID, and Message are required.');
            }

            if (!$this->ticketModel->find($data['ticket_id'])) {
                return $this->failNotFound('Ticket not found.');
            }

            $messageData = [
                'ticket_id' => $data['ticket_id'],
                'sender_id' => $data['sender_id'],
                'message'   => $data['message'],
                'created_at' => date('Y-m-d H:i:s'),
            ];

            if (!$this->ticketMessageModel->insert($messageData)) {
                return $this->failServerError('Failed to add message.');
            }

            return $this->respondCreated([
                'status'  => 201,
                'message' => 'Message added to ticket.',
                'data'    => $messageData
            ]);
        } catch (Exception $e) {
            log_message('error', 'Add Ticket Message Error: ' . $e->getMessage());
            return $this->failServerError('Something went wrong.');
        }
    }

    // Get all messages for a ticket
    public function getMessages($ticketId)
    {
        try {
            if (!$this->ticketModel->find($ticketId)) {
                return $this->failNotFound('Ticket not found.');
            }

            $messages = $this->ticketMessageModel->where('ticket_id', $ticketId)->findAll();

            return $this->respond([
                'status'  => 200,
                'message' => 'Messages retrieved successfully.',
                'data'    => $messages
            ]);
        } catch (Exception $e) {
            log_message('error', 'Get Ticket Messages Error: ' . $e->getMessage());
            return $this->failServerError('Failed to fetch messages.');
        }
    }
}
