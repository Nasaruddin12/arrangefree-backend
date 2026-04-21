<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\BookingsModel;
use App\Models\BookingServicesModel;
use App\Models\ReviewAspectsModel;
use App\Models\ReviewMediaModel;
use App\Models\ReviewShareLinkModel;
use App\Models\ReviewsModel;
use App\Models\ReviewVotesModel;
use App\Models\ServiceReviewSummaryModel;
use App\Services\ImageProcessingService;
use CodeIgniter\API\ResponseTrait;
use Exception;

class ReviewController extends BaseController
{
    use ResponseTrait;

    protected ReviewsModel $reviewsModel;
    protected ReviewAspectsModel $reviewAspectsModel;
    protected ReviewMediaModel $reviewMediaModel;
    protected ReviewShareLinkModel $reviewShareLinkModel;
    protected ReviewVotesModel $reviewVotesModel;
    protected ServiceReviewSummaryModel $serviceReviewSummaryModel;
    protected ImageProcessingService $imageProcessingService;
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->reviewsModel = new ReviewsModel();
        $this->reviewAspectsModel = new ReviewAspectsModel();
        $this->reviewMediaModel = new ReviewMediaModel();
        $this->reviewShareLinkModel = new ReviewShareLinkModel();
        $this->reviewVotesModel = new ReviewVotesModel();
        $this->serviceReviewSummaryModel = new ServiceReviewSummaryModel();
        $this->imageProcessingService = new ImageProcessingService();
    }

    public function uploadMedia()
    {
        try {
            $files = $this->request->getFiles();
            $mediaFiles = [];

            if (isset($files['media'])) {
                $mediaFiles = is_array($files['media']) ? $files['media'] : [$files['media']];
            } elseif ($this->request->getFile('file')) {
                $mediaFiles = [$this->request->getFile('file')];
            }

            if (empty($mediaFiles)) {
                return $this->respond([
                    'status' => 400,
                    'message' => 'No media files uploaded.',
                ], 400);
            }

            $uploadRelativePath = 'public/uploads/reviews/';
            $uploadFullPath = FCPATH . $uploadRelativePath;
            $uploadedMedia = [];

            foreach ($mediaFiles as $file) {
                if (!$file || !$file->isValid() || $file->hasMoved()) {
                    continue;
                }

                $mimeType = (string) $file->getMimeType();

                if (str_starts_with($mimeType, 'image/')) {
                    $allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp'];
                    if (!in_array($mimeType, $allowedTypes, true)) {
                        continue;
                    }

                    $baseName = pathinfo($file->getRandomName(), PATHINFO_FILENAME);
                    $webpName = $this->imageProcessingService->uploadAndConvertToWebp(
                        $file,
                        $uploadFullPath,
                        $baseName,
                        1200,
                        1200,
                        90
                    );

                    $uploadedMedia[] = [
                        'media_type' => 'image',
                        'media_url' => $uploadRelativePath . $webpName,
                    ];
                    continue;
                }

                if (str_starts_with($mimeType, 'video/')) {
                    $allowedExtensions = ['mp4', 'mov', 'avi', 'mkv', 'webm'];
                    $extension = strtolower((string) $file->getExtension());
                    if (!in_array($extension, $allowedExtensions, true)) {
                        continue;
                    }

                    if (!is_dir($uploadFullPath) && !mkdir($uploadFullPath, 0755, true) && !is_dir($uploadFullPath)) {
                        return $this->respond([
                            'status' => 500,
                            'message' => 'Failed to create upload directory.',
                        ], 500);
                    }

                    $fileName = $file->getRandomName();
                    if (!$file->move($uploadFullPath, $fileName)) {
                        return $this->respond([
                            'status' => 500,
                            'message' => 'Failed to upload media.',
                        ], 500);
                    }

                    $uploadedMedia[] = [
                        'media_type' => 'video',
                        'media_url' => $uploadRelativePath . $fileName,
                    ];
                }
            }

            if (empty($uploadedMedia)) {
                return $this->respond([
                    'status' => 400,
                    'message' => 'No valid media files were uploaded.',
                ], 400);
            }

            return $this->respondCreated([
                'status' => 201,
                'message' => 'Review media uploaded successfully.',
                'data' => $uploadedMedia,
            ]);
        } catch (Exception $e) {
            return $this->respond([
                'status' => $e->getCode() ?: 500,
                'message' => 'Failed to upload review media.',
                'error' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    public function deleteMedia()
    {
        try {
            $payload = $this->getRequestData();
            $mediaUrl = trim((string) ($payload['media_url'] ?? ''));

            if ($mediaUrl === '') {
                return $this->respond([
                    'status' => 400,
                    'message' => 'media_url is required.',
                ], 400);
            }

            $relativePath = ltrim($mediaUrl, '/');
            $allowedPrefix = 'public/uploads/reviews/';
            if (!str_starts_with($relativePath, $allowedPrefix)) {
                return $this->respond([
                    'status' => 400,
                    'message' => 'Invalid review media path.',
                ], 400);
            }

            $fullPath = FCPATH . $relativePath;
            if (!is_file($fullPath)) {
                return $this->respond([
                    'status' => 404,
                    'message' => 'Media file not found.',
                ], 404);
            }

            if (!@unlink($fullPath)) {
                return $this->respond([
                    'status' => 500,
                    'message' => 'Failed to delete media file.',
                ], 500);
            }

            return $this->respondDeleted([
                'status' => 200,
                'message' => 'Review media deleted successfully.',
                'data' => [
                    'media_url' => $relativePath,
                ],
            ]);
        } catch (Exception $e) {
            return $this->respond([
                'status' => $e->getCode() ?: 500,
                'message' => 'Failed to delete review media.',
                'error' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    public function customerSubmit()
    {
        try {
            $customerId = $this->getCustomerIdFromSession();
            if ($customerId === null) {
                return $this->respond(['status' => 401, 'message' => 'Unauthorized customer token.'], 401);
            }

            $payload = $this->getRequestData();
            $result = $this->storeCustomerReview($payload, $customerId);

            return $this->reviewResultResponse($result);
        } catch (Exception $e) {
            return $this->respond([
                'status' => $e->getCode() ?: 500,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    public function adminCreateBookingReviewLink($bookingId)
    {
        try {
            if (!$this->isAdminSession()) {
                return $this->respond(['status' => 401, 'message' => 'Unauthorized admin token.'], 401);
            }

            $bookingId = (int) $bookingId;
            if ($bookingId <= 0) {
                return $this->respond(['status' => 422, 'message' => 'Valid booking_id is required.'], 422);
            }

            $booking = (new BookingsModel())
                ->select('bookings.*, customers.name as customer_name, customers.email as customer_email, customers.mobile_no as customer_mobile')
                ->join('customers', 'customers.id = bookings.user_id', 'left')
                ->where('bookings.id', $bookingId)
                ->first();

            if (!$booking) {
                return $this->respond(['status' => 404, 'message' => 'Booking not found.'], 404);
            }

            $customerId = (int) ($booking['user_id'] ?? 0);
            if ($customerId <= 0) {
                return $this->respond([
                    'status' => 422,
                    'message' => 'This booking is not linked with a customer.',
                ], 422);
            }

            $payload = $this->getRequestData();
            $expiresInDays = (int) ($payload['expires_in_days'] ?? 30);
            $expiresInDays = max(1, min($expiresInDays, 365));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+' . $expiresInDays . ' days'));
            $now = date('Y-m-d H:i:s');

            if (!empty($payload['revoke_existing'])) {
                $this->reviewShareLinkModel
                    ->where('booking_id', $bookingId)
                    ->where('revoked_at', null)
                    ->set(['revoked_at' => $now])
                    ->update();
            }

            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            $code = $this->generateReviewLinkCode();
            $adminId = $this->getAdminIdFromSession();

            $linkId = $this->reviewShareLinkModel->insert([
                'booking_id' => $bookingId,
                'user_id' => $customerId,
                'token_hash' => $tokenHash,
                'code' => $code,
                'created_by_admin_id' => $adminId > 0 ? $adminId : null,
                'expires_at' => $expiresAt,
            ], true);

            if ($linkId === false) {
                return $this->respond([
                    'status' => 422,
                    'message' => 'Failed to create review link.',
                    'errors' => $this->reviewShareLinkModel->errors(),
                ], 422);
            }

            $urls = $this->buildReviewShareUrls(
                $token,
                $code,
                $payload['frontend_url'] ?? null,
                $payload['deep_link_url'] ?? null
            );

            return $this->respondCreated([
                'status' => 201,
                'message' => 'Booking review link created successfully.',
                'data' => [
                    'id' => (int) $linkId,
                    'booking_id' => $bookingId,
                    'booking_code' => $booking['booking_code'] ?? null,
                    'customer_id' => $customerId,
                    'customer_name' => $booking['customer_name'] ?? null,
                    'code' => $code,
                    'review_url' => $urls['review_url'],
                    'short_url' => $urls['short_url'],
                    'deep_link_url' => $urls['deep_link_url'],
                    'preferred_share_url' => $urls['preferred_share_url'],
                    'api_url' => $urls['api_url'],
                    'submit_api_url' => $urls['submit_api_url'],
                    'expires_at' => $expiresAt,
                ],
            ]);
        } catch (Exception $e) {
            return $this->respond([
                'status' => $e->getCode() ?: 500,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    public function publicReviewCode($code)
    {
        $resolved = $this->resolveReviewShareLinkByCode((string) $code);
        if (isset($resolved['error'])) {
            return $this->respond($resolved['error']['body'], $resolved['error']['status']);
        }

        return $this->respond([
            'status' => 200,
            'data' => $this->buildReviewSharePayload(
                $resolved,
                base_url('reviews/code/' . rawurlencode((string) $code))
            ),
        ], 200);
    }

    public function publicReviewCodeSave($code)
    {
        try {
            $resolved = $this->resolveReviewShareLinkByCode((string) $code);
            if (isset($resolved['error'])) {
                return $this->respond($resolved['error']['body'], $resolved['error']['status']);
            }

            return $this->submitResolvedReviewShareLink($resolved);
        } catch (Exception $e) {
            return $this->respond([
                'status' => $e->getCode() ?: 500,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    public function customerMyReviews()
    {
        $customerId = $this->getCustomerIdFromSession();
        if ($customerId === null) {
            return $this->respond(['status' => 401, 'message' => 'Unauthorized customer token.'], 401);
        }

        $reviews = $this->reviewsModel
            ->where('user_id', $customerId)
            ->orderBy('id', 'DESC')
            ->findAll();

        return $this->respond([
            'status' => 200,
            'data'   => $this->appendReviewRelations($reviews),
        ], 200);
    }

    public function bookingReviewServices($bookingId)
    {
        $customerId = $this->getCustomerIdFromSession();
        if ($customerId === null) {
            return $this->respond(['status' => 401, 'message' => 'Unauthorized customer token.'], 401);
        }

        $bookingId = (int) $bookingId;
        if ($bookingId <= 0) {
            return $this->respond(['status' => 422, 'message' => 'Valid booking_id is required.'], 422);
        }

        $booking = (new BookingsModel())
            ->where('id', $bookingId)
            ->where('user_id', $customerId)
            ->first();

        if (!$booking) {
            return $this->respond(['status' => 404, 'message' => 'Booking not found.'], 404);
        }

        return $this->respond([
            'status' => 200,
            'booking_id' => $bookingId,
            'data' => $this->getBookingReviewServicesData($bookingId, $customerId),
        ], 200);
    }

    public function getByBooking($bookingId)
    {
        $reviews = $this->reviewsModel
            ->select('reviews.*, customers.name as customer_name')
            ->join('customers', 'customers.id = reviews.user_id', 'left')
            ->where('reviews.booking_id', (int) $bookingId)
            ->orderBy('reviews.id', 'DESC')
            ->findAll();

        return $this->respond([
            'status'     => 200,
            'booking_id' => (int) $bookingId,
            'reviewed'   => !empty($reviews),
            'data'       => $this->appendReviewRelations($reviews),
        ], 200);
    }

    public function serviceReviews($serviceId)
    {
        $reviews = $this->reviewsModel
            ->select('reviews.*, customers.name as customer_name')
            ->join('customers', 'customers.id = reviews.user_id', 'left')
            ->where('reviews.review_type', 'service')
            ->where('reviews.service_id', (int) $serviceId)
            ->where('reviews.status', 'approved')
            ->orderBy('reviews.id', 'DESC')
            ->findAll();

        return $this->respond([
            'status'  => 200,
            'summary' => $this->serviceReviewSummaryModel->getByServiceId((int) $serviceId),
            'data'    => $this->appendReviewRelations($reviews),
        ], 200);
    }

    public function publicServiceReviews()
    {
        $filters = $this->getRequestData();
        $limit = (int) ($filters['limit'] ?? 0);

        $builder = $this->reviewsModel
            ->select('reviews.*, customers.name as customer_name, services.slug as service_slug, services.name as service_name')
            ->join('customers', 'customers.id = reviews.user_id', 'left')
            ->join('services', 'services.id = reviews.service_id', 'left')
            ->where('reviews.review_type', 'service')
            ->where('reviews.status', 'approved')
            ->orderBy('reviews.id', 'DESC');

        if ($limit > 0) {
            $builder->limit($limit);
        }

        $reviews = $builder->findAll();
        $reviews = $this->appendReviewRelations($reviews);

        // Remove full objects, keep only customer_name, service_slug and service_name
        foreach ($reviews as &$review) {
            unset($review['customer']);
            unset($review['service']);
            unset($review['partner']);
        }
        unset($review);

        return $this->respond([
            'status' => 200,
            'data'   => $reviews,
        ], 200);
    }

    public function submit()
    {
        return $this->customerSubmit();
    }

    public function customerUpdate($reviewId)
    {
        try {
            $customerId = $this->getCustomerIdFromSession();
            if ($customerId === null) {
                return $this->respond(['status' => 401, 'message' => 'Unauthorized customer token.'], 401);
            }

            $review = $this->reviewsModel
                ->where('id', (int) $reviewId)
                ->where('user_id', $customerId)
                ->first();

            if (!$review) {
                return $this->respond(['status' => 404, 'message' => 'Review not found.'], 404);
            }

            $payload = $this->getRequestData();
            $result = $this->updateCustomerReviewRecord($review, $payload);

            return $this->reviewResultResponse($result);
        } catch (Exception $e) {
            return $this->respond([
                'status' => $e->getCode() ?: 500,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    public function getByPartner($partnerId)
    {
        $reviews = $this->reviewsModel
            ->select('reviews.*, customers.name as customer_name')
            ->join('customers', 'customers.id = reviews.user_id', 'left')
            ->where('reviews.review_type', 'partner')
            ->where('reviews.partner_id', (int) $partnerId)
            ->where('reviews.status', 'approved')
            ->orderBy('reviews.id', 'DESC')
            ->findAll();

        return $this->respond([
            'status' => 200,
            'data'   => $this->appendReviewRelations($reviews),
        ], 200);
    }

    public function getAllPartnerReviews()
    {
        if (!$this->isAdminSession()) {
            return $this->respond(['status' => 401, 'message' => 'Unauthorized admin token.'], 401);
        }

        $reviews = $this->reviewsModel
            ->select('reviews.*, customers.name as customer_name')
            ->join('customers', 'customers.id = reviews.user_id', 'left')
            ->where('reviews.review_type', 'partner')
            ->where('reviews.status', 'approved')
            ->orderBy('reviews.id', 'DESC')
            ->findAll();

        return $this->respond([
            'status' => 200,
            'data'   => $this->appendReviewRelations($reviews),
        ], 200);
    }

    public function customerVote($reviewId)
    {
        $payload = $this->getRequestData();
        $customerId = $this->getCustomerIdFromSession();
        $guestToken = trim((string) ($payload['guest_token'] ?? ''));

        if ($payload['user_id'] === null && $guestToken === '') {
            return $this->respond([
                'status' => 422,
                'message' => 'guest_token is required for guest voting.',
            ], 422);
        }

        $voteData = [
            'review_id' => (int) $reviewId,
            'user_id'   => $payload['user_id'],
            'guest_token' => $guestToken !== '' ? $guestToken : null,
            'vote'      => $payload['vote'] ?? null,
        ];

        if (!$this->reviewsModel->find($reviewId)) {
            return $this->respond(['status' => 404, 'message' => 'Review not found.'], 404);
        }

        if (!$this->reviewVotesModel->saveUserVote($voteData)) {
            return $this->respond([
                'status' => 422,
                'message' => 'Vote validation failed.',
                'errors' => $this->reviewVotesModel->errors(),
            ], 422);
        }

        return $this->respond([
            'status'  => 200,
            'message' => 'Vote saved successfully.',
        ], 200);
    }

    public function vote($reviewId)
    {
        return $this->customerVote($reviewId);
    }

    public function adminList()
    {
        if (!$this->isAdminSession()) {
            return $this->respond(['status' => 401, 'message' => 'Unauthorized admin token.'], 401);
        }

        $builder = $this->reviewsModel
            ->select('reviews.*, customers.name as customer_name, services.name as service_name, partners.name as partner_name')
            ->join('customers', 'customers.id = reviews.user_id', 'left')
            ->join('services', 'services.id = reviews.service_id', 'left')
            ->join('partners', 'partners.id = reviews.partner_id', 'left')
            ->orderBy('reviews.id', 'DESC');

        $filters = $this->getRequestData();
        foreach (['status', 'review_type', 'service_id', 'partner_id', 'booking_id', 'user_id'] as $filter) {
            if (!empty($filters[$filter])) {
                $builder->where('reviews.' . $filter, $filters[$filter]);
            }
        }

        $reviews = $builder->findAll();

        return $this->respond([
            'status' => 200,
            'data'   => $this->appendReviewRelations($reviews),
        ], 200);
    }

    public function adminShow($reviewId)
    {
        if (!$this->isAdminSession()) {
            return $this->respond(['status' => 401, 'message' => 'Unauthorized admin token.'], 401);
        }

        $review = $this->reviewsModel
            ->select('reviews.*, customers.name as customer_name, services.name as service_name, partners.name as partner_name')
            ->join('customers', 'customers.id = reviews.user_id', 'left')
            ->join('services', 'services.id = reviews.service_id', 'left')
            ->join('partners', 'partners.id = reviews.partner_id', 'left')
            ->where('reviews.id', (int) $reviewId)
            ->first();

        if (!$review) {
            return $this->respond(['status' => 404, 'message' => 'Review not found.'], 404);
        }

        $review = $this->appendReviewRelations([$review])[0];

        return $this->respond([
            'status' => 200,
            'data'   => $review,
        ], 200);
    }

    public function adminUpdateStatus($reviewId)
    {
        if (!$this->isAdminSession()) {
            return $this->respond(['status' => 401, 'message' => 'Unauthorized admin token.'], 401);
        }

        $payload = $this->getRequestData();
        $review = $this->reviewsModel->find((int) $reviewId);

        if (!$review) {
            return $this->respond(['status' => 404, 'message' => 'Review not found.'], 404);
        }

        $updateData = [
            'status'      => $payload['status'] ?? null,
            'is_verified' => isset($payload['is_verified']) ? (int) $payload['is_verified'] : $review['is_verified'],
        ];

        if (!$this->reviewsModel->update((int) $reviewId, $updateData)) {
            return $this->respond([
                'status' => 422,
                'message' => 'Validation failed.',
                'errors' => $this->reviewsModel->errors(),
            ], 422);
        }

        if (($review['review_type'] ?? null) === 'service' && !empty($review['service_id'])) {
            $this->syncServiceSummary((int) $review['service_id']);
        }

        return $this->respond([
            'status'  => 200,
            'message' => 'Review updated successfully.',
        ], 200);
    }

    public function adminServiceSummary($serviceId)
    {
        if (!$this->isAdminSession()) {
            return $this->respond(['status' => 401, 'message' => 'Unauthorized admin token.'], 401);
        }

        $approvedReviews = $this->reviewsModel
            ->select('reviews.*, customers.name as customer_name')
            ->join('customers', 'customers.id = reviews.user_id', 'left')
            ->where('reviews.review_type', 'service')
            ->where('reviews.service_id', (int) $serviceId)
            // ->where('status', 'approved')
            ->findAll();

        return $this->respond([
            'status'  => 200,
            'summary' => $this->serviceReviewSummaryModel->getByServiceId((int) $serviceId),
            'reviews' => $this->appendReviewRelations($approvedReviews),
        ], 200);
    }

    public function getAllServicesReviewSummary()
    {
        if (!$this->isAdminSession()) {
            return $this->respond(['status' => 401, 'message' => 'Unauthorized admin token.'], 401);
        }
        $summaries = $this->serviceReviewSummaryModel
            ->select('service_review_summary.*, services.name as service_name, services.image as service_image, services.slug as service_slug')
            ->join('services', 'services.id = service_review_summary.service_id', 'left')
            ->orderBy('service_review_summary.avg_rating', 'DESC')
            ->findAll();

        return $this->respond([
            'status' => 200,
            'data'   => $summaries,
        ], 200);
    }

    public function adminPartnerReviews($partnerId)
    {
        if (!$this->isAdminSession()) {
            return $this->respond(['status' => 401, 'message' => 'Unauthorized admin token.'], 401);
        }

        $reviews = $this->reviewsModel
            ->select('reviews.*, customers.name as customer_name')
            ->join('customers', 'customers.id = reviews.user_id', 'left')
            ->where('reviews.review_type', 'partner')
            ->where('reviews.partner_id', (int) $partnerId)
            ->orderBy('reviews.id', 'DESC')
            ->findAll();

        return $this->respond([
            'status' => 200,
            'data'   => $this->appendReviewRelations($reviews),
        ], 200);
    }

    private function storeCustomerReview(
        array $payload,
        int $customerId,
        ?int $forcedBookingId = null,
        array $allowedReviewTypes = ['service', 'partner', 'booking']
    ): array {
        $reviewType = trim((string) ($payload['review_type'] ?? 'service'));
        if (!in_array($reviewType, $allowedReviewTypes, true)) {
            return [
                'status_code' => 422,
                'body' => [
                    'status' => 422,
                    'message' => 'Invalid review_type.',
                ],
            ];
        }

        $bookingId = $forcedBookingId ?? (int) ($payload['booking_id'] ?? 0);
        $serviceId = $reviewType === 'service' ? ($payload['service_id'] ?? null) : null;
        $partnerId = $reviewType === 'partner' ? ($payload['partner_id'] ?? null) : null;

        $reviewData = [
            'review_type' => $reviewType,
            'booking_id'  => $bookingId,
            'service_id'  => $serviceId,
            'partner_id'  => $partnerId,
            'user_id'     => $customerId,
            'title'       => $payload['title'] ?? null,
            'rating'      => $payload['rating'] ?? null,
            'review'      => $payload['review'] ?? null,
            'is_verified' => isset($payload['is_verified']) ? (int) $payload['is_verified'] : 1,
            'status'      => 'approved',
        ];

        if ($this->reviewsModel->hasDuplicateReview($reviewData)) {
            return [
                'status_code' => 409,
                'body' => [
                    'status'  => 409,
                    'message' => 'Review already submitted for this booking and target.',
                ],
            ];
        }

        $this->db->transStart();

        $reviewId = $this->reviewsModel->insert($reviewData, true);
        if ($reviewId === false) {
            $this->db->transRollback();

            return [
                'status_code' => 422,
                'body' => [
                    'status' => 422,
                    'message' => 'Validation failed.',
                    'errors' => $this->reviewsModel->errors(),
                ],
            ];
        }

        $aspects = $this->normalizeAspects($payload['aspects'] ?? []);
        if (!empty($aspects) && !$this->reviewAspectsModel->replaceReviewAspects((int) $reviewId, $aspects)) {
            $this->db->transRollback();

            return [
                'status_code' => 422,
                'body' => [
                    'status' => 422,
                    'message' => 'Aspect validation failed.',
                    'errors' => $this->reviewAspectsModel->errors(),
                ],
            ];
        }

        $mediaItems = $this->normalizeMedia($payload['media'] ?? []);
        foreach ($mediaItems as $mediaItem) {
            $mediaItem['review_id'] = $reviewId;
            if ($this->reviewMediaModel->insert($mediaItem) === false) {
                $this->db->transRollback();

                return [
                    'status_code' => 422,
                    'body' => [
                        'status' => 422,
                        'message' => 'Review media validation failed.',
                        'errors' => $this->reviewMediaModel->errors(),
                    ],
                ];
            }
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return [
                'status_code' => 500,
                'body' => [
                    'status' => 500,
                    'message' => 'Failed to save review.',
                ],
            ];
        }

        if (($reviewData['review_type'] ?? null) === 'service' && !empty($reviewData['service_id'])) {
            $this->syncServiceSummary((int) $reviewData['service_id']);
        }

        return [
            'status_code' => 201,
            'body' => [
                'status'    => 201,
                'message'   => 'Review submitted successfully.',
                'review_id' => (int) $reviewId,
            ],
            'review_id' => (int) $reviewId,
        ];
    }

    private function reviewResultResponse(array $result)
    {
        $statusCode = (int) ($result['status_code'] ?? 500);
        $body = $result['body'] ?? [
            'status' => 500,
            'message' => 'Unexpected review response.',
        ];

        if ($statusCode === 201) {
            return $this->respondCreated($body);
        }

        return $this->respond($body, $statusCode);
    }

    private function updateCustomerReviewRecord(array $review, array $payload): array
    {
        $reviewId = (int) ($review['id'] ?? 0);
        if ($reviewId <= 0) {
            return [
                'status_code' => 422,
                'body' => [
                    'status' => 422,
                    'message' => 'Valid review_id is required.',
                ],
            ];
        }

        $updateData = [
            'title'       => $payload['title'] ?? $review['title'] ?? null,
            'rating'      => $payload['rating'] ?? $review['rating'] ?? null,
            'review'      => $payload['review'] ?? $review['review'] ?? null,
            'is_verified' => isset($payload['is_verified']) ? (int) $payload['is_verified'] : (int) ($review['is_verified'] ?? 1),
            'status'      => $review['status'] ?? 'approved',
        ];

        $this->db->transStart();

        if (!$this->reviewsModel->update($reviewId, $updateData)) {
            $this->db->transRollback();

            return [
                'status_code' => 422,
                'body' => [
                    'status' => 422,
                    'message' => 'Validation failed.',
                    'errors' => $this->reviewsModel->errors(),
                ],
            ];
        }

        if (array_key_exists('aspects', $payload)) {
            $aspects = $this->normalizeAspects($payload['aspects'] ?? []);
            if (!$this->reviewAspectsModel->replaceReviewAspects($reviewId, $aspects)) {
                $this->db->transRollback();

                return [
                    'status_code' => 422,
                    'body' => [
                        'status' => 422,
                        'message' => 'Aspect validation failed.',
                        'errors' => $this->reviewAspectsModel->errors(),
                    ],
                ];
            }
        }

        if (array_key_exists('media', $payload)) {
            $mediaItems = $this->normalizeMedia($payload['media'] ?? []);
            $this->reviewMediaModel->where('review_id', $reviewId)->delete();

            foreach ($mediaItems as $mediaItem) {
                $mediaItem['review_id'] = $reviewId;
                if ($this->reviewMediaModel->insert($mediaItem) === false) {
                    $this->db->transRollback();

                    return [
                        'status_code' => 422,
                        'body' => [
                            'status' => 422,
                            'message' => 'Review media validation failed.',
                            'errors' => $this->reviewMediaModel->errors(),
                        ],
                    ];
                }
            }
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return [
                'status_code' => 500,
                'body' => [
                    'status' => 500,
                    'message' => 'Failed to update review.',
                ],
            ];
        }

        if (($review['review_type'] ?? null) === 'service' && !empty($review['service_id'])) {
            $this->syncServiceSummary((int) $review['service_id']);
        }

        return [
            'status_code' => 200,
            'body' => [
                'status'    => 200,
                'message'   => 'Review updated successfully.',
                'review_id' => $reviewId,
            ],
            'review_id' => $reviewId,
        ];
    }

    private function getBookingReviewServicesData(int $bookingId, int $customerId): array
    {
        $services = (new BookingServicesModel())
            ->select('booking_services.service_id, services.name as service_name, services.image as service_image, services.slug as service_slug')
            ->join('services', 'services.id = booking_services.service_id', 'left')
            ->where('booking_services.booking_id', $bookingId)
            ->where('booking_services.status !=', 'cancelled')
            ->where('booking_services.service_id IS NOT NULL', null, false)
            ->groupBy('booking_services.service_id, services.name, services.image, services.slug')
            ->orderBy('services.name', 'ASC')
            ->findAll();

        $reviews = $this->reviewsModel
            ->where('booking_id', $bookingId)
            ->where('user_id', $customerId)
            ->where('review_type', 'service')
            ->findAll();

        $reviewsByServiceId = [];
        foreach ($reviews as $review) {
            $serviceId = (int) ($review['service_id'] ?? 0);
            if ($serviceId > 0) {
                $reviewsByServiceId[$serviceId] = $review;
            }
        }

        $data = [];
        foreach ($services as $service) {
            $serviceId = (int) ($service['service_id'] ?? 0);
            $review = $reviewsByServiceId[$serviceId] ?? null;

            $data[] = [
                'booking_id' => $bookingId,
                'service_id' => $serviceId,
                'service_name' => $service['service_name'] ?? null,
                'service_image' => $service['service_image'] ?? null,
                'service_slug' => $service['service_slug'] ?? null,
                'has_review' => $review !== null,
                'can_add_review' => $review === null,
                'can_update_review' => $review !== null,
                'review' => $review ? [
                    'review_id' => (int) $review['id'],
                    'rating' => isset($review['rating']) ? (float) $review['rating'] : null,
                    'title' => $review['title'] ?? null,
                    'review' => $review['review'] ?? null,
                    'status' => $review['status'] ?? null,
                    'is_verified' => isset($review['is_verified']) ? (int) $review['is_verified'] : null,
                    'media' => $this->reviewMediaModel->where('review_id', (int) $review['id'])->findAll(),
                ] : null,
            ];
        }

        return $data;
    }

    private function getBookingReviewData(int $bookingId, int $customerId): ?array
    {
        $review = $this->reviewsModel
            ->where('booking_id', $bookingId)
            ->where('user_id', $customerId)
            ->where('review_type', 'booking')
            ->orderBy('id', 'DESC')
            ->first();

        if (!$review) {
            return null;
        }

        $reviewId = (int) $review['id'];

        return [
            'review_id' => $reviewId,
            'rating' => isset($review['rating']) ? (float) $review['rating'] : null,
            'title' => $review['title'] ?? null,
            'review' => $review['review'] ?? null,
            'status' => $review['status'] ?? null,
            'is_verified' => isset($review['is_verified']) ? (int) $review['is_verified'] : null,
            'aspects' => $this->reviewAspectsModel->where('review_id', $reviewId)->findAll(),
            'media' => $this->reviewMediaModel->where('review_id', $reviewId)->findAll(),
        ];
    }

    private function serviceBelongsToBooking(int $bookingId, int $serviceId): bool
    {
        return (new BookingServicesModel())
            ->where('booking_id', $bookingId)
            ->where('service_id', $serviceId)
            ->where('status !=', 'cancelled')
            ->countAllResults() > 0;
    }

    private function buildReviewSharePayload(array $resolved, string $submitApiUrl): array
    {
        $booking = $resolved['booking'];
        $bookingId = (int) $booking['id'];
        $customerId = (int) $resolved['link']['user_id'];

        return [
            'booking' => [
                'booking_id' => $bookingId,
                'booking_code' => $booking['booking_code'] ?? null,
                'slot_date' => $booking['slot_date'] ?? null,
                'status' => $booking['status'] ?? null,
                'payment_status' => $booking['payment_status'] ?? null,
                'customer_name' => $booking['customer_name'] ?? null,
            ],
            'booking_review' => $this->getBookingReviewData($bookingId, $customerId),
            'services' => $this->getBookingReviewServicesData($bookingId, $customerId),
            'expires_at' => $resolved['link']['expires_at'] ?? null,
            'submit_api_url' => $submitApiUrl,
        ];
    }

    private function submitResolvedReviewShareLink(array $resolved)
    {
        $payload = $this->getRequestData();
        $bookingId = (int) $resolved['link']['booking_id'];
        $customerId = (int) $resolved['link']['user_id'];
        $reviewType = (string) ($payload['review_type'] ?? 'booking');

        if (!in_array($reviewType, ['booking', 'service'], true)) {
            return $this->respond([
                'status' => 422,
                'message' => 'review_type must be booking or service for a shared booking review link.',
            ], 422);
        }

        $payload['booking_id'] = $bookingId;
        $payload['review_type'] = $reviewType;

        if ($reviewType === 'service') {
            $serviceId = (int) ($payload['service_id'] ?? 0);
            if ($serviceId <= 0 || !$this->serviceBelongsToBooking($bookingId, $serviceId)) {
                return $this->respond([
                    'status' => 422,
                    'message' => 'Valid service_id for this booking is required.',
                ], 422);
            }
        } else {
            $payload['service_id'] = null;
            $payload['partner_id'] = null;
        }

        $existingReview = $this->findExistingReviewForTarget($bookingId, $customerId, $reviewType, $payload);
        if ($existingReview) {
            $this->reviewShareLinkModel->update((int) $resolved['link']['id'], [
                'used_at' => date('Y-m-d H:i:s'),
            ]);

            return $this->reviewResultResponse($this->updateCustomerReviewRecord($existingReview, $payload));
        }

        $result = $this->storeCustomerReview($payload, $customerId, $bookingId, ['booking', 'service']);

        if (($result['status_code'] ?? 0) === 201) {
            $this->reviewShareLinkModel->update((int) $resolved['link']['id'], [
                'used_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return $this->reviewResultResponse($result);
    }

    private function findExistingReviewForTarget(int $bookingId, int $customerId, string $reviewType, array $payload): ?array
    {
        $builder = $this->reviewsModel
            ->where('booking_id', $bookingId)
            ->where('user_id', $customerId)
            ->where('review_type', $reviewType);

        if ($reviewType === 'service') {
            $builder->where('service_id', (int) ($payload['service_id'] ?? 0));
        }

        return $builder->orderBy('id', 'DESC')->first() ?: null;
    }

    private function resolveReviewShareLinkByCode(string $code): array
    {
        $code = trim($code);
        if (!preg_match('/^[A-Za-z0-9]{8,24}$/', $code)) {
            return [
                'error' => [
                    'status' => 422,
                    'body' => [
                        'status' => 422,
                        'message' => 'Invalid review link code.',
                    ],
                ],
            ];
        }

        $link = $this->reviewShareLinkModel
            ->where('code', $code)
            ->first();

        if (!$link) {
            return [
                'error' => [
                    'status' => 404,
                    'body' => [
                        'status' => 404,
                        'message' => 'Review link not found.',
                    ],
                ],
            ];
        }

        return $this->resolveReviewShareLinkRecord($link);
    }

    private function resolveReviewShareLinkRecord(array $link): array
    {
        if (!empty($link['revoked_at'])) {
            return [
                'error' => [
                    'status' => 410,
                    'body' => [
                        'status' => 410,
                        'message' => 'This review link has been revoked.',
                    ],
                ],
            ];
        }

        if (!empty($link['expires_at']) && strtotime((string) $link['expires_at']) < time()) {
            return [
                'error' => [
                    'status' => 410,
                    'body' => [
                        'status' => 410,
                        'message' => 'This review link has expired.',
                    ],
                ],
            ];
        }

        $booking = (new BookingsModel())
            ->select('bookings.id, bookings.booking_code, bookings.user_id, bookings.slot_date, bookings.status, bookings.payment_status, customers.name as customer_name')
            ->join('customers', 'customers.id = bookings.user_id', 'left')
            ->where('bookings.id', (int) $link['booking_id'])
            ->first();

        if (!$booking || (int) ($booking['user_id'] ?? 0) !== (int) $link['user_id']) {
            return [
                'error' => [
                    'status' => 404,
                    'body' => [
                        'status' => 404,
                        'message' => 'Booking for this review link was not found.',
                    ],
                ],
            ];
        }

        return [
            'link' => $link,
            'booking' => $booking,
        ];
    }

    private function buildReviewShareUrls(
        string $token,
        ?string $code = null,
        ?string $frontendUrl = null,
        ?string $deepLinkUrl = null
    ): array {
        $encodedCode = rawurlencode((string) ($code ?: $token));
        $apiUrl = base_url('reviews/code/' . $encodedCode);
        $submitApiUrl = $apiUrl;
        $frontendUrl = trim((string) ($frontendUrl ?? getenv('FRONT_URL') ?: ''));
        $deepLinkUrl = $this->buildReviewDeepLinkUrl($token, $code, $deepLinkUrl);

        $reviewUrl = $frontendUrl !== ''
            ? rtrim($frontendUrl, '/') . '/booking-review/' . $encodedCode
            : $apiUrl;
        $shortUrl = $frontendUrl !== ''
            ? rtrim($frontendUrl, '/') . '/booking-review/' . $encodedCode
            : $apiUrl;

        return [
            'review_url' => $reviewUrl,
            'short_url' => $shortUrl,
            'deep_link_url' => $deepLinkUrl,
            'preferred_share_url' => $deepLinkUrl ?: $shortUrl,
            'api_url' => $apiUrl,
            'submit_api_url' => $submitApiUrl,
        ];
    }

    private function buildReviewDeepLinkUrl(string $token, ?string $code = null, ?string $deepLinkUrl = null): ?string
    {
        $deepLinkUrl = trim((string) ($deepLinkUrl ?? getenv('REVIEW_DEEP_LINK_URL') ?: getenv('DEEP_LINK_URL') ?: ''));
        if ($deepLinkUrl === '') {
            return null;
        }

        $encodedToken = rawurlencode($token);
        $encodedCode = rawurlencode((string) ($code ?: $token));
        if (str_contains($deepLinkUrl, '{token}')) {
            return str_replace('{token}', $encodedToken, $deepLinkUrl);
        }

        if (str_contains($deepLinkUrl, '{code}')) {
            return str_replace('{code}', $encodedCode, $deepLinkUrl);
        }

        $separator = str_contains($deepLinkUrl, '?') ? '&' : '?';

        return $deepLinkUrl . $separator . 'code=' . $encodedCode;
    }

    private function generateReviewLinkCode(): string
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $code = bin2hex(random_bytes(8));
            $exists = $this->reviewShareLinkModel
                ->where('code', $code)
                ->countAllResults() > 0;

            if (!$exists) {
                return $code;
            }
        }

        throw new Exception('Unable to generate a unique review link code.');
    }

    private function getRequestData(): array
    {
        $query = $this->request->getGet();
        if (is_array($query) && !empty($query)) {
            return $query;
        }

        $json = $this->request->getJSON(true);
        if (is_array($json) && !empty($json)) {
            return $json;
        }

        $data = $this->request->getPost();
        if (is_array($data) && !empty($data)) {
            return $data;
        }

        $raw = $this->request->getRawInput();

        return is_array($raw) ? $raw : [];
    }

    private function getAuthUser(): array
    {
        return (array) session()->get('auth_user');
    }

    private function getCustomerIdFromSession(): ?int
    {
        $authUser = $this->getAuthUser();
        $customerId = (int) ($authUser['customer_id'] ?? 0);

        return $customerId > 0 ? $customerId : null;
    }

    private function isAdminSession(): bool
    {
        $authUser = $this->getAuthUser();

        return (($authUser['aud'] ?? '') === 'Admin' && !empty($authUser['id']));
    }

    private function getAdminIdFromSession(): ?int
    {
        $authUser = $this->getAuthUser();
        $adminId = (int) ($authUser['admin_id'] ?? $authUser['id'] ?? 0);

        return $adminId > 0 ? $adminId : null;
    }

    private function normalizeAspects($aspects): array
    {
        if (!is_array($aspects)) {
            return [];
        }

        $normalized = [];
        foreach ($aspects as $aspect) {
            if (!is_array($aspect) || empty($aspect['aspect']) || !isset($aspect['rating'])) {
                continue;
            }

            $normalized[] = [
                'aspect' => trim((string) $aspect['aspect']),
                'rating' => (int) $aspect['rating'],
            ];
        }

        return $normalized;
    }

    private function normalizeMedia($media): array
    {
        if (!is_array($media)) {
            return [];
        }

        $normalized = [];
        foreach ($media as $item) {
            if (!is_array($item) || empty($item['media_url'])) {
                continue;
            }

            $normalized[] = [
                'media_type' => in_array(($item['media_type'] ?? 'image'), ['image', 'video'], true) ? $item['media_type'] : 'image',
                'media_url'  => trim((string) $item['media_url']),
            ];
        }

        return $normalized;
    }


    private function appendReviewRelations(array $reviews): array
    {
        if (empty($reviews)) {
            return [];
        }

        $reviewIds = [];
        $serviceIds = [];
        $partnerIds = [];
        $customerIds = [];

        foreach ($reviews as $review) {
            $reviewIds[] = (int) $review['id'];

            if (!empty($review['service_id'])) {
                $serviceIds[] = (int) $review['service_id'];
            }

            if (!empty($review['partner_id'])) {
                $partnerIds[] = (int) $review['partner_id'];
            }

            if (!empty($review['user_id'])) {
                $customerIds[] = (int) $review['user_id'];
            }
        }

        $reviewIds = array_values(array_unique(array_filter($reviewIds)));
        $serviceIds = array_values(array_unique(array_filter($serviceIds)));
        $partnerIds = array_values(array_unique(array_filter($partnerIds)));
        $customerIds = array_values(array_unique(array_filter($customerIds)));

        $aspectsByReviewId = [];
        if (!empty($reviewIds)) {
            $aspectRows = $this->reviewAspectsModel
                ->whereIn('review_id', $reviewIds)
                ->findAll();

            foreach ($aspectRows as $aspectRow) {
                $aspectsByReviewId[(int) $aspectRow['review_id']][] = $aspectRow;
            }
        }

        $mediaByReviewId = [];
        if (!empty($reviewIds)) {
            $mediaRows = $this->reviewMediaModel
                ->whereIn('review_id', $reviewIds)
                ->findAll();

            foreach ($mediaRows as $mediaRow) {
                $mediaByReviewId[(int) $mediaRow['review_id']][] = $mediaRow;
            }
        }

        $voteSummaryByReviewId = [];
        if (!empty($reviewIds)) {
            $voteRows = $this->db->table('review_votes')
                ->select('review_id, vote, COUNT(*) as total')
                ->whereIn('review_id', $reviewIds)
                ->groupBy('review_id, vote')
                ->get()
                ->getResultArray();

            foreach ($voteRows as $voteRow) {
                $reviewId = (int) $voteRow['review_id'];

                if (!isset($voteSummaryByReviewId[$reviewId])) {
                    $voteSummaryByReviewId[$reviewId] = [
                        'helpful' => 0,
                        'not_helpful' => 0,
                    ];
                }

                $voteType = (string) ($voteRow['vote'] ?? '');
                if ($voteType === 'helpful' || $voteType === 'not_helpful') {
                    $voteSummaryByReviewId[$reviewId][$voteType] = (int) $voteRow['total'];
                }
            }
        }

        $servicesById = [];
        if (!empty($serviceIds)) {
            $serviceRows = $this->db->table('services')
                ->whereIn('id', $serviceIds)
                ->get()
                ->getResultArray();

            foreach ($serviceRows as $serviceRow) {
                $servicesById[(int) $serviceRow['id']] = $serviceRow;
            }
        }

        $partnersById = [];
        if (!empty($partnerIds)) {
            $partnerRows = $this->db->table('partners')
                ->whereIn('id', $partnerIds)
                ->get()
                ->getResultArray();

            foreach ($partnerRows as $partnerRow) {
                $partnersById[(int) $partnerRow['id']] = $partnerRow;
            }
        }

        $customersById = [];
        if (!empty($customerIds)) {
            $customerRows = $this->db->table('customers')
                ->whereIn('id', $customerIds)
                ->get()
                ->getResultArray();

            foreach ($customerRows as $customerRow) {
                $customersById[(int) $customerRow['id']] = $customerRow;
            }
        }

        foreach ($reviews as &$review) {
            $reviewId = (int) $review['id'];
            $serviceId = (int) ($review['service_id'] ?? 0);
            $partnerId = (int) ($review['partner_id'] ?? 0);
            $customerId = (int) ($review['user_id'] ?? 0);

            $review['aspects'] = $aspectsByReviewId[$reviewId] ?? [];
            $review['media'] = $mediaByReviewId[$reviewId] ?? [];
            $review['vote_summary'] = $voteSummaryByReviewId[$reviewId] ?? [
                'helpful' => 0,
                'not_helpful' => 0,
            ];
            $review['service'] = $serviceId > 0 ? ($servicesById[$serviceId] ?? null) : null;
            $review['partner'] = $partnerId > 0 ? ($partnersById[$partnerId] ?? null) : null;
            $review['customer'] = $customerId > 0 ? ($customersById[$customerId] ?? null) : null;
        }
        unset($review);

        return $reviews;
    }

    private function syncServiceSummary(int $serviceId): void
    {
        $approvedReviews = $this->reviewsModel
            ->where('review_type', 'service')
            ->where('service_id', $serviceId)
            ->where('status', 'approved')
            ->findAll();

        $totalReviews = count($approvedReviews);
        $totalRating = 0.0;
        $bucketCounts = [
            'rating_1' => 0,
            'rating_2' => 0,
            'rating_3' => 0,
            'rating_4' => 0,
            'rating_5' => 0,
        ];

        foreach ($approvedReviews as $review) {
            $rating = (float) ($review['rating'] ?? 0);
            $totalRating += $rating;

            if ($rating < 2) {
                $bucketCounts['rating_1']++;
            } elseif ($rating < 3) {
                $bucketCounts['rating_2']++;
            } elseif ($rating < 4) {
                $bucketCounts['rating_3']++;
            } elseif ($rating < 5) {
                $bucketCounts['rating_4']++;
            } else {
                $bucketCounts['rating_5']++;
            }
        }

        $summaryData = [
            'avg_rating'    => $totalReviews > 0 ? round($totalRating / $totalReviews, 2) : 0,
            'total_reviews' => $totalReviews,
            'rating_1'      => $bucketCounts['rating_1'],
            'rating_2'      => $bucketCounts['rating_2'],
            'rating_3'      => $bucketCounts['rating_3'],
            'rating_4'      => $bucketCounts['rating_4'],
            'rating_5'      => $bucketCounts['rating_5'],
            'updated_at'    => date('Y-m-d H:i:s'),
        ];

        $this->serviceReviewSummaryModel->saveSummary($serviceId, $summaryData);
    }
}
