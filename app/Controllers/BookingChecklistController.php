<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ChecklistFeedbackModel;

class ChecklistFeedbackController extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new ChecklistFeedbackModel();
    }

    /**
     * Submit single feedback entry
     */
    public function submit()
    {
        $rules = [
            'assignment_checklist_id' => 'required|integer',
            'question_id'             => 'required|integer',
            'rating'                  => 'permit_empty|integer',
            'comment'                 => 'permit_empty|string'
        ];

        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                'status' => 400,
                'errors' => $this->validator->getErrors()
            ]);
        }

        $data = $this->request->getPost();
        $data['created_at'] = date('Y-m-d H:i:s');

        $this->model->insert($data);

        return $this->response->setJSON([
            'status' => 200,
            'message' => 'Feedback submitted successfully'
        ]);
    }

    /**
     * Submit multiple feedback entries in one request
     */
    public function submitBulk()
    {
        $entries = $this->request->getJSON(true);

        if (!is_array($entries)) {
            return $this->response->setJSON([
                'status' => 400,
                'message' => 'Invalid payload format. Expecting array of feedback entries.'
            ]);
        }

        foreach ($entries as $entry) {
            if (empty($entry['assignment_checklist_id']) || empty($entry['question_id'])) {
                continue;
            }
            $entry['created_at'] = date('Y-m-d H:i:s');
            $this->model->insert($entry);
        }

        return $this->response->setJSON([
            'status' => 200,
            'message' => 'Bulk feedback submitted'
        ]);
    }

    /**
     * Get feedback by assignment checklist ID
     */
    public function getByAssignment($assignmentChecklistId)
    {
        $feedback = $this->model
            ->where('assignment_checklist_id', $assignmentChecklistId)
            ->findAll();

        return $this->response->setJSON([
            'status' => 200,
            'data' => $feedback
        ]);
    }
}
