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
            $data = json_decode($this->request->getBody(), true);
            if (empty($data['user_id']) || empty($data['subject'])) {
                return $this->failValidationError('User ID and Subject are required.');
            }

            // Generate Ticket UID
            $ticketUID = $this->ticketModel->generateTicketUID();

            $ticketData = [
                'ticket_uid' => $ticketUID,
                'user_id'    => $data['user_id'],
                'subject'    => $data['subject'],
                'file'       => $data['file'],
                'status'     => 'open',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            // Insert and get ID
            $insertedId = $this->ticketModel->insert($ticketData, true);

            if (!$insertedId) {
                return $this->failServerError('Failed to create ticket.');
            }

            // Add ID to response data
            $ticketData['id'] = $insertedId;

            return $this->respondCreated([
                'status'  => 201,
                'message' => 'Ticket created successfully.',
                'data'    => $ticketData
            ]);
        } catch (Exception $e) {
            log_message('error', 'Create Ticket Error: ' . $e->getMessage());
            return $this->failServerError('Something went wrong.' .  $e->getMessage());
        }
    }


    // Get all tickets
    public function getAllTickets()
    {
        try {
            $limit       = $this->request->getVar('limit') ?? 10; // Default 10 items per page
            $page        = $this->request->getVar('page') ?? 1; // Default page 1
            $startDate   = $this->request->getVar('start_date'); // Start date filter
            $endDate     = $this->request->getVar('end_date');   // End date filter
            $status      = $this->request->getVar('status'); // Status filter
            $searchQuery = $this->request->getVar('search'); // Unified search for user_name or ticket_id

            $query = $this->ticketModel
                ->select('tickets.*, af_customers.name as user_name')
                ->join('af_customers', 'af_customers.id = tickets.user_id', 'left');

            // Apply unified search filter (Ticket ID or User Name)
            if (!empty($searchQuery)) {
                $query->groupStart()
                    ->like('af_customers.name', $searchQuery) // User name search
                    ->orLike('tickets.id', $searchQuery) // Ticket ID search (allows partial match)
                    ->groupEnd();
            }

            // Filter by status
            if ($status) {
                $query->where('tickets.status', $status);
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

    public function getTicketsByUserId($userId = null)
    {
        try {
            if (empty($userId)) {
                return $this->failValidationError('User ID is required.');
            }

            $tickets = $this->ticketModel
                ->where('user_id', $userId)
                ->orderBy('created_at', 'DESC')
                ->findAll();

            return $this->respond([
                'status'  => 200,
                'message' => 'Tickets fetched successfully.',
                'data'    => $tickets
            ]);
        } catch (Exception $e) {
            log_message('error', 'Get Tickets Error: ' . $e->getMessage());
            return $this->failServerError('Something went wrong. ' . $e->getMessage());
        }
    }


    // Add a message to a ticket
    public function addMessage()
    {
        try {
            $data = $this->request->getJSON(true);

            if (empty($data['ticket_id']) || empty($data['message'])) {
                return $this->failValidationError('Ticket ID, Sender ID, and Message are required.');
            }

            if (!$this->ticketModel->find($data['ticket_id'])) {
                return $this->failNotFound('Ticket not found.');
            }

            $messageData = [
                'ticket_id' => $data['ticket_id'],
                'user_id' => $data['user_id'],
                'sender_id' => $data['sender_id'],
                'created_by' => $data['created_by'],
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
    public function getTicketById($ticketId)
    {
        try {
            $ticket = $this->ticketModel
                ->select('tickets.*, af_customers.name as user_name')
                ->join('af_customers', 'af_customers.id = tickets.user_id', 'left')
                ->where('tickets.id', $ticketId)
                ->first();

            if (!$ticket) {
                return $this->failNotFound('Ticket not found.');
            }

            $messages = $this->ticketMessageModel
                ->where('ticket_id', $ticketId)
                ->orderBy('created_at', 'ASC')
                ->findAll();

            return $this->respond([
                'status'  => 200,
                'message' => 'Ticket retrieved successfully.',
                'data'    => [
                    'ticket'   => $ticket,
                    'messages' => $messages
                ]
            ]);
        } catch (Exception $e) {
            log_message('error', 'Get Ticket By ID Error: ' . $e->getMessage());
            return $this->failServerError('Failed to fetch ticket details.');
        }
    }
    public function uploadFile()
    {
        try {
            $file = $this->request->getFile('file');

            if (!$file->isValid() || $file->hasMoved()) {
                return $this->failValidationErrors('Invalid file upload or file already moved.');
            }

            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp'];

            // Get file extension
            $extension = $file->getExtension();

            if (!in_array($extension, $allowedExtensions)) {
                return $this->failValidationErrors("Invalid file type. Allowed types: " . implode(', ', $allowedExtensions));
            }

            // Define upload directory
            $uploadPath = 'public/uploads/ticket/';

            // Generate new random name and move file
            $newName = $file->getRandomName();
            $file->move($uploadPath, $newName);

            // Generate file URL


            // Response
            return $this->respondCreated([
                'message' => 'File uploaded successfully',
                'file_path' => $uploadPath . $newName
            ]);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            return $this->failServerError("An error occurred while uploading the file: " . $e->getMessage());
        }
    }
}
