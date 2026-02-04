<?php

namespace App\Controllers;

use App\Libraries\FirestoreService;
use App\Models\BookingAddressModel;
use App\Models\BookingsModel;
use App\Models\CustomerModel;
use App\Models\PartnerJobsModel;
use App\Models\PartnerJobItemModel;
use App\Models\PartnerJobStatusLogModel;
use App\Models\PartnerJobRequestModel;
use App\Models\PartnerJobAdjustmentsModel;
use App\Models\PartnerJobAdditionalItemsModel;
use App\Models\PartnerModel;
use CodeIgniter\RESTful\ResourceController;

class PartnerJobController extends ResourceController
{
    protected $format = 'json';

    protected $partnerJobsModel;
    protected $partnerJobItemModel;
    protected $partnerJobStatusLogModel;
    protected $partnerJobRequestModel;
    protected $db;

    public function __construct()
    {
        $this->partnerJobsModel = new PartnerJobsModel();
        $this->partnerJobItemModel = new PartnerJobItemModel();
        $this->partnerJobStatusLogModel = new PartnerJobStatusLogModel();
        $this->partnerJobRequestModel = new PartnerJobRequestModel();
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        try {
            $page = (int) ($this->request->getVar('page') ?? 1);
            $limit = (int) ($this->request->getVar('limit') ?? 10);
            $offset = ($page - 1) * $limit;

            $status = $this->request->getVar('status');
            $partnerId = $this->request->getVar('partner_id');
            $bookingId = $this->request->getVar('booking_id');
            $search = $this->request->getVar('search');

            $builder = $this->partnerJobsModel
                ->select('partner_jobs.*')
                ->orderBy('partner_jobs.created_at', 'DESC');

            if (!empty($status)) {
                $builder->where('partner_jobs.status', $status);
            }

            if (!empty($partnerId)) {
                $builder->where('partner_jobs.partner_id', (int) $partnerId);
            }

            if (!empty($bookingId)) {
                $builder->where('partner_jobs.booking_id', (int) $bookingId);
            }

            if (!empty($search)) {
                $builder->like('partner_jobs.job_id', $search);
            }

            $totalFiltered = $builder->countAllResults(false);

            $jobs = $builder->findAll($limit, $offset);

            return $this->respond([
                'status' => 200,
                'message' => 'Partner jobs retrieved successfully.',
                'data' => $jobs,
                'pagination' => [
                    'current_page'   => (int) $page,
                    'per_page'       => (int) $limit,
                    'total_records'  => (int) $totalFiltered,
                    'total_pages'    => $limit > 0 ? (int) ceil($totalFiltered / $limit) : 0,
                ],
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Partner jobs list error: ' . $e->getMessage());
            return $this->failServerError('Something went wrong while fetching partner jobs.');
        }
    }

    public function show($id = null)
    {
        try {
            $job = $this->partnerJobsModel->find((int) $id);
            if (!$job) {
                return $this->failNotFound('Partner job not found.');
            }

            $items = $this->partnerJobItemModel
                ->where('partner_job_id', (int) $id)
                ->findAll();

            $statusLogs = $this->partnerJobStatusLogModel
                ->where('partner_job_id', (int) $id)
                ->orderBy('created_at', 'DESC')
                ->findAll();

            $requests = $this->partnerJobRequestModel
                ->where('partner_job_id', (int) $id)
                ->orderBy('created_at', 'DESC')
                ->findAll();

            return $this->respond([
                'status' => 200,
                'message' => 'Partner job retrieved successfully.',
                'data' => [
                    'job' => $job,
                    'items' => $items,
                    'status_logs' => $statusLogs,
                    'requests' => $requests,
                ],
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Partner job fetch error: ' . $e->getMessage());
            return $this->failServerError('Something went wrong while fetching partner job.');
        }
    }

    public function details($id = null)
    {
        try {
            $partnerId = (int) ($this->request->getVar('partner_id') ?? 0);

            if ($partnerId <= 0) {
                return $this->failValidationErrors('partner_id is required.');
            }

            $job = $this->partnerJobsModel->find((int) $id);
            if (!$job) {
                return $this->failNotFound('Partner job not found.');
            }

            if ((int) ($job['partner_id'] ?? 0) !== $partnerId) {
                return $this->failValidationErrors('Job is not assigned to this partner.');
            }

            $items = $this->partnerJobItemModel
                ->where('partner_job_id', (int) $id)
                ->findAll();

            $items = $this->buildNestedItems($items);

            $statusLogs = $this->partnerJobStatusLogModel
                ->where('partner_job_id', (int) $id)
                ->orderBy('created_at', 'DESC')
                ->findAll();

            $requests = $this->partnerJobRequestModel
                ->where('partner_job_id', (int) $id)
                ->orderBy('created_at', 'DESC')
                ->findAll();

            $adjustmentsModel = new PartnerJobAdjustmentsModel();
            $additionalItemsModel = new PartnerJobAdditionalItemsModel();

            $adjustments = $adjustmentsModel
                ->where('partner_job_id', (int) $id)
                ->orderBy('created_at', 'DESC')
                ->findAll();

            $additionalItems = $additionalItemsModel
                ->where('partner_job_id', (int) $id)
                ->orderBy('created_at', 'DESC')
                ->findAll();

            $booking = null;
            $customer = null;
            $bookingAddress = null;

            if (!empty($job['booking_id'])) {
                $bookingModel = new BookingsModel();
                $bookingAddressModel = new BookingAddressModel();
                $customerModel = new CustomerModel();

                $booking = $bookingModel->find((int) $job['booking_id']);
                if ($booking) {
                    $customer = $customerModel->find((int) ($booking['user_id'] ?? 0));
                    $bookingAddress = $bookingAddressModel
                        ->where('booking_id', (int) $job['booking_id'])
                        ->first();
                }
            }

            return $this->respond([
                'status' => 200,
                'message' => 'Partner job details retrieved successfully.',
                'data' => [
                    'job' => $job,
                    'items' => $items,
                    // 'status_logs' => $statusLogs,
                    'requests' => $requests,
                    'adjustments' => $adjustments,
                    'additional_items' => $additionalItems,
                    // 'booking' => $booking,
                    'customer' => $customer,
                    'address' => $bookingAddress,
                ],
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Partner job details error: ' . $e->getMessage());
            return $this->failServerError('Something went wrong while fetching partner job details.');
        }
    }

    public function preview($id = null)
    {
        try {
            $partnerId = (int) ($this->request->getVar('partner_id') ?? 0);

            if ($partnerId <= 0) {
                return $this->failValidationErrors('partner_id is required.');
            }

            $job = $this->partnerJobsModel->find((int) $id);
            if (!$job) {
                return $this->failNotFound('Partner job not found.');
            }

            if ((int) ($job['partner_id'] ?? 0) !== $partnerId) {
                $request = $this->partnerJobRequestModel
                    ->where('partner_job_id', (int) $id)
                    ->where('partner_id', $partnerId)
                    ->where('status', 'requested')
                    ->first();

                if (!$request) {
                    return $this->failValidationErrors('Job is not available for this partner.');
                }
            }

            $items = $this->partnerJobItemModel
                ->where('partner_job_id', (int) $id)
                ->findAll();

            $items = $this->buildNestedItems($items);

            $booking = null;
            $customer = null;
            $bookingAddress = null;

            if (!empty($job['booking_id'])) {
                $bookingModel = new BookingsModel();
                $bookingAddressModel = new BookingAddressModel();
                $customerModel = new CustomerModel();

                $booking = $bookingModel->find((int) $job['booking_id']);
                if ($booking) {
                    $customer = $customerModel->find((int) ($booking['user_id'] ?? 0));
                    $bookingAddress = $bookingAddressModel
                        ->where('booking_id', (int) $job['booking_id'])
                        ->first();
                }
            }

            return $this->respond([
                'status' => 200,
                'message' => 'Partner job preview retrieved successfully.',
                'data' => [
                    'job' => [
                        'id' => (int) ($job['id'] ?? 0),
                        'job_id' => $job['job_id'] ?? null,
                        'title' => $job['title'] ?? null,
                        'estimated_completion_date' => $job['estimated_completion_date'] ?? null,
                        'status' => $job['status'] ?? null,
                    ],
                    'customer' => $customer ? [
                        'name' => $customer['name'] ?? null,
                        'mobile' => $customer['mobile'] ?? null,
                    ] : null,
                    'address' => $bookingAddress,
                    'items' => $items,
                ],
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Partner job preview error: ' . $e->getMessage());
            return $this->failServerError('Something went wrong while fetching partner job preview.');
        }
    }

    public function create()
    {

        // in this i will pass the job details and job services with addons the addon is child of service 
        try {
            $data = $this->request->getJSON(true) ?? $this->request->getVar();

            if (empty($data['booking_id'])) {
                return $this->failValidationErrors('booking_id is required.');
            }

            $jobId = $this->generateJobId();
            $partnerId = $data['partner_id'] ?? null;
            $status = $partnerId ? 'assigned' : 'pending';
            $assignedAt = $partnerId ? date('Y-m-d H:i:s') : null;

            $this->db->transException(true);
            $this->db->transStart();

            $jobData = [
                'job_id' => $jobId,
                'title' => $data['title'] ?? null,
                'notes' => $data['notes'] ?? null,
                'booking_id' => (int) $data['booking_id'],
                'partner_id' => $partnerId ? (int) $partnerId : null,
                'assigned_by_admin_id' => $data['assigned_by_admin_id'] ?? null,
                'status' => $status,
                'stopped_by' => $data['stopped_by'] ?? null,
                'stop_reason' => $data['stop_reason'] ?? null,
                'total_partner_amount' => 0,
                'estimated_start_date' => $data['estimated_start_date'] ?? null,
                'estimated_completion_date' => $data['estimated_completion_date'] ?? null,
                'assigned_at' => $assignedAt,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            $this->partnerJobsModel->skipValidation(true)->insert($jobData);
            $partnerJobId = $this->partnerJobsModel->insertID();

            if (!$partnerJobId) {
                $this->db->transRollback();
                return $this->failServerError('Failed to create partner job.');
            }

            $items = $data['items'] ?? [];
            $itemsTotal = 0.0;

            if (!empty($items) && is_array($items)) {
                foreach ($items as $item) {
                    $itemData = $this->buildItemData($partnerJobId, $item);
                    if (!$this->partnerJobItemModel->insert($itemData)) {
                        $this->db->transRollback();
                        return $this->failValidationErrors([
                            'status' => 400,
                            'message' => 'Validation failed for partner job items.',
                            'errors' => $this->partnerJobItemModel->errors(),
                        ]);
                    }

                    $parentItemId = $this->partnerJobItemModel->insertID();
                    $itemsTotal += (float) $itemData['amount'];

                    $addons = $item['addons'] ?? [];
                    if (!empty($addons) && is_array($addons)) {
                        foreach ($addons as $addon) {
                            $addonItem = $addon;
                            $addonItem['service_source'] = 'addon';
                            $addonItem['parent_item_id'] = $parentItemId;
                            if (empty($addonItem['room_id']) && !empty($item['room_id'])) {
                                $addonItem['room_id'] = $item['room_id'];
                            }
                            if (!array_key_exists('with_material', $addonItem) && array_key_exists('with_material', $item)) {
                                $addonItem['with_material'] = $item['with_material'];
                            }
                            $addonItem['title'] = $addon['name'] ?? ($addon['title'] ?? 'Addon');
                            $addonItemData = $this->buildItemData($partnerJobId, $addonItem);

                            if (!$this->partnerJobItemModel->insert($addonItemData)) {
                                $this->db->transRollback();
                                return $this->failValidationErrors([
                                    'status' => 400,
                                    'message' => 'Validation failed for partner job addon items.',
                                    'errors' => $this->partnerJobItemModel->errors(),
                                ]);
                            }

                            $itemsTotal += (float) $addonItemData['amount'];
                        }
                    }
                }
            }

            if ($itemsTotal > 0) {
                $this->partnerJobsModel->skipValidation(true)->update($partnerJobId, [
                    'total_partner_amount' => $itemsTotal,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }

            $this->logStatusChange($partnerJobId, null, $status, $data['changed_by'] ?? 'system', $data['changed_by_id'] ?? null, $data['note'] ?? null);

            $this->db->transComplete();
            if ($this->db->transStatus() === false) {
                $dbError = $this->db->error();
                $lastQuery = $this->db->getLastQuery();
                log_message('error', 'Partner job transaction failed: ' . json_encode($dbError) . ' | Query: ' . ($lastQuery ? (string) $lastQuery : ''));
                return $this->failServerError('Transaction failed while creating partner job. ' . ($dbError['message'] ?? ''));
            }

            return $this->respondCreated([
                'status' => 201,
                'message' => 'Partner job created successfully.',
                'data' => [
                    'id' => $partnerJobId,
                    'job_id' => $jobId,
                    'status' => $status,
                ],
            ]);
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'Partner job create error: ' . $e->getMessage());
            return $this->failServerError('Something went wrong while creating partner job. ' . $e->getMessage());
        }
    }

    public function assign()
    {
        try {
            $data = $this->request->getJSON(true) ?? $this->request->getVar();

            $partnerJobId = isset($data['partner_job_id']) ? (int) $data['partner_job_id'] : (isset($data['job_id']) ? (int) $data['job_id'] : 0);

            if (empty($partnerJobId) || empty($data['partner_ids']) || !is_array($data['partner_ids'])) {
                return $this->failValidationErrors('partner_job_id and partner_ids[] are required.');
            }

            $partnerIds = array_values(array_unique(array_filter($data['partner_ids'], static function ($id) {
                return !empty($id);
            })));

            if (empty($partnerIds)) {
                return $this->failValidationErrors('partner_ids[] must contain at least one partner.');
            }

            $job = $this->partnerJobsModel->find($partnerJobId);
            if (!$job) {
                return $this->failNotFound('Partner job not found.');
            }

            if (!empty($job['partner_id']) && in_array($job['status'], ['assigned', 'accepted', 'in_progress', 'partially_completed', 'completed'], true)) {
                return $this->respond([
                    'status' => 409,
                    'message' => 'Partner job already assigned/accepted.',
                ], 409);
            }

            $bookingModel = new BookingsModel();
            $bookingAddressModel = new BookingAddressModel();
            $customerModel = new CustomerModel();
            $partnerModel = new PartnerModel();

            $booking = $bookingModel->find((int) $job['booking_id']);
            $customer = $booking ? $customerModel->find((int) $booking['user_id']) : null;
            $bookingAddress = $booking ? $bookingAddressModel->where('booking_id', (int) $job['booking_id'])->first() : null;

            $customerName = $customer['name'] ?? '';
            $slotDate = $booking['slot_date'] ?? null;
            $addressText = $this->buildBookingAddressText($bookingAddress);
            $estimatedCompletionDate = $job['estimated_completion_date'] ?? null;

            $existing = $this->partnerJobRequestModel
                ->where('partner_job_id', $partnerJobId)
                ->whereIn('partner_id', $partnerIds)
                ->findAll();

            $existingPartnerIds = array_column($existing, 'partner_id');
            $assignedPartners = [];
            $skippedPartners = [];

            foreach ($partnerIds as $partnerId) {
                if (in_array((int) $partnerId, $existingPartnerIds, true)) {
                    $skippedPartners[] = [
                        'partner_id' => (int) $partnerId,
                        'reason' => 'Request already exists'
                    ];
                    continue;
                }

                $requestData = [
                    'partner_job_id' => $partnerJobId,
                    'partner_id' => (int) $partnerId,
                    'status' => 'requested',
                    'requested_by' => $data['requested_by'] ?? 'admin',
                    'requested_by_id' => $data['requested_by_id'] ?? null,
                    'created_at' => date('Y-m-d H:i:s'),
                ];

                try {
                    if (!$this->partnerJobRequestModel->insert($requestData)) {
                        $skippedPartners[] = [
                            'partner_id' => (int) $partnerId,
                            'reason' => 'Request insert failed'
                        ];
                        continue;
                    }
                } catch (\Throwable $e) {
                    if (stripos($e->getMessage(), 'Duplicate entry') !== false) {
                        $skippedPartners[] = [
                            'partner_id' => (int) $partnerId,
                            'reason' => 'Request already exists'
                        ];
                        continue;
                    }
                    throw $e;
                }

                $partner = $partnerModel->find((int) $partnerId);
                if (!$partner || empty($partner['firebase_uid'])) {
                    $skippedPartners[] = [
                        'partner_id' => (int) $partnerId,
                        'reason' => 'Missing firebase_uid'
                    ];
                    continue;
                }

                $firestore = new FirestoreService();
                $firestore->pushPartnerJobRequest(
                    $partnerJobId,
                    $partner['firebase_uid'],
                    (int) $partnerId,
                    $job['job_id'] ?? null,
                    (int) $job['booking_id'],
                    $job['title'] ?? null,
                    $customerName,
                    $slotDate,
                    $estimatedCompletionDate,
                    $addressText,
                    $job['total_partner_amount'] ?? null
                );

                $assignedPartners[] = (int) $partnerId;
            }

            return $this->respond([
                'status' => 200,
                'message' => 'Partner job requests created successfully.',
                'summary' => [
                    'partner_job_id' => $partnerJobId,
                    'assigned_partners' => $assignedPartners,
                    'skipped_partners' => $skippedPartners,
                ]
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Partner job assign error: ' . $e->getMessage());
            return $this->failServerError('Something went wrong while assigning partners.');
        }
    }



    public function updateStatus($jobId = null)
    {
        try {
            $data = $this->request->getJSON(true) ?? $this->request->getVar();

            if (empty($data['status'])) {
                return $this->failValidationErrors('status is required.');
            }

            $job = $this->partnerJobsModel->find((int) $jobId);
            if (!$job) {
                return $this->failNotFound('Partner job not found.');
            }

            $oldStatus = $job['status'] ?? null;

            $update = [
                'status' => $data['status'],
                'stopped_by' => $data['stopped_by'] ?? null,
                'stop_reason' => $data['stop_reason'] ?? null,
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            $this->partnerJobsModel->skipValidation(true)->update((int) $jobId, $update);

            $this->logStatusChange((int) $jobId, $oldStatus, $data['status'], $data['changed_by'] ?? 'system', $data['changed_by_id'] ?? null, $data['note'] ?? null);

            return $this->respond([
                'status' => 200,
                'message' => 'Partner job status updated successfully.',
                'data' => [
                    'id' => (int) $jobId,
                    'status' => $data['status'],
                ],
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Update partner job status error: ' . $e->getMessage());
            return $this->failServerError('Something went wrong while updating job status.');
        }
    }

    public function acceptJob($jobId = null)
    {
        try {
            $data = $this->request->getJSON(true) ?? $this->request->getVar();

            $partnerId = isset($data['partner_id']) ? (int) $data['partner_id'] : 0;
            if (empty($jobId) || $partnerId <= 0) {
                return $this->failValidationErrors('job_id and partner_id are required.');
            }

            $this->db->transException(true);
            $this->db->transStart();

            $job = $this->db->query('SELECT * FROM partner_jobs WHERE id = ? FOR UPDATE', [(int) $jobId])->getRowArray();
            if (!$job) {
                $this->db->transRollback();
                return $this->failNotFound('Partner job not found.');
            }

            if (($job['status'] ?? null) === 'accepted' && (int) ($job['partner_id'] ?? 0) === $partnerId) {
                $this->db->transRollback();
                return $this->respond([
                    'status' => 200,
                    'message' => 'Partner job already accepted.',
                    'data' => [
                        'id' => (int) $jobId,
                        'status' => 'accepted',
                    ],
                ]);
            }

            if (!empty($job['partner_id']) && (int) $job['partner_id'] !== $partnerId) {
                $this->db->transRollback();
                return $this->failValidationErrors('Job is assigned to another partner.');
            }

            if (in_array($job['status'] ?? null, ['completed', 'cancelled'], true)) {
                $this->db->transRollback();
                return $this->failValidationErrors('Job cannot be accepted in current status.');
            }

            if (!in_array($job['status'] ?? null, ['pending', 'assigned'], true)) {
                $this->db->transRollback();
                return $this->failValidationErrors('Job can only be accepted when status is pending or assigned.');
            }

            $request = $this->partnerJobRequestModel
                ->where('partner_job_id', (int) $jobId)
                ->where('partner_id', $partnerId)
                ->first();

            if ($request && ($request['status'] ?? null) !== 'requested') {
                $this->db->transRollback();
                return $this->failValidationErrors('Job request is not in requested status.');
            }

            $oldStatus = $job['status'] ?? null;

            $this->partnerJobsModel->skipValidation(true)->update((int) $jobId, [
                'partner_id' => $partnerId,
                'status' => 'accepted',
                'assigned_at' => $job['assigned_at'] ?? date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $this->partnerJobRequestModel
                ->where('partner_job_id', (int) $jobId)
                ->where('partner_id', $partnerId)
                ->set([
                    'status' => 'accepted',
                    'responded_at' => date('Y-m-d H:i:s'),
                ])
                ->update();

            $this->partnerJobRequestModel
                ->where('partner_job_id', (int) $jobId)
                ->where('partner_id !=', $partnerId)
                ->where('status', 'requested')
                ->set([
                    'status' => 'expired',
                    'responded_at' => date('Y-m-d H:i:s'),
                ])
                ->update();

            $this->logStatusChange((int) $jobId, $oldStatus, 'accepted', 'partner', $partnerId, $data['note'] ?? null);

            $this->db->transComplete();
            if ($this->db->transStatus() === false) {
                $dbError = $this->db->error();
                $lastQuery = $this->db->getLastQuery();
                log_message('error', 'Partner job accept transaction failed: ' . json_encode($dbError) . ' | Query: ' . ($lastQuery ? (string) $lastQuery : ''));
                return $this->failServerError('Transaction failed while accepting job. ' . ($dbError['message'] ?? ''));
            }

            // Update Firestore for accepted partner and expire others
            $firestore = new FirestoreService();
            $partnerModel = new PartnerModel();

            $acceptedPartner = $partnerModel->find($partnerId);
            if ($acceptedPartner && !empty($acceptedPartner['firebase_uid'])) {
                $firestore->updatePartnerJobRequestStatus((int) $jobId, $acceptedPartner['firebase_uid'], 'accepted');
            }

            $otherRequests = $this->partnerJobRequestModel
                ->where('partner_job_id', (int) $jobId)
                ->where('partner_id !=', $partnerId)
                ->findAll();

            foreach ($otherRequests as $request) {
                $otherPartner = $partnerModel->find((int) ($request['partner_id'] ?? 0));
                if ($otherPartner && !empty($otherPartner['firebase_uid'])) {
                    $firestore->updatePartnerJobRequestStatus((int) $jobId, $otherPartner['firebase_uid'], 'expired');
                }
            }

            return $this->respond([
                'status' => 200,
                'message' => 'Partner job accepted successfully.',
                'data' => [
                    'id' => (int) $jobId,
                    'status' => 'accepted',
                ],
            ]);
        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Accept partner job error: ' . $e->getMessage());
            return $this->failServerError('Something went wrong while accepting job.');
        }
    }

    public function listByPartner($partnerId = null)
    {
        try {
            $jobs = $this->partnerJobsModel
                ->where('partner_id', (int) $partnerId)
                ->orderBy('created_at', 'DESC')
                ->findAll();

            return $this->respond([
                'status' => 200,
                'message' => 'Partner jobs retrieved successfully.',
                'data' => $jobs,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'List partner jobs error: ' . $e->getMessage());
            return $this->failServerError('Something went wrong while fetching partner jobs.');
        }
    }

    public function listActiveByPartner($partnerId = null)
    {
        try {
            $jobs = $this->partnerJobsModel
                ->where('partner_id', (int) $partnerId)
                ->whereIn('status', ['accepted', 'in_progress'])
                ->orderBy('created_at', 'DESC')
                ->findAll();

            return $this->respond([
                'status' => 200,
                'message' => 'Partner active jobs retrieved successfully.',
                'data' => $jobs,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'List partner active jobs error: ' . $e->getMessage());
            return $this->failServerError('Something went wrong while fetching partner active jobs.');
        }
    }

    public function listByBooking($bookingId = null)
    {
        try {
            $jobs = $this->partnerJobsModel
                ->where('booking_id', (int) $bookingId)
                ->orderBy('created_at', 'DESC')
                ->findAll();

            if (!empty($jobs)) {
                $jobIds = array_map(static function ($job) {
                    return (int) ($job['id'] ?? 0);
                }, $jobs);

                $jobIds = array_filter($jobIds, static function ($id) {
                    return $id > 0;
                });

                if (!empty($jobIds)) {
                    $items = $this->partnerJobItemModel
                        ->whereIn('partner_job_id', $jobIds)
                        ->findAll();

                    $itemsByJob = [];
                    foreach ($items as $item) {
                        $itemsByJob[$item['partner_job_id']][] = $item;
                    }

                    foreach ($jobs as &$job) {
                        $jobId = (int) ($job['id'] ?? 0);
                        $job['items'] = $itemsByJob[$jobId] ?? [];
                    }
                    unset($job);
                }
            }

            return $this->respond([
                'status' => 200,
                'message' => 'Partner jobs retrieved successfully.',
                'data' => $jobs,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'List jobs by booking error: ' . $e->getMessage());
            return $this->failServerError('Something went wrong while fetching jobs by booking.');
        }
    }

    public function listItems($jobId = null)
    {
        try {
            $items = $this->partnerJobItemModel
                ->where('partner_job_id', (int) $jobId)
                ->findAll();

            return $this->respond([
                'status' => 200,
                'message' => 'Partner job items retrieved successfully.',
                'data' => $items,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Partner job items error: ' . $e->getMessage());
            return $this->failServerError('Something went wrong while fetching job items.');
        }
    }

    public function addItems($jobId = null)
    {
        try {
            $data = $this->request->getJSON(true) ?? $this->request->getVar();
            $items = $data['items'] ?? [];

            if (empty($items) || !is_array($items)) {
                return $this->failValidationErrors('items array is required.');
            }

            $job = $this->partnerJobsModel->find((int) $jobId);
            if (!$job) {
                return $this->failNotFound('Partner job not found.');
            }

            $this->db->transException(true);
            $this->db->transStart();

            $itemsTotal = 0.0;
            foreach ($items as $item) {
                $itemData = $this->buildItemData((int) $jobId, $item);
                if (!$this->partnerJobItemModel->insert($itemData)) {
                    $this->db->transRollback();
                    return $this->failValidationErrors([
                        'status' => 400,
                        'message' => 'Validation failed for partner job items.',
                        'errors' => $this->partnerJobItemModel->errors(),
                    ]);
                }

                $parentItemId = $this->partnerJobItemModel->insertID();
                $itemsTotal += (float) $itemData['amount'];

                $addons = $item['addons'] ?? [];
                if (!empty($addons) && is_array($addons)) {
                    foreach ($addons as $addon) {
                        $addonItem = $addon;
                        $addonItem['service_source'] = 'addon';
                        $addonItem['parent_item_id'] = $parentItemId;
                        if (empty($addonItem['room_id']) && !empty($item['room_id'])) {
                            $addonItem['room_id'] = $item['room_id'];
                        }
                        if (!array_key_exists('with_material', $addonItem) && array_key_exists('with_material', $item)) {
                            $addonItem['with_material'] = $item['with_material'];
                        }
                        $addonItem['title'] = $addon['name'] ?? ($addon['title'] ?? 'Addon');
                        $addonItemData = $this->buildItemData((int) $jobId, $addonItem);

                        if (!$this->partnerJobItemModel->insert($addonItemData)) {
                            $this->db->transRollback();
                            return $this->failValidationErrors([
                                'status' => 400,
                                'message' => 'Validation failed for partner job addon items.',
                                'errors' => $this->partnerJobItemModel->errors(),
                            ]);
                        }

                        $itemsTotal += (float) $addonItemData['amount'];
                    }
                }
            }

            $newTotal = (float) ($job['total_partner_amount'] ?? 0) + $itemsTotal;
            $this->partnerJobsModel->skipValidation(true)->update((int) $jobId, [
                'total_partner_amount' => $newTotal,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $this->db->transComplete();
            if ($this->db->transStatus() === false) {
                $dbError = $this->db->error();
                $lastQuery = $this->db->getLastQuery();
                log_message('error', 'Partner job add items transaction failed: ' . json_encode($dbError) . ' | Query: ' . ($lastQuery ? (string) $lastQuery : ''));
                return $this->failServerError('Transaction failed while adding items. ' . ($dbError['message'] ?? ''));
            }

            return $this->respond([
                'status' => 200,
                'message' => 'Partner job items added successfully.',
                'data' => [
                    'partner_job_id' => (int) $jobId,
                    'items_added' => count($items),
                    'total_partner_amount' => $newTotal,
                ],
            ]);
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'Add partner job items error: ' . $e->getMessage());
            return $this->failServerError('Something went wrong while adding job items. ' . $e->getMessage());
        }
    }

    public function requestPartner($jobId = null)
    {
        try {
            $data = $this->request->getJSON(true) ?? $this->request->getVar();

            if (empty($data['partner_id'])) {
                return $this->failValidationErrors('partner_id is required.');
            }

            $existingRequest = $this->partnerJobRequestModel
                ->where('partner_job_id', (int) $jobId)
                ->where('partner_id', (int) $data['partner_id'])
                ->first();

            if ($existingRequest) {
                return $this->respond([
                    'status' => 409,
                    'message' => 'Partner request already exists for this job.',
                    'data' => $existingRequest,
                ], 409);
            }

            $requestData = [
                'partner_job_id' => (int) $jobId,
                'partner_id' => (int) $data['partner_id'],
                'status' => 'requested',
                'requested_by' => $data['requested_by'] ?? 'admin',
                'requested_by_id' => $data['requested_by_id'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
            ];

            if (!$this->partnerJobRequestModel->insert($requestData)) {
                return $this->failValidationErrors([
                    'status' => 400,
                    'message' => 'Validation failed for partner job request.',
                    'errors' => $this->partnerJobRequestModel->errors(),
                ]);
            }

            return $this->respondCreated([
                'status' => 201,
                'message' => 'Partner job request created successfully.',
                'data' => [
                    'id' => $this->partnerJobRequestModel->insertID(),
                    'partner_job_id' => (int) $jobId,
                    'partner_id' => (int) $data['partner_id'],
                    'status' => 'requested',
                ],
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Partner job request error: ' . $e->getMessage());
            return $this->failServerError('Something went wrong while creating partner job request.');
        }
    }

    public function respondRequest($requestId = null)
    {
        try {
            $data = $this->request->getJSON(true) ?? $this->request->getVar();

            if (empty($data['status']) || !in_array($data['status'], ['accepted', 'rejected'], true)) {
                return $this->failValidationErrors('status must be accepted or rejected.');
            }

            $request = $this->partnerJobRequestModel->find((int) $requestId);
            if (!$request) {
                return $this->failNotFound('Partner job request not found.');
            }

            $this->db->transException(true);
            $this->db->transStart();

            $this->partnerJobRequestModel->update((int) $requestId, [
                'status' => $data['status'],
                'responded_at' => date('Y-m-d H:i:s'),
                'response_note' => $data['response_note'] ?? null,
            ]);

            if ($data['status'] === 'accepted') {
                $job = $this->partnerJobsModel->find((int) $request['partner_job_id']);
                if ($job) {
                    $oldStatus = $job['status'] ?? null;
                    $this->partnerJobsModel->skipValidation(true)->update((int) $job['id'], [
                        'partner_id' => (int) $request['partner_id'],
                        'status' => 'accepted',
                        'assigned_at' => $job['assigned_at'] ?? date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);

                    $this->logStatusChange((int) $job['id'], $oldStatus, 'accepted', $data['changed_by'] ?? 'partner', $data['changed_by_id'] ?? $request['partner_id'], $data['note'] ?? null);
                }
            }

            $this->db->transComplete();
            if ($this->db->transStatus() === false) {
                $dbError = $this->db->error();
                $lastQuery = $this->db->getLastQuery();
                log_message('error', 'Partner job respond request transaction failed: ' . json_encode($dbError) . ' | Query: ' . ($lastQuery ? (string) $lastQuery : ''));
                return $this->failServerError('Transaction failed while responding to request. ' . ($dbError['message'] ?? ''));
            }

            return $this->respond([
                'status' => 200,
                'message' => 'Partner job request updated successfully.',
                'data' => [
                    'id' => (int) $requestId,
                    'status' => $data['status'],
                ],
            ]);
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'Respond partner job request error: ' . $e->getMessage());
            return $this->failServerError('Something went wrong while responding to partner job request. ' . $e->getMessage());
        }
    }

    private function buildItemData(int $partnerJobId, array $item): array
    {
        $quantity = (float) ($item['quantity'] ?? 0);
        $rate = (float) ($item['rate'] ?? 0);
        $amount = isset($item['amount'])
            ? (float) $item['amount']
            : round($quantity * $rate, 2);

        return [
            'partner_job_id' => $partnerJobId,
            'parent_item_id' => $item['parent_item_id'] ?? null,
            'service_source' => $item['service_source'] ?? 'manual',
            'source_id' => $item['source_id'] ?? null,
            'room_id' => $item['room_id'] ?? null,
            'with_material' => (bool) ($item['with_material'] ?? false),
            'title' => $item['title'] ?? 'Service',
            'quantity' => $quantity,
            'unit' => $item['unit'] ?? 'unit',
            'rate' => $rate,
            'amount' => $amount,
            'status' => $item['status'] ?? 'pending',
            'checklist_status' => isset($item['checklist_status']) ? json_encode($item['checklist_status']) : null,
            'cancelled_by' => $item['cancelled_by'] ?? null,
            'cancel_reason' => $item['cancel_reason'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ];
    }

    private function generateJobId(): string
    {
        $prefix = 'SEJ';
        $jobId = $prefix . date('ymdHis');

        $exists = $this->partnerJobsModel->where('job_id', $jobId)->first();
        if (!$exists) {
            return $jobId;
        }

        return $prefix . date('ymdHis') . rand(10, 99);
    }

    private function logStatusChange(int $partnerJobId, ?string $oldStatus, string $newStatus, string $changedBy, ?int $changedById, ?string $note): void
    {
        $this->partnerJobStatusLogModel->insert([
            'partner_job_id' => $partnerJobId,
            'old_status' => $oldStatus ?? $newStatus,
            'new_status' => $newStatus,
            'changed_by' => $changedBy,
            'changed_by_id' => $changedById,
            'note' => $note,
        ]);
    }

    private function buildBookingAddressText(?array $bookingAddress): ?string
    {
        if (empty($bookingAddress)) {
            return null;
        }

        return trim(implode(', ', array_filter([
            $bookingAddress['house'] ?? null,
            $bookingAddress['address'] ?? null,
            $bookingAddress['landmark'] ?? null,
        ])));
    }

    private function buildNestedItems(array $items): array
    {
        if (empty($items)) {
            return [];
        }

        $byId = [];
        foreach ($items as $item) {
            $item['children'] = [];
            $byId[(int) $item['id']] = $item;
        }

        foreach ($byId as $id => &$item) {
            $parentId = (int) ($item['parent_item_id'] ?? 0);
            if ($parentId > 0 && isset($byId[$parentId])) {
                $byId[$parentId]['children'][] = &$item;
            }
        }
        unset($item);

        $roots = [];
        foreach ($byId as $id => $item) {
            $parentId = (int) ($item['parent_item_id'] ?? 0);
            if ($parentId <= 0 || !isset($byId[$parentId])) {
                $roots[] = $item;
            }
        }

        return $this->pruneEmptyChildren($roots);
    }

    private function pruneEmptyChildren(array $items): array
    {
        foreach ($items as &$item) {
            if (!empty($item['children']) && is_array($item['children'])) {
                $item['children'] = $this->pruneEmptyChildren($item['children']);
            }

            if (empty($item['children'])) {
                unset($item['children']);
            }
        }
        unset($item);

        return $items;
    }
}
