<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\VendorsModel;
use App\Models\DesignerModel;
use App\Models\ProductModel;
use CodeIgniter\API\ResponseTrait;
use Exception;

class ProductDashboardController extends BaseController
{
    use ResponseTrait;
    public function getProductStatics()
    {
        try {
            $db = db_connect();
            $statics = $db->table('af_products')->select('status, COUNT(*) as count')->groupBy('status')->get()->getResultArray();
            // print_r($statics);die;
            $statusArray = array(
                '0' => 'raw_products',
                '1' => 'photoshoot_done',
                '2' => 'designer_assigned',
                '3' => 'editing_started',
                '4' => 'ready',
                '5' => 'live',
            );
            $statics = array_column($statics, 'count', 'status');
            $totalCount = 0;
            foreach ($statusArray as $key => $value) {
                if (isset($statics[$key])) {
                    $statics[$value] = $statics[$key];
                    $totalCount += $statics[$key];
                    unset($statics[$key]);
                } else {
                    $statics[$value] = 0;
                }
            }
            $statics['total_counts'] = $totalCount;
            // $vendorsModel = new VendorsModel();
            $designerModel = new DesignerModel();
            $VendorsModel = new VendorsModel();
            $vendorsCount = $VendorsModel->countAllResults();
            $statics['vendors_count'] = $vendorsCount;
            $statics['designers_count'] = $designerModel->countAllResults();

            $statusCode = 200;
            $response = [
                'data' => $statics,
            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    public function productsStats()
    {
        try {
            // $productsModel = new ProductModel();
            /* $productsStats = $productsModel->select([
                'af_home_zone_appliances.title AS title',
                // 'COUNT(*) AS products_count'
            ])
                ->join('af_home_zone_appliances', 'af_products.home_zone_appliances_id = af_home_zone_appliances.id', 'right')
                ->groupBy('af_products.home_zone_appliances_id')
                ->findAll();
                echo $productsModel->db->getLastQuery(); */
            $status = $this->request->getVar('status');
            $db = db_connect();
            $subquery = $db->table('af_products')
                ->select('home_zone_appliances_id, COUNT(*) AS products_count');
            if (!($status == 'null' || $status == '')) {
                $subquery = $subquery->where('status', $status);
            }
            $subquery = $subquery->groupBy('af_products.home_zone_appliances_id');
            $productsStats = $db->newQuery()
                ->select('af_home_zone_appliances.id AS id, af_home_zone_appliances.title AS title, t1.products_count AS products_count')
                ->fromSubquery($subquery, 't1')->join('af_home_zone_appliances', 'af_home_zone_appliances.id = t1.home_zone_appliances_id', 'right')
                ->get()->getResultArray();
            $statusCode = 200;
            $response = [
                'data' => $productsStats,
            ];
            // print_r($productsStats);
            // die;
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }
}
