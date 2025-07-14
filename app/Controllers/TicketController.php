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
        $db = db_connect();
        $ticketModel = new \App\Models\TicketModel();
        $ticketMessageModel = new \App\Models\TicketMessageModel();

        try {
            $db->transBegin(); // Start transaction

            $data = json_decode($this->request->getBody(), true);

            // Dynamic validation rules based on user_type
            $rules = [
                'user_type' => 'required|in_list[customer,partner]',
                'subject'   => 'required|string|max_length[255]',
                'priority'  => 'required|in_list[low,medium,high]',
                'category'  => 'required',
                'message'   => 'permit_empty|string',
                'status'    => 'permit_empty|in_list[open,in_progress,closed]',
                'file'      => 'permit_empty',
            ];

            if ($data['user_type'] === 'customer') {
                $rules['user_id'] = 'required|integer';
            } elseif ($data['user_type'] === 'partner') {
                $rules['partner_id'] = 'required|integer';
            }

            $messages = [
                'user_type' => [
                    'required' => 'User type is required.',
                    'in_list'  => 'User type must be either customer or partner.'
                ],
                'subject' => [
                    'required'   => 'Subject is required.',
                    'max_length' => 'Subject must not exceed 255 characters.'
                ],
                'priority' => [
                    'required' => 'Priority is required.',
                    'in_list'  => 'Priority must be either low, medium, or high.'
                ],
                'category' => [
                    'required' => 'Category is required.'
                ],
                'message' => [
                    'string' => 'Message must be a string.'
                ],
            ];

            // Validate input
            if (!$this->validateData($data, $rules, $messages)) {
                throw new \Exception(json_encode($this->validator->getErrors()), 400);
            }
            // ✅ Check if referenced user/partner exists (foreign key protection)
            if ($data['user_type'] === 'partner') {
                $partnerExists = $db->table('partners')
                    ->where('id', $data['partner_id'] ?? 0)
                    ->countAllResults();

                if (!$partnerExists) {
                    throw new \Exception(json_encode(['partner_id' => 'Invalid partner_id. Partner not found.']), 400);
                }
            } elseif ($data['user_type'] === 'customer') {
                $userExists = $db->table('af_customers')
                    ->where('id', $data['user_id'] ?? 0)
                    ->countAllResults();

                if (!$userExists) {
                    throw new \Exception(json_encode(['user_id' => 'Invalid user_id. Customer not found.']), 400);
                }
            }

            // Generate UID and prepare ticket data
            $ticketUID = $ticketModel->generateTicketUID();
            $ticketData = [
                'ticket_uid'        => $ticketUID,
                'user_type'         => $data['user_type'],
                'user_id'           => $data['user_id'] ?? null,
                'partner_id'        => $data['partner_id'] ?? null,
                'booking_id'        => $data['booking_id'] ?? null,
                'task_id'           => $data['task_id'] ?? null,
                'subject'           => $data['subject'],
                'file'              => $data['file'] ?? null,
                'status'            => 'open',
                'priority'          => $data['priority'],
                'category'          => $data['category'],
                'assigned_admin_id' => $data['assigned_admin_id'] ?? null,
                // 'created_at'        => date('Y-m-d H:i:s'),
                // 'updated_at'        => date('Y-m-d H:i:s'),
            ];



            // Insert ticket
            if (!$ticketModel->insert($ticketData)) {
                throw new \Exception(json_encode($ticketModel->errors()), 400);
            }

            if ($ticketModel->db->error()['code']) {
                throw new \Exception($ticketModel->db->error()['message'], 500);
            }

            $ticketID = $ticketModel->insertID();
            if (!$ticketID) {
                throw new \Exception('Failed to create ticket.', 500);
            }

            if ($data['message'] === null && $data['file'] === null) {
                // If no message or file, skip inserting a ticket message
                $db->transCommit();
                return $this->respond([
                    'status'  => 201,
                    'message' => 'Ticket created successfully without a message.',
                    'data'    => [
                        'ticket_id'  => $ticketID,
                        'ticket_uid' => $ticketUID
                    ]
                ], 201);
            }

            // Insert ticket message
            $messageData = [
                'ticket_id'    => $ticketID,
                'sender_type'  => $data['user_type'],
                'sender_id'    => $data['user_id'] ?? $data['partner_id'],
                'message'      => $data['message'],
                'file'         => $data['file'] ?? null,
                'is_read_by_admin' => false,
                'is_read_by_user'  => true,
                'created_at'   => date('Y-m-d H:i:s'),
            ];

            if (!$ticketMessageModel->insert($messageData)) {
                throw new \Exception(json_encode($ticketMessageModel->errors()), 400);
            }

            if ($ticketMessageModel->db->error()['code']) {
                throw new \Exception($ticketMessageModel->db->error()['message'], 500);
            }

            $db->transCommit();

            return $this->respond([
                'status'  => 201,
                'message' => 'Ticket created successfully.',
                'data'    => [
                    'ticket_id'  => $ticketID,
                    'ticket_uid' => $ticketUID
                ]
            ], 201);
        } catch (\Exception $e) {
            $db->transRollback();

            $statusCode = $e->getCode() === 400 ? 400 : 500;

            // If the exception message is JSON (from validation), decode it
            $decodedMessage = json_decode($e->getMessage(), true);
            $errorData = is_array($decodedMessage)
                ? ['validation' => $decodedMessage]
                : ['error' => $e->getMessage()];

            return $this->respond([
                'status' => $statusCode,
                'error' => $errorData
            ], $statusCode);
        } catch (\CodeIgniter\Database\Exceptions\DatabaseException $e) {
            return $this->failServerError('Database error: ' . $e->getMessage());
        }
    }

    // Get all tickets
    public function getAllTickets()
    {
        try {
            $limit       = $this->request->getVar('limit') ?? 10;
            $page        = $this->request->getVar('page') ?? 1;
            $startDate   = $this->request->getVar('start_date');
            $endDate     = $this->request->getVar('end_date');
            $status      = $this->request->getVar('status');
            $searchQuery = $this->request->getVar('search');

            $query = $this->ticketModel
                ->select('tickets.*, af_customers.name as user_name')
                ->join('af_customers', 'af_customers.id = tickets.user_id', 'left');

            // Apply search
            if (!empty($searchQuery)) {
                $query->groupStart()
                    ->like('af_customers.name', $searchQuery)
                    ->orLike('tickets.id', $searchQuery)
                    ->groupEnd();
            }

            // Status filter
            if ($status) {
                $query->where('tickets.status', $status);
            }

            // Date range filter
            if ($startDate && $endDate) {
                $query->where('tickets.created_at >=', $startDate)
                    ->where('tickets.created_at <=', $endDate);
            }

            // Fetch paginated result
            $tickets = $query->paginate($limit, 'default', $page);
            $pager   = $this->ticketModel->pager;

            // ✅ Append unread message counts for each ticket
            $ticketMessageModel = new \App\Models\TicketMessageModel();

            foreach ($tickets as &$ticket) {
                $ticket_id = $ticket['id'];

                $ticket['unread_admin_messages'] = $ticketMessageModel
                    ->where('ticket_id', $ticket_id)
                    ->where('is_read_by_admin', false)
                    ->countAllResults();

                $ticket['unread_user_messages'] = $ticketMessageModel
                    ->where('ticket_id', $ticket_id)
                    ->where('is_read_by_user', false)
                    ->countAllResults();
            }

            return $this->respond([
                'status'     => 200,
                'message'    => 'Tickets retrieved successfully.',
                'data'       => $tickets,
                'pagination' => [
                    'current_page' => $pager->getCurrentPage(),
                    'per_page'     => $limit,
                    'total'        => $pager->getTotal(),
                    'last_page'    => $pager->getPageCount(),
                ]
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Get Tickets Error: ' . $e->getMessage());
            return $this->respond([
                'status' => 500,
                'error'  => 'Failed to fetch tickets.'
            ], 500);
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


    public function getTicketsByPartnerId($partnerID = null)
    {
        try {
            if (empty($partnerID)) {
                return $this->respond([
                    'status'  => 422,
                    'message' => 'Partner ID is required.',
                    'data'    => [],
                ], 422);
            }

            $tickets = $this->ticketModel
                ->where('partner_id', $partnerID)
                ->orderBy('created_at', 'DESC')
                ->findAll();

            if (empty($tickets)) {
                return $this->respond([
                    'status'  => 204,
                    'message' => 'No tickets found for this partner.',
                    'data'    => [],
                ], 200); // HTTP 200 OK but custom message code 204
            }

            return $this->respond([
                'status'  => 200,
                'message' => 'Tickets fetched successfully.',
                'data'    => $tickets
            ], 200);
        } catch (Exception $e) {
            log_message('error', '❌ Get Tickets Error: ' . $e->getMessage());
            return $this->respond([
                'status'  => 500,
                'message' => 'Internal server error. Please try again later.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    // Add a message to a ticket
    public function addMessage()
    {
        try {
            $data = $this->request->getJSON(true);

            // ✅ Define validation rules
            $rules = [
                'ticket_id'   => 'required|integer',
                'sender_type' => 'required|in_list[customer,partner,admin]',
                'sender_id'   => 'required|integer',
                'message'     => 'permit_empty|string',
                'file'        => 'permit_empty'
            ];

            // ✅ Validate input
            if (!$this->validateData($data, $rules)) {
                throw new \Exception('Validation', 400);
            }

            // ✅ Check if at least one of message or file is present
            if (empty($data['message']) && empty($data['file'])) {
                // Manually format the validation error like CI
                $customValidationError = [
                    'validation' => [
                        'message' => 'Either message or file is required.'
                    ]
                ];
                return $this->respond(['status' => 400, 'error' => $customValidationError], 400);
            }


            // ✅ Check if ticket exists
            if (!$this->ticketModel->find($data['ticket_id'])) {
                return $this->failNotFound('Ticket not found.');
            }

            // ✅ Set read flags based on sender_type
            $isReadByAdmin = ($data['sender_type'] === 'admin') ? true : false;
            $isReadByUser  = ($data['sender_type'] === 'admin') ? false : true;


            // ✅ Prepare message data
            $messageData = [
                'ticket_id'        => $data['ticket_id'],
                'sender_type'      => $data['sender_type'],
                'sender_id'        => $data['sender_id'],
                'message'          => $data['message'] ?? '',
                'file'             => $data['file'] ?? null,
                'is_read_by_admin' => $isReadByAdmin,
                'is_read_by_user'  => $isReadByUser,
                'created_at'       => date('Y-m-d H:i:s'),
            ];

            // ✅ Insert into DB
            if (!$this->ticketMessageModel->insert($messageData)) {
                throw new \Exception('Validation', 400);
            }

            if ($this->ticketMessageModel->db->error()['code']) {
                throw new \Exception($this->ticketMessageModel->db->error()['message'], 500);
            }

            return $this->respondCreated([
                'status'  => 201,
                'message' => 'Message added to ticket.',
                'data'    => $messageData
            ]);
        } catch (\Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;

            $errorData = $e->getCode() === 400
                ? ['validation' => $this->validator->getErrors() ?: $e->getMessage()]
                : $e->getMessage();

            return $this->respond([
                'status' => $statusCode,
                'error'  => $errorData
            ], $statusCode);
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

    public function markTicketAsRead()
    {
        $ticketModel = new \App\Models\TicketModel();
        $messageModel = new \App\Models\TicketMessageModel();
        $db = db_connect();

        try {
            $data = $this->request->getJSON(true);

            // Basic manual validation
            if (empty($data['ticket_id']) || empty($data['viewer_type'])) {
                return $this->respond([
                    'status' => 400,
                    'error' => [
                        'ticket_id'    => 'ticket_id is required.',
                        'viewer_type'  => 'viewer_type is required.'
                    ]
                ], 400);
            }

            $ticketID = $data['ticket_id'];
            $viewerType = $data['viewer_type']; // "admin", "customer", "partner"

            // Check if ticket exists
            $ticket = $ticketModel->find($ticketID);
            if (!$ticket) {
                return $this->respond([
                    'status' => 404,
                    'error'  => 'Ticket not found.'
                ], 404);
            }

            // Update status based on viewer type
            if ($viewerType === 'admin') {
                // Update ticket
                $ticketModel->update($ticketID, [
                    'admin_unread' => false,
                    'last_admin_viewed_at' => date('Y-m-d H:i:s'),
                ]);

                // Mark messages as read by admin
                $messageModel->set(['is_read_by_admin' => true])
                    ->where('ticket_id', $ticketID)
                    ->where('is_read_by_admin', false)
                    ->update();
            } elseif ($viewerType === 'customer' || $viewerType === 'partner') {
                // Mark messages as read by user
                $messageModel->where('ticket_id', $ticketID)
                    ->where('is_read_by_user', false)
                    ->set(['is_read_by_user' => true])
                    ->update();
            } else {
                return $this->respond([
                    'status' => 400,
                    'error'  => ['viewer_type' => 'Invalid viewer_type.']
                ], 400);
            }

            return $this->respond([
                'status' => 200,
                'message' => 'Ticket and messages marked as read successfully.'
            ], 200);
        } catch (\Throwable $e) {
            return $this->respond([
                'status' => 500,
                'error'  => $e->getMessage()
            ], 500);
        }
    }
}
