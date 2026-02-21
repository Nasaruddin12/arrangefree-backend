<?php

namespace App\Controllers;
use App\Controllers\BaseController;
use App\Models\ServiceOfferModel;
use App\Models\ServiceOfferTargetModel;


class ServiceOfferController extends BaseController
{
    protected $modelName = ServiceOfferModel::class;
    protected $format = 'json';
    protected $offerModel;
    protected $targetModel;

    public function __construct()
    {
        $this->offerModel = new ServiceOfferModel();
        $this->targetModel = new ServiceOfferTargetModel();
    }

        /**
         * DELETE OFFER
         */
        public function delete($id)
        {
            // Delete offer
            $deleted = $this->offerModel->delete($id);
            // Delete related targets
            $this->targetModel->where('offer_id', $id)->delete();
            if ($deleted) {
                return $this->response->setJSON([
                    'status' => true,
                    'message' => 'Offer deleted successfully'
                ]);
            } else {
                return $this->response->setStatusCode(404)->setJSON([
                    'status' => false,
                    'message' => 'Offer not found or could not be deleted.'
                ]);
            }
        }

        /**
         * CHANGE OFFER STATUS
         */
        public function changeStatus($id)
        {
            $data = $this->request->getJSON(true);
            $status = $data['is_active'] ?? null;
            if ($status === null) {
                return $this->response->setStatusCode(400)->setJSON([
                    'status' => false,
                    'message' => 'Missing is_active field.'
                ]);
            }
            $updated = $this->offerModel->update($id, ['is_active' => $status]);
            if ($updated) {
                return $this->response->setJSON([
                    'status' => true,
                    'message' => 'Offer status updated successfully.'
                ]);
            } else {
                return $this->response->setStatusCode(404)->setJSON([
                    'status' => false,
                    'message' => 'Offer not found or could not update status.'
                ]);
            }
        }

    /**
     * List all service offers
     */
    /**
     * CREATE OFFER
     */
    public function create()
    {
        $data = $this->request->getJSON(true);
        if (($data['discount_type'] ?? null) !== 'percentage') {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => false,
                'message' => 'Only percentage discount_type is supported.'
            ]);
        }

        $offerData = [
            'title'          => $data['title'] ?? null,
            'discount_type'  => $data['discount_type'],
            'discount_value' => $data['discount_value'],
            'start_date'     => $data['start_date'] ?? null,
            'end_date'       => $data['end_date'] ?? null,
            'priority'       => $data['priority'] ?? 1,
            'is_active'      => $data['is_active'] ?? 1,
        ];

        $this->offerModel->insert($offerData);
        $offerId = $this->offerModel->getInsertID();

        // Insert Targets
        if (!empty($data['targets'])) {
            foreach ($data['targets'] as $target) {
                $this->targetModel->insert([
                    'offer_id'    => $offerId,
                    'target_type' => $target['target_type'],
                    'service_id'  => $target['service_id'] ?? null,
                    'category_id' => $target['category_id'] ?? null,
                ]);
            }
        }

        return $this->response->setJSON([
            'status' => true,
            'message' => 'Offer created successfully'
        ]);
    }

    /**
     * UPDATE OFFER
     */
    public function update($id)
    {
        $data = $this->request->getJSON(true);
        if (($data['discount_type'] ?? null) !== 'percentage') {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => false,
                'message' => 'Only percentage discount_type is supported.'
            ]);
        }

        $offerData = [
            'title'          => $data['title'] ?? null,
            'discount_type'  => $data['discount_type'],
            'discount_value' => $data['discount_value'],
            'start_date'     => $data['start_date'] ?? null,
            'end_date'       => $data['end_date'] ?? null,
            'priority'       => $data['priority'] ?? 1,
            'is_active'      => $data['is_active'] ?? 1,
        ];

        $this->offerModel->update($id, $offerData);

        // Delete old targets
        $this->targetModel->where('offer_id', $id)->delete();

        // Insert new targets
        if (!empty($data['targets'])) {
            foreach ($data['targets'] as $target) {
                $this->targetModel->insert([
                    'offer_id'    => $id,
                    'target_type' => $target['target_type'],
                    'service_id'  => $target['service_id'] ?? null,
                    'category_id' => $target['category_id'] ?? null,
                ]);
            }
        }

        return $this->response->setJSON([
            'status' => true,
            'message' => 'Offer updated successfully'
        ]);
    }

    /**
     * LIST OFFERS
     */
    public function list()
    {
        $offers = $this->offerModel->findAll();

        foreach ($offers as &$offer) {
            $offer['targets'] = $this->targetModel
                ->where('offer_id', $offer['id'])
                ->findAll();
        }

        return $this->response->setJSON([
            'status' => true,
            'data'   => $offers
        ]);
    }
}
