<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\BookingUpdateMediaModel;
use App\Models\BookingUpdateModel;
use CodeIgniter\API\ResponseTrait;

class BookingUpdateController extends BaseController
{
    protected $bookingUpdateModel;
    protected $mediaModel;
    use ResponseTrait;

    public function __construct()
    {
        $this->bookingUpdateModel = new BookingUpdateModel();
        $this->mediaModel = new BookingUpdateMediaModel();
    }

    /**
     * POST /booking-updates
     * Create a new booking update with optional media
     */
    public function create()
    {
        $rules = [
            'booking_service_id' => 'required|integer',
            'partner_id'         => 'required|integer',
            'message'            => 'permit_empty|string',
            'status'             => 'permit_empty|string',
            'media.*'            => 'permit_empty|uploaded[media]|max_size[media,5120]|ext_in[media,jpg,jpeg,png,mp4,pdf]'
        ];

        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        // Save the update
        $updateData = [
            'booking_service_id' => $this->request->getVar('booking_service_id'),
            'partner_id'         => $this->request->getVar('partner_id'),
            'message'            => $this->request->getVar('message'),
            'status'             => $this->request->getVar('status')
        ];

        $updateId = $this->bookingUpdateModel->insert($updateData, true);

        // Save media if uploaded
        $uploadedFiles = $this->request->getFiles();
        if (!empty($uploadedFiles['media'])) {
            foreach ($uploadedFiles['media'] as $file) {
                if ($file->isValid() && !$file->hasMoved()) {
                    $path = $file->store('booking_media'); // saved to writable/uploads/booking_media/
                    $this->mediaModel->insert([
                        'booking_update_id' => $updateId,
                        'media_type'        => $this->detectMediaType($file->getClientMimeType()),
                        'file_url'          => 'uploads/' . $path,
                        'label'             => $file->getClientName()
                    ]);
                }
            }
        }

        return $this->respondCreated(['status' => true, 'message' => 'Update submitted']);
    }

    /**
     * GET /booking-updates/{booking_service_id}
     * List updates for a booking with media
     */
    public function list($bookingServiceId)
    {
        $updates = $this->bookingUpdateModel
            ->where('booking_service_id', $bookingServiceId)
            ->orderBy('created_at', 'asc')
            ->findAll();

        foreach ($updates as &$update) {
            $update['media'] = $this->mediaModel
                ->where('booking_update_id', $update['id'])
                ->findAll();
        }

        return $this->respond([
            'status' => true,
            'data' => $updates
        ]);
    }

    /**
     * Helper to detect media type
     */
    private function detectMediaType($mime)
    {
        if (str_starts_with($mime, 'image/')) return 'image';
        if (str_starts_with($mime, 'video/')) return 'video';
        if (str_contains($mime, 'pdf')) return 'document';
        return 'file';
    }
}
