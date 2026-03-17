<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\BookingsModel;
use App\Models\BookingServicesModel;
use App\Models\ReviewAspectsModel;
use App\Models\ReviewMediaModel;
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
            $reviewData = [
                'review_type' => $payload['review_type'] ?? 'service',
                'booking_id'  => $payload['booking_id'] ?? null,
                'service_id'  => $payload['service_id'] ?? null,
                'partner_id'  => $payload['partner_id'] ?? null,
                'user_id'     => $customerId,
                'title'       => $payload['title'] ?? null,
                'rating'      => $payload['rating'] ?? null,
                'review'      => $payload['review'] ?? null,
                'is_verified' => isset($payload['is_verified']) ? (int) $payload['is_verified'] : 1,
                'status'      => 'approved',
            ];

            if ($this->reviewsModel->hasDuplicateReview($reviewData)) {
                return $this->respond([
                    'status'  => 409,
                    'message' => 'Review already submitted for this booking and target.',
                ], 409);
            }

            $this->db->transStart();

            $reviewId = $this->reviewsModel->insert($reviewData, true);
            if ($reviewId === false) {
                $this->db->transRollback();
                return $this->respond([
                    'status' => 422,
                    'message' => 'Validation failed.',
                    'errors' => $this->reviewsModel->errors(),
                ], 422);
            }

            $aspects = $this->normalizeAspects($payload['aspects'] ?? []);
            if (!empty($aspects) && !$this->reviewAspectsModel->replaceReviewAspects((int) $reviewId, $aspects)) {
                $this->db->transRollback();
                return $this->respond([
                    'status' => 422,
                    'message' => 'Aspect validation failed.',
                    'errors' => $this->reviewAspectsModel->errors(),
                ], 422);
            }

            $mediaItems = $this->normalizeMedia($payload['media'] ?? []);
            foreach ($mediaItems as $mediaItem) {
                $mediaItem['review_id'] = $reviewId;
                if ($this->reviewMediaModel->insert($mediaItem) === false) {
                    $this->db->transRollback();
                    return $this->respond([
                        'status' => 422,
                        'message' => 'Review media validation failed.',
                        'errors' => $this->reviewMediaModel->errors(),
                    ], 422);
                }
            }

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                return $this->respond(['status' => 500, 'message' => 'Failed to save review.'], 500);
            }

            if (($reviewData['review_type'] ?? null) === 'service' && !empty($reviewData['service_id'])) {
                $this->syncServiceSummary((int) $reviewData['service_id']);
            }

            return $this->respondCreated([
                'status'    => 201,
                'message'   => 'Review submitted successfully.',
                'review_id' => $reviewId,
            ]);
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

        return $this->respond([
            'status' => 200,
            'booking_id' => $bookingId,
            'data' => $data,
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
            $updateData = [
                'title'       => $payload['title'] ?? $review['title'] ?? null,
                'rating'      => $payload['rating'] ?? $review['rating'] ?? null,
                'review'      => $payload['review'] ?? $review['review'] ?? null,
                'is_verified' => isset($payload['is_verified']) ? (int) $payload['is_verified'] : (int) ($review['is_verified'] ?? 1),
                'status'      => $review['status'] ?? 'approved',
            ];

            $this->db->transStart();

            if (!$this->reviewsModel->update((int) $reviewId, $updateData)) {
                $this->db->transRollback();
                return $this->respond([
                    'status' => 422,
                    'message' => 'Validation failed.',
                    'errors' => $this->reviewsModel->errors(),
                ], 422);
            }

            if (array_key_exists('aspects', $payload)) {
                $aspects = $this->normalizeAspects($payload['aspects'] ?? []);
                if (!$this->reviewAspectsModel->replaceReviewAspects((int) $reviewId, $aspects)) {
                    $this->db->transRollback();
                    return $this->respond([
                        'status' => 422,
                        'message' => 'Aspect validation failed.',
                        'errors' => $this->reviewAspectsModel->errors(),
                    ], 422);
                }
            }

            if (array_key_exists('media', $payload)) {
                $mediaItems = $this->normalizeMedia($payload['media'] ?? []);
                $this->reviewMediaModel->where('review_id', (int) $reviewId)->delete();

                foreach ($mediaItems as $mediaItem) {
                    $mediaItem['review_id'] = (int) $reviewId;
                    if ($this->reviewMediaModel->insert($mediaItem) === false) {
                        $this->db->transRollback();
                        return $this->respond([
                            'status' => 422,
                            'message' => 'Review media validation failed.',
                            'errors' => $this->reviewMediaModel->errors(),
                        ], 422);
                    }
                }
            }

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                return $this->respond(['status' => 500, 'message' => 'Failed to update review.'], 500);
            }

            if (($review['review_type'] ?? null) === 'service' && !empty($review['service_id'])) {
                $this->syncServiceSummary((int) $review['service_id']);
            }

            return $this->respond([
                'status'    => 200,
                'message'   => 'Review updated successfully.',
                'review_id' => (int) $reviewId,
            ], 200);
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

        // die($customerId);
        print_r($payload);
        echo ($customerId);
        if ($customerId === null && $guestToken === '') {
            return $this->respond([
                'status' => 422,
                'message' => 'guest_token is required for guest voting.',
            ], 422);
        }

        $voteData = [
            'review_id' => (int) $reviewId,
            'user_id'   => $customerId,
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
        foreach ($reviews as &$review) {
            $reviewId = (int) $review['id'];
            $review['aspects'] = $this->reviewAspectsModel->getByReviewId($reviewId);
            $review['media'] = $this->reviewMediaModel->where('review_id', $reviewId)->findAll();

            $review['vote_summary'] = [
                'helpful' => $this->reviewVotesModel->where('review_id', $reviewId)->where('vote', 'helpful')->countAllResults(),
                'not_helpful' => $this->reviewVotesModel->where('review_id', $reviewId)->where('vote', 'not_helpful')->countAllResults(),
            ];
        }

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
