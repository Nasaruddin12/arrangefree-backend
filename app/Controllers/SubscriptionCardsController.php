<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\SubscriptionCardsModel;
use App\Models\SubscriptionCardDetailsModel;
use App\Models\SubscriptionCardPricingModel;
use App\Models\SubscriptionCardCommercialPricingModel;
use CodeIgniter\API\ResponseTrait;
use Exception;

class SubscriptionCardsController extends BaseController
{
    use ResponseTrait;
    public function get_all_cards()
    {
        $SubscriptionCardsModel = new SubscriptionCardsModel();
        $card_data = $SubscriptionCardsModel->findAll();
        $response = [
            'status' => 200,
            'data' => $card_data
        ];
        return $this->respond($response);
    }

    public function create_cards()
    {
        $SubscriptionCardsModel = new SubscriptionCardsModel();
        $data = $this->request->getVar();
        if ($SubscriptionCardsModel->insert($data)) {
            $response = [
                'status' => 200,
                'msg' => 'Card Created Successfully'
            ];
        } else {
            $response = [
                'status' => 500,
                'msg' => 'Something went Wrong'

            ];
        }
        return $this->respond($response);
    }
    // public function push_card_details()
    // {
    //     $SubscriptionCardDetailsModel = new SubscriptionCardDetailsModel();
    //     $request_data = $this->request->getJSON();
    //     // $request_data=json_encode($request_data);
    //     $request_data = json_decode(json_encode($request_data), true);
    //     // print_r($request_data);die();
    //     $card_id = $request_data['card_id'];
    //     $card_details_array = $request_data['card_details_array'];
    //     if (empty($card_id)) {
    //         $response = [
    //             'status' => 0,
    //             'msg' => 'param card_id is missing'

    //         ];
    //     } else if (empty($card_details_array)) {
    //         $response = [
    //             'status' => 0,
    //             'msg' => 'param card_details_array is missing'

    //         ];
    //     } else {
    //         $data = [
    //             'card_id' => $card_id,
    //             'details' => json_encode($card_details_array),
    //             'created_at' => date($this->date_format),
    //             'updated_at' => date($this->date_format),
    //         ];
    //         if ($SubscriptionCardDetailsModel->insert($data)) {
    //             $response = [
    //                 'status' => 1,
    //                 'msg' => 'Card Details Added Successfully'

    //             ];
    //         } else {
    //             $response = [
    //                 'status' => 0,
    //                 'msg' => 'Something went Wrong'

    //             ];
    //         }
    //     }
    //     return $this->respond($response);
    // }
    function delete_card($id)
    {
        $SubscriptionCardsModel = new SubscriptionCardsModel();
        if ($SubscriptionCardsModel->delete($id)) {
            $response = [
                'status' => 200,
                'msg' => 'Card Deleted Successfully'
            ];
        } else {
            $response = [
                'status' => 500,
                'msg' => 'Something went Wrong'

            ];
        }
        return $this->respond($response);
    }
    function update_card($id)
    {
        $SubscriptionCardsModel = new SubscriptionCardsModel();
        $data = $this->request->getVar();
        if ($SubscriptionCardsModel->update($id, $data)) {
            $response = [
                'status' => 200,
                'msg' => 'Card Updated Successfully'
            ];
        } else {
            $response = [
                'status' => 500,
                'msg' => 'Something went Wrong'
            ];
        }
        return $this->respond($response);
    }
    function get_all_cards_byId($id)
    {
        $SubscriptionCardsModel = new SubscriptionCardsModel();
        $card_data = $SubscriptionCardsModel->where("id", $id)->first();
        $response = [
            'status' => 200,
            'data' => $card_data
        ];
        return $this->respond($response);
    }
    // function get_cards_pricing_residential_byId($card_id)
    // {
    //     $SubscriptionCardResidentialPricingModel = new SubscriptionCardResidentialPricingModel();
    //     $card_data = $SubscriptionCardResidentialPricingModel->where("card_id", $card_id)->findAll();
    //     $response = [
    //         'status' => 200,
    //         'data' => $card_data
    //     ];
    //     return $this->respond($response);
    // }
    function get_cards_pricing_commercial_byId($card_id)
    {
        $SubscriptionCardCommercialPricingModel = new SubscriptionCardCommercialPricingModel();
        $card_data = $SubscriptionCardCommercialPricingModel->where("card_id", $card_id)->findAll();
        $response = [
            'status' => 200,
            'data' => $card_data
        ];
        return $this->respond($response);
    }
    function get_cards_pricing_byId($card_id)
    {
        $SubscriptionCardPricingModel = new SubscriptionCardPricingModel();
        $card_data = $SubscriptionCardPricingModel->where("card_id", $card_id)->findAll();
        $response = [
            'status' => 200,
            'data' => $card_data
        ];
        return $this->respond($response);
    }
}
