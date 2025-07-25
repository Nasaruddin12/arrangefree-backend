<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ServiceChecklistModel;
use App\Models\BookingChecklistStatusModel;

class ChecklistController extends BaseController
{
    use \CodeIgniter\API\ResponseTrait;
    public function getServiceChecklist($serviceId)
    {
        $model = new ServiceChecklistModel();
        $checklists = $model->where('service_id', $serviceId)->orderBy('sort_order')->findAll();

        return $this->response->setJSON(['status' => true, 'data' => $checklists]);
    }

    public function getChecklistStatus($bookingServiceId)
    {
        $model = new BookingChecklistStatusModel();
        $statuses = $model
            ->select('booking_assignment_checklist_status.*, service_checklists.title')
            ->join('service_checklists', 'service_checklists.id = booking_assignment_checklist_status.checklist_id')
            ->where('booking_service_id', $bookingServiceId)
            ->findAll();

        return $this->response->setJSON(['status' => true, 'data' => $statuses]);
    }

    public function updateChecklistItem()
    {
        $rules = [
            'booking_service_id' => 'required|integer',
            'checklist_id'       => 'required|integer',
            'partner_id'         => 'required|integer',
            'is_done'            => 'required|in_list[0,1]',
            'note'               => 'permit_empty|string',
            'image_url'          => 'permit_empty|valid_url'
        ];

        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $model = new BookingChecklistStatusModel();

        $data = [
            'booking_service_id' => $this->request->getVar('booking_service_id'),
            'checklist_id'       => $this->request->getVar('checklist_id'),
            'partner_id'         => $this->request->getVar('partner_id'),
            'is_done'            => $this->request->getVar('is_done'),
            'note'               => $this->request->getVar('note'),
            'image_url'          => $this->request->getVar('image_url'),
            'updated_at'         => date('Y-m-d H:i:s')
        ];

        // Update if exists or insert new
        $existing = $model
            ->where('booking_service_id', $data['booking_service_id'])
            ->where('checklist_id', $data['checklist_id'])
            ->first();

        if ($existing) {
            $model->update($existing['id'], $data);
        } else {
            $model->insert($data);
        }

        return $this->response->setJSON(['status' => true, 'message' => 'Checklist updated']);
    }
}
