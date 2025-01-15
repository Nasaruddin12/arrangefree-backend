<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\RatingReviewModel;
use CodeIgniter\API\ResponseTrait;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class RatingReviewController extends BaseController
{
    use ResponseTrait;
    public function createRatingReview()
    {
        try {
            $ratingReviewModel = new RatingReviewModel();
            $validation = &$ratingReviewModel;
            $statusCode = 200;

            $userBackendToFrontendAttrs = [
                'customer_id' => 'customer_id',
                'product_id' => 'product_id',
                'rating' => 'rating',
                'review' => 'review',
            ];

            $ratingReviewData = [];
            foreach ($userBackendToFrontendAttrs as $backendAttr => $frontendAttr) {
                $ratingReviewData[$backendAttr] = $this->request->getVar($frontendAttr);
            }

            $ratingReviewModel->insert($ratingReviewData);

            if ($ratingReviewModel->db->error()['code']) {
                throw new Exception($ratingReviewModel->db->error()['message'], 500);
            }

            if (!empty($ratingReviewModel->errors())) {
                throw new Exception('Validation', 400);
            }

            if ($ratingReviewModel->db->affectedRows() == 1) {
                $statusCode = 200;
                $response = [
                    'message' => 'Rating and review created successfully.',
                    'rating_review_id' => $ratingReviewModel->db->insertID(),
                ];
            }
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? ['validation' => $validation->errors()] : $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    public function getRatingReviewById($id)
    {
        try {
            $ratingReviewModel = new RatingReviewModel();
            $ratingReview = $ratingReviewModel->select(['af_rating_review.id', 'af_customers.name AS customer_name', 'af_rating_review.rating AS rating', 'af_rating_review.review AS review', 'af_rating_review.status AS status', 'af_rating_review.created_at AS created_at'])
                ->join('af_customers', 'af_customers.id = af_rating_review.customer_id')
                ->where('af_rating_review.product_id', $id)
                ->findAll();
            if (empty($ratingReview)) {
                throw new Exception('Rating and review not found.', 404);
            }
            $ratingData = array(
                "1" => 0,
                "2" => 0,
                "3" => 0,
                "4" => 0,
                "5" => 0,
            );
            foreach ($ratingReview as $rating) {
                switch ($rating['rating']) {
                    case "1":
                        $ratingData["1"]++;
                        break;
                    case "2":
                        $ratingData["2"]++;
                        break;
                    case "3":
                        $ratingData["3"]++;
                        break;
                    case "4":
                        $ratingData["4"]++;
                        break;
                    case "5":
                        $ratingData["5"]++;
                        break;
                }
            }

            $totalCount = count($ratingReview);
            $avgData = array();
            $avgData["one"] = ($ratingData["1"] / $totalCount) * 100;
            $avgData["two"] = ($ratingData["2"] / $totalCount) * 100;
            $avgData["three"] = ($ratingData["3"] / $totalCount) * 100;
            $avgData["four"] = ($ratingData["4"] / $totalCount) * 100;
            $avgData["five"] = ($ratingData["5"] / $totalCount) * 100;

            $obtainedRating = 0;
            $obtainedRating += $ratingData["1"] * 1;
            $obtainedRating += $ratingData["2"] * 2;
            $obtainedRating += $ratingData["3"] * 3;
            $obtainedRating += $ratingData["4"] * 4;
            $obtainedRating += $ratingData["5"] * 5;
            $avgData['avg'] = $obtainedRating / $totalCount;


            // $customerID = $this->request->getvar('id');
            // $customerRartingReview = $ratingReviewModel->where('customer_id', $customerID)->findAll();



            $statusCode = 200;
            $response = [
                'data' => $ratingReview,
                'avg_data' => $avgData,
                // 'customer_review' => $customerRartingReview,
            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 404 ? 404 : 500;
            $response = [
                'error' => $e->getCode() === 404 ? 'Rating and review not found.' : $e->getMessage(),
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }


    public function updateRatingReview($id)
    {
        try {
            $ratingReviewModel = new RatingReviewModel();
            $validation = &$ratingReviewModel;
            $statusCode = 200;

            $ratingReview = $ratingReviewModel->find($id);

            if (!$ratingReview) {
                throw new Exception('Rating and review not found.', 404);
            }

            $userBackendToFrontendAttrs = [
                'customer_id' => 'customer_id',
                'product_id' => 'product_id',
                'rating' => 'rating',
                'review' => 'review',
            ];

            $ratingReviewData = [];
            foreach ($userBackendToFrontendAttrs as $backendAttr => $frontendAttr) {
                $ratingReviewData[$backendAttr] = $this->request->getVar($frontendAttr);
            }

            $ratingReviewModel->update($id, $ratingReviewData);

            if ($ratingReviewModel->db->error()['code']) {
                throw new Exception($ratingReviewModel->db->error()['message'], 500);
            }

            if (!empty($ratingReviewModel->errors())) {
                throw new Exception('Validation', 400);
            }

            $statusCode = 200;
            $response = [
                'message' => 'Rating and review updated successfully.',
            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 404 ? 404 : ($e->getCode() === 400 ? 400 : 500);
            $response = [
                'error' => $e->getCode() === 404 ? 'Rating and review not found.' : ($e->getCode() === 400 ? ['validation' => $validation->errors()] : $e->getMessage()),
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    public function deleteRatingReview($id)
    {
        try {
            $ratingReviewModel = new RatingReviewModel();
            $ratingReview = $ratingReviewModel->find($id);

            if (!$ratingReview) {
                throw new Exception('Rating and review not found.', 404);
            }

            $ratingReviewModel->delete($id);

            if ($ratingReviewModel->db->error()['code']) {
                throw new Exception($ratingReviewModel->db->error()['message'], 500);
            }

            $statusCode = 200;
            $response = [
                'message' => 'Rating and review deleted successfully.',
            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 404 ? 404 : 500;
            $response = [
                'error' => $e->getCode() === 404 ? 'Rating and review not found.' : $e->getMessage(),
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    public function getAllRatingReview()
    {
        try {
            $ratingReviewModel = new RatingReviewModel();
            $reviews = $ratingReviewModel->findAll();

            $statusCode = 200;
            $response = [
                'data' => $reviews,
            ];
        } catch (Exception $e) {
            $statusCode = 500;
            $response = [
                'error' => $e->getMessage(),
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    public function updateStatus($reviewID)
    {
        try {
            $ratingReviewModel = new RatingReviewModel();
            // $reviewID = $this->request->getVar('id');
            $status = $this->request->getVar('status');
            $validation = &$ratingReviewModel;
            $ratingReviewModel->set(['status' => $status])->update($reviewID);

            if ($ratingReviewModel->db->affectedRows() == 1) {
                $statusCode = 200;
                $response = [
                    'message' => 'Status updated successfully.',
                ];
            } else {
                $statusCode = 500;
                $response = [
                    'message' => 'Nothing to update!',
                ];
            }
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 404 ? 404 : ($e->getCode() === 400 ? 400 : 500);
            $response = [
                'error' => $e->getCode() === 404 ? 'Rating and review not found.' : ($e->getCode() === 400 ? ['validation' => $validation->errors()] : $e->getMessage()),
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    public function getReviewRatingByCustomerId($id)
    {
        $ratingReviewModel = new RatingReviewModel();
        $productID = $this->request->getVar('product_id');

        try {
            $ratingReviewData = $ratingReviewModel->where('customer_id', $id)->where('product_id', $productID)->findAll();

            $statusCode = 200;
            $response = [
                'data' => $ratingReviewData
            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? $ratingReviewModel->errors() : $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }
}
