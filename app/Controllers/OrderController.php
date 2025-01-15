<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\CouponModel;
use App\Models\CustomerModel;
use App\Models\OrderProductsModel;
use App\Models\OrdersModel;
use App\Models\OrdersTimelineModel;
use App\Models\ProductImageModel;
use App\Models\ProductModel;
use CodeIgniter\API\ResponseTrait;
use Exception;

use function PHPUnit\Framework\isNull;

class OrderController extends BaseController
{
    use ResponseTrait;
    /* public function createOrder()
    {
        try {
            $ordersModel = new OrdersModel();
            $validation = &$ordersModel;
            $statusCode = 200;

            $userBackendToFrontendAttrs = [
                'customer_id' => 'customer_id',
                'product_id' => 'product_id',
                'CustomerAddress_id' => 'CustomerAddress_id',
                'total_amount' => 'total_amount',
                'payment_method' => 'payment_method',
                'status' => 'status',
                'payment_status' => 'payment_status',
            ];

            $orderData = [];
            foreach ($userBackendToFrontendAttrs as $backendAttr => $frontendAttr) {
                $orderData[$backendAttr] = $this->request->getVar($frontendAttr);
            }

            $ordersModel->insert($orderData);



            if ($ordersModel->db->error()['code']) {
                throw new Exception($ordersModel->db->error()['message'], 500);
            }

            if (!empty($ordersModel->errors())) {
                throw new Exception('Validation', 400);
            }

            if ($ordersModel->db->affectedRows() == 1) {
                $statusCode = 200;
                $response = [
                    'message' => 'Order created successfully.',
                    'order_id' => $ordersModel->db->insertID(),
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
    } */

    public function updateOrder($order_id)
    {
        try {
            $ordersModel = new OrdersModel();
            $validation = &$ordersModel;
            $statusCode = 200;

            $userBackendToFrontendAttrs = [
                'customer_id' => 'customer_id',
                'product_id' => 'product_id',
                'CustomerAddress_id' => 'CustomerAddress_id',
                'total_amount' => 'total_amount',
                'payment_method' => 'payment_method',
                'status' => 'status',
                'payment_status' => 'payment_status',

            ];

            $orderData = [];
            foreach ($userBackendToFrontendAttrs as $backendAttr => $frontendAttr) {
                $orderData[$backendAttr] = $this->request->getVar($frontendAttr);
            }

            $ordersModel->update($order_id, $orderData);

            if ($ordersModel->db->error()['code']) {
                throw new Exception($ordersModel->db->error()['message'], 500);
            }

            if (!empty($ordersModel->errors())) {
                throw new Exception('Validation', 400);
            }

            if ($ordersModel->db->affectedRows() == 1) {
                $statusCode = 200;
                $response = [
                    'message' => 'Order updated successfully.',
                    'order_id' => $order_id,
                ];
            } else {
                $statusCode = 404;
                $response = [
                    'error' => 'Order not found.',
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

    public function deleteOrder($order_id)
    {
        try {
            $ordersModel = new OrdersModel();
            $statusCode = 200;

            $ordersModel->delete($order_id);

            if ($ordersModel->db->error()['code']) {
                throw new Exception($ordersModel->db->error()['message'], 500);
            }

            if ($ordersModel->db->affectedRows() == 1) {
                $statusCode = 200;
                $response = [
                    'message' => 'Order deleted successfully.',
                    'order_id' => $order_id,
                ];
            } else {
                $statusCode = 404;
                $response = [
                    'error' => 'Nothing to update',
                ];
            }
        } catch (Exception $e) {
            $statusCode = 500;
            $response = [
                'error' => $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    public function getOrder($customOrderID)
    {
        try {
            $ordersModel = new OrdersModel();
            $statusCode = 200;

            $order = $ordersModel->select([
                'af_orders.id AS id',
                'af_orders.customer_id AS customer_id',
                'af_orders.address_id AS customer_address_id',
                'af_orders.total AS total',
                'af_orders.discount AS discount',
                'af_orders.subtotal AS subtotal',
                'af_orders.razorpay_order_id AS razorpay_order_id',
                'af_orders.payment_status AS payment_status',
                'af_orders.transaction_id AS transaction_id',
                'af_orders.invoice_id AS invoice_id',
                'af_orders.coupon AS coupon',
                'af_orders.is_cod AS is_cod',
                'af_orders.created_at AS order_date',
                'af_orders.status AS status',
                'af_customers.name AS customer_name',
                "CONCAT(af_customer_address.street_address, ', ', af_customer_address.city, ', ', af_customer_address.state, ' - ', af_customer_address.pincode) AS customer_address",
                'af_invoices.invoice_path AS invoice_path',
            ])
                ->join('af_customers', 'af_customers.id = af_orders.customer_id')
                ->join('af_customer_address', 'af_customer_address.id = af_orders.address_id')
                ->join('af_invoices', 'af_invoices.id = af_orders.invoice_id', 'LEFT')
                ->where('af_orders.razorpay_order_id', $customOrderID)
                ->first();
            // ->find($order_id);
            // echo $customOrderID;
            // print_r($order);die;
            if (empty($order)) {
                throw new Exception('No order found!', 404);
            }
            if ($order['coupon']) {
                $subtotal = $order['subtotal'] - $order['discount'];
                $couponModel = new CouponModel();
                $couponDetails = $couponModel->select(['id', 'coupon_code', 'coupon_type_name', 'coupon_type'])->find($order['coupon']);
                switch ($couponDetails['coupon_type']) {
                    case 1:
                        $couponDiscount = ceil($subtotal / 100 * $couponDetails['coupon_type_name']);
                        break;
                    case 2:
                        $couponDiscount = $couponDetails['coupon_type_name'];
                        break;
                }
                $order['coupon_details'] = [
                    'coupon_code' => $couponDetails['coupon_code'],
                    'discount_amount' => $couponDiscount,
                ];
            }
            $order_id = $order['id'];
            $ordersTimelineModel = new OrdersTimelineModel();
            $timelineData = $ordersTimelineModel->where("order_id", $order_id)->findAll();
            $order['timeline'] = $timelineData;

            $productModel = new ProductModel();
            $orderProductsModel = new OrderProductsModel();
            $customerID = $this->request->getVar('id');
            $products = $orderProductsModel->select([
                'af_order_products.product_id AS id',
                'af_order_products.actual_price AS actual_price',
                'af_order_products.increase_percent AS increase_percent',
                'af_order_products.discounted_percent AS discounted_percent',
                'af_order_products.quantity AS quantity',
                'af_products.name AS name',
                'af_products.product_code AS product_code',
                'af_product_images.path_360x360 AS image',
            ])
                ->join('af_products', 'af_products.id = af_order_products.product_id')
                ->join('af_product_images', 'af_product_images.product_id = af_order_products.product_id')
                ->where('af_product_images.image_index', 0)
                ->where('af_order_products.order_id', $order_id)->findAll();

            helper('products');
            $products = array_map('get_discounted_price', $products);

            if ($order) {
                $statusCode = 200;
                $response = [
                    'order' => $order,
                    'product' => $products,
                ];
            } else {
                $statusCode = 404;
                $response = [
                    'error' => 'Order not found.',
                ];
            }
        } catch (Exception $e) {
            $statusCode = 500;
            $response = [
                'error' => $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    public function listOrders($id)
    {
        try {
            $ordersModel = new OrdersModel();
            $productImageModel = new ProductImageModel();
            $productModel = new ProductModel();
            // $customerID = $this->request->getVar('customer_id');

            $validation = &$ordersModel;
            $orders = $ordersModel->where('customer_id', $id)->findAll();

            if ($validation->db->error()['code']) {
                throw new Exception($validation->db->error()['message'], 500);
            }


            $orderProductsModel = new OrderProductsModel();
            $validation = &$orderProductsModel;
            foreach ($orders as &$singleOrder) {
                $singleOrder['products'] = $orderProductsModel->select([
                    'af_order_products.product_id AS id',
                    'af_order_products.actual_price AS actual_price',
                    'af_order_products.discounted_percent AS discounted_percent',
                    'af_order_products.quantity AS quantity',
                    'af_products.name AS name',
                    'af_products.product_code AS product_code',
                ])->join('af_products', 'af_products.id = af_order_products.product_id')->where('af_order_products.order_id', $singleOrder['id'])->findAll();


                foreach ($singleOrder['products'] as &$singleProduct) {
                    $singleProduct['image'] = $productImageModel->where('product_id', $singleProduct['id'])->first();
                    $singleProduct['image'] = $singleProduct['image']['path_360x360'];
                }
            }

            //     $product = $productModel->where('id', $id)->findAll();

            // foreach ($product as $key)
            //     if ($product['discounted_percent'] != '') {
            //         $products[$key]['discounted_price'] = $product['actual_price'] - ($product['actual_price'] / 100 * $product['discounted_percent']);
            //     }

            $statusCode = 200;
            $response = [
                'orders' => $orders,

            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? ['validation' => $validation->errors()] : $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    public function getOrderHistorybycustomer($id)
    {
        try {
            $ordersModel = new OrdersModel();
            // $customerID = $this->request->getVar('customer_id');

            $validation = &$ordersModel;
            $orders = $ordersModel->select(['af_orders.id AS primary_id', 'af_orders.razorpay_order_id AS id`', 'af_orders.total AS total', 'af_orders.status AS status'])->where('customer_id', $id)->orderBy('af_orders.id', 'DESC')->findAll();

            if ($validation->db->error()['code']) {
                throw new Exception($validation->db->error()['message'], 500);
            }


            $orderProductsModel = new OrderProductsModel();
            $validation = &$orderProductsModel;
            $productImageModel = new ProductImageModel();
            foreach ($orders as &$singleOrder) {
                $singleOrder['products'] = $orderProductsModel->select([
                    'af_order_products.product_id AS id',
                    'af_order_products.quantity AS quantity',
                    'af_products.name AS name',
                    // 'af_products.brand AS brand',
                ])->join('af_products', 'af_products.id = af_order_products.product_id')->where('af_order_products.order_id', $singleOrder['primary_id'])->findAll();

                foreach ($singleOrder['products'] as &$singleProduct) {
                    $singleProduct['image'] = $productImageModel->where('product_id', $singleProduct['id'])->first();
                    $singleProduct['image'] = $singleProduct['image']['path_360x360'];
                }
            }

            // echo "<pre>";
            // print_r($orders);

            $statusCode = 200;
            $response = [
                'orders' => $orders,
            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? ['validation' => $validation->errors()] : $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    public function getAllOrder()
    {
        try {
            $ordersModel = new OrdersModel();
            $orderCountQuery = new OrdersModel();
            $customerModel = new CustomerModel();
            $start_date = $this->request->getVar('start_date');
            $end_date = $this->request->getVar('end_date');
            $statusCode = 200;
            $search = $this->request->getVar('search');
            $page = $this->request->getVar('page');
            $category_id = $this->request->getVar('category_id');
            $searchAll = $this->request->getVar('searchAll');


            // $vendorID = $this->request->getVar('vendor_id');
            // echo $order_id;die;
            // $email = $this->request->getVar('email');


            $orders = $ordersModel->select([
                'af_orders.id AS id',
                'af_orders.customer_id AS customer_id',
                'af_orders.address_id AS address_id',
                'af_orders.total AS total',
                'af_orders.subtotal AS subtotal',
                'af_orders.razorpay_order_id AS razorpay_order_id',
                'af_orders.transaction_id AS transaction_id',
                'af_orders.invoice_id AS invoice_id',
                'af_orders.is_cod AS is_cod',
                'af_orders.status AS status',
                'af_orders.payment_status AS payment_status',
                'af_orders.created_at AS created_at',
                'af_customers.name AS customer_name',
                // 'af_customers.email AS email',
                'af_customer_address.email AS email',
            ])
                ->join('af_customers', 'af_customers.id = af_orders.customer_id')
                ->join('af_customer_address', 'af_customer_address.id = af_orders.address_id', 'LEFT');
                
                
                $orderCountQuery = $orderCountQuery->join('af_customers', 'af_customers.id = af_orders.customer_id')
                ->join('af_customer_address', 'af_customer_address.id = af_orders.address_id', 'LEFT');
            if (!($search == null || $search == '')) {
                // echo var_dump($order_id);
                $orders = $orders->like('razorpay_order_id', $search)->orLike('af_customer_address.email', $search);
                $orderCountQuery = $orderCountQuery->like('razorpay_order_id', $search)->orLike('af_customer_address.email', $search);

                // $pageCountQuery = $orders->like('razorpay_order_id', $search)->orLike('email', $search);
            }

            // echo $start_date !==null;
            // die;
            // if (($start_date === 'null' || $start_date === '') && ($end_date === 'null' || $end_date === '')) {
            if (($start_date != 'null') && ($end_date != 'null')) {
                $orders = $orders->where('af_orders.created_at >=', $start_date)->where('af_orders.created_at <=', $end_date)->orLike('af_orders.created_at', $end_date);
                $orderCountQuery = $orderCountQuery->where('af_orders.created_at >=', $start_date)->where('af_orders.created_at <=', $end_date)->orLike('af_orders.created_at', $end_date);
            }
            $ordersCount = $orderCountQuery->countAllResults();
            $orders = $orders->orderBy('id', 'DESC')->paginate(50, 'allOrders', $page);
            // echo $ordersModel->db->getLastQuery();
            // $pageCount = $pageCountQuery->countAllResults();
            // print_r($ordersModel->pager->getDetails('allOrders'));die;
            $pageCount = $ordersModel->pager->getPageCount('allOrders');
            // $ordersCount = $ordersModel->pager->getTotal('allOrders');


            $productModel = new ProductModel();
            $productImageModel = new ProductImageModel();
            $customerID = $this->request->getVar('id');

            $orderProductsModel = new OrderProductsModel();
            $validation = &$orderProductsModel;
            $orderidarray = [];
            $i = 0;
            foreach ($orders as &$singleOrder) {
                if ($category_id) {
                    $orddata = $orderProductsModel->where("order_id", $singleOrder["id"])->first();
                    $prodata = $productModel->where("id", $orddata["product_id"])->first();
                    if ($category_id == $prodata["home_zone_appliances_id"]) {
                        $orderidarray[$i] = $singleOrder["id"];
                        $i++;
                    }
                } else {
                    $orderidarray[$i] = $singleOrder["id"];
                    $i++;
                }
            }
            $OrderDataUser = [];


            foreach ($orderidarray as $key => $ordid) {
                $singleOrder['products'] = $orderProductsModel->select([
                    'af_order_products.product_id AS id',
                    'af_order_products.actual_price AS actual_price',
                    'af_order_products.discounted_percent AS discounted_percent',
                    'af_products.name AS name',
                    'af_products.vendor_name AS vender_name',
                    // 'af_customers.name AS customerName',
                    // 'af_customers.email AS email',
                ])
                    // ->join('af_orders', 'af_orders.customer_id = af_order_products.customer_id')
                    ->join('af_products', 'af_products.id = af_order_products.product_id')
                    // ->join('af_customers', 'af_customers.id = af_order_products.customer_id') 
                    ->where('af_order_products.order_id', $ordid)
                    ->findAll();

                foreach ($singleOrder['products'] as &$singleProduct) {
                    $singleProduct['image'] = $productImageModel->where('product_id', $singleProduct['id'])->first();
                    $singleProduct['image'] = $singleProduct['image']['path_360x360'];
                }
                $OrderDataUser[$key] = $ordersModel->select([
                    'af_orders.id AS primary_id',
                    'af_orders.razorpay_order_id AS id',
                    'af_orders.customer_id AS customer_id',
                    'af_orders.address_id AS address_id',
                    'af_orders.total AS total',
                    'af_orders.subtotal AS subtotal',
                    'af_orders.razorpay_order_id AS razorpay_order_id',
                    'af_orders.transaction_id AS transaction_id',
                    'af_orders.invoice_id AS invoice_id',
                    'af_orders.is_cod AS is_cod',
                    'af_orders.status AS status',
                    'af_orders.payment_status AS payment_status',
                    'af_orders.created_at AS created_at',
                    'af_customer_address.email AS email',
                ])
                ->join('af_customer_address', 'af_customer_address.id = af_orders.address_id', 'LEFT')
                ->where("af_orders.id", $ordid)->first();
                // print_r($OrderDataUser);
                
                $cdata = $customerModel->where("id", $OrderDataUser[$key]["customer_id"])->first();
                // print_r($cdata);
                
                $OrderDataUser[$key]["customer_name"] = $cdata["name"];
                // $OrderDataUser[$key]["email"] = $cdata["email"];
                $OrderDataUser[$key]["Product"] = $singleProduct;
                // die;
            }


            $statusCode = 200;
            $response = [
                'order' => $OrderDataUser,
                'page_count' => $pageCount,
                'total_products_count' => $ordersCount
            ];
        } catch (Exception $e) {
            $statusCode = 500;
            $response = [
                'error' => $e->getMessage(),
                'Line' => $e->getLine()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }


    function getAllOrderByDate()
    {
        try {
            $ordersModel = new OrdersModel();
            $pageCountQuery = new OrdersModel();
            $customerModel = new CustomerModel();
            $statusCode = 200;
            $search = $this->request->getVar('search');
            $page = $this->request->getVar('page');
            $category_id = $this->request->getVar('category_id');
            $start_date = $this->request->getVar('start_date');
            $end_date = $this->request->getVar('end_date');

            // $vendorID = $this->request->getVar('vendor_id');
            // echo $order_id;die;
            // $email = $this->request->getVar('email');



            // Date Formate
            // '2023-08-31' 
            $Date = $this->request->getVar("date");


            $orders = $ordersModel->select([
                'af_orders.id AS id',
                'af_orders.customer_id AS customer_id',
                'af_orders.address_id AS address_id',
                // 'af_orders.total AS total',
                'af_orders.subtotal AS subtotal',
                'af_orders.razorpay_order_id AS razorpay_order_id',
                'af_orders.transaction_id AS transaction_id',
                'af_orders.invoice_id AS invoice_id',
                'af_orders.is_cod AS is_cod',
                'af_orders.status AS status',
                'af_orders.payment_status AS payment_status',
                'af_orders.created_at AS created_at',
                'af_customers.name AS customer_name',
                'af_customers.email AS email',
            ])->join('af_customers', 'af_customers.id = af_orders.customer_id');




            $pageCountQuery = $pageCountQuery->join('af_customers', 'af_customers.id = af_orders.customer_id');
            if (!(is_null($search) || $search === '')) {
                // echo var_dump($order_id);
                $orders = $orders->like('razorpay_order_id', $search)->orLike('email', $search);
                $pageCountQuery = $pageCountQuery->like('razorpay_order_id', $search)->orLike('email', $search);

                // $pageCountQuery = $orders->like('razorpay_order_id', $search)->orLike('email', $search);
            }
            /* if (!(is_null($email) || $email == '')) {
                // echo 2;
                $orders = $orders->like('email', $email);
            } */
            // $orders = $orders->orderBy('id', 'DESC')->findAll();
            // $pageCount = $pageCountQuery->countAllResults();



            if ($start_date !== null && $end_date !== null) {
                $orders = $orders->where('af_orders.created_at >=', $start_date)->where('af_orders.created_at <=', $end_date)->orLike('af_orders.created_at', $end_date)->orderBy('id', 'DESC')->paginate(50, 'allOrders', $page);
            } else {
                $orders = $orders->orderBy('id', 'DESC')->paginate(50, 'allOrders', $page);
            }
            $pageCount = $pageCountQuery->countAllResults();
            $pageCount = ceil($pageCount / 10);


            // $pageCount = ceil($pageCount / 10);
            // echo $ordersModel->db->getLastQuery();



            $productModel = new ProductModel();
            $productImageModel = new ProductImageModel();
            $customerID = $this->request->getVar('id');

            $orderProductsModel = new OrderProductsModel();
            $validation = &$orderProductsModel;
            $orderidarray = [];
            $i = 0;
            foreach ($orders as &$singleOrder) {
                // if ($Date == date("Y-m-d", strtotime($singleOrder["created_at"]))) 
                {
                    if ($category_id) {

                        $orddata = $orderProductsModel->where("order_id", $singleOrder["id"])->first();
                        if ($orddata) {
                            $prodata = $productModel->where("id", $orddata["product_id"])->first();
                            if ($category_id == $prodata["home_zone_appliances_id"]) {
                                $orderidarray[$i] = $singleOrder["id"];
                                $i++;
                            }
                        }
                    } else {
                        $orderidarray[$i] = $singleOrder["id"];
                        $i++;
                    }
                }
            }
            $OrderDataUser = [];



            foreach ($orderidarray as $key => $ordid) {
                $singleOrder['products'] = $orderProductsModel->select([
                    'af_order_products.product_id AS id',
                    'af_order_products.actual_price AS actual_price',
                    'af_order_products.discounted_percent AS discounted_percent',
                    'af_products.name AS name',
                    'af_products.vendor_name AS vender_name',
                    // 'af_customers.name AS customerName',
                    // 'af_customers.email AS email',
                ])
                    // ->join('af_orders', 'af_orders.customer_id = af_order_products.customer_id')
                    ->join('af_products', 'af_products.id = af_order_products.product_id')
                    // ->join('af_customers', 'af_customers.id = af_order_products.customer_id') 
                    ->where('af_order_products.order_id', $ordid)
                    ->findAll();

                foreach ($singleOrder['products'] as &$singleProduct) {
                    $singleProduct['image'] = $productImageModel->where('product_id', $singleProduct['id'])->first();
                    $singleProduct['image'] = $singleProduct['image']['path_128x128'];
                }
                $OrderDataUser[$key] = $ordersModel->select([
                    'af_orders.id AS id',
                    'af_orders.customer_id AS customer_id',
                    'af_orders.address_id AS address_id',
                    // 'af_orders.total AS total',
                    'af_orders.subtotal AS subtotal',
                    'af_orders.razorpay_order_id AS razorpay_order_id',
                    'af_orders.transaction_id AS transaction_id',
                    'af_orders.invoice_id AS invoice_id',
                    'af_orders.is_cod AS is_cod',
                    'af_orders.status AS status',
                    'af_orders.payment_status AS payment_status',
                    'af_orders.created_at AS created_at',
                ])->where("id", $ordid)->first();

                $cdata = $customerModel->where("id", $OrderDataUser[$key]["customer_id"])->first();

                $OrderDataUser[$key]["customer_name"] = $cdata["name"];
                $OrderDataUser[$key]["email"] = $cdata["email"];
                $OrderDataUser[$key]["Product"] = $singleProduct;
            }


            $statusCode = 200;
            $response = [
                'order' => $OrderDataUser,
                'page_count' => $pageCount,
                'total_order_count' => $ordersModel->countAllResults(),
            ];
        } catch (Exception $e) {
            $statusCode = 500;
            $response = [
                'error' => $e->getMessage(),
                'Line' => $e->getLine()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    public function updateOrderStatus()
    {
        try {
            $orderModel = new OrdersModel();
            $orderID = $this->request->getVar('order_id');
            $status = $this->request->getVar("status");
            $orderRecord = $orderModel->find($orderID);

            if (empty($orderRecord)) {
                throw new Exception('No order found!', 500);
            }

            $status = $this->request->getVar('status');
            $orderModel->set(['status' => $status])->update($orderID);

            $ordersTimelineModel = new OrdersTimelineModel();
            $timelineData = [
                "order_id" => $this->request->getVar("order_id"),
                "status" => $this->request->getVar("status"),
                "timeline" => date("d-m-y H:i:s"),
                "remark" => $this->request->getVar("remark"),
            ];
            $ordersTimelineModel->insert($timelineData);

            $orderTimeline = $ordersTimelineModel->where("order_id", $timelineData['order_id'])->findAll();
            $statusCode = 200;
            $response = [
                'message' => 'Order status updated',
                'timeline' => $orderTimeline,
            ];
        } catch (Exception $e) {
            $statusCode = 500;
            $response = [
                'error' => $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    public function trackOrder($custom_order_id)
    {
        try {
            $ordersModel = new OrdersModel();
            $statusCode = 200;

            $order = $ordersModel->select([
                'af_orders.id AS id',
                'af_orders.customer_id AS customer_id',
                'af_orders.address_id AS customer_address_id',
                'af_orders.total AS subtotal',
                'af_orders.discount AS discount',
                'af_orders.subtotal AS total',
                'af_orders.razorpay_order_id AS razorpay_order_id',
                'af_orders.payment_status AS payment_status',
                'af_orders.transaction_id AS transaction_id',
                'af_orders.invoice_id AS invoice_id',
                'af_orders.coupon AS coupon',
                'af_orders.is_cod AS is_cod',
                'af_orders.created_at AS order_date',
                'af_orders.status AS status',
                'af_customers.name AS customer_name',
                "CONCAT(af_customer_address.street_address, ', ', af_customer_address.city, ', ', af_customer_address.state, ' - ', af_customer_address.pincode) AS customer_address",
                'af_invoices.invoice_path AS invoice_path',
            ])
                ->join('af_customers', 'af_customers.id = af_orders.customer_id')
                ->join('af_customer_address', 'af_customer_address.id = af_orders.address_id')
                ->join('af_invoices', 'af_invoices.id = af_orders.invoice_id')
                ->where('af_orders.razorpay_order_id', $custom_order_id)
                ->first();
            if (empty($order)) {
                throw new Exception('No order found!', 404);
            }
            $order_id = $order['id'];
            $ordersTimelineModel = new OrdersTimelineModel();
            $timelineData = $ordersTimelineModel->where("order_id", $order_id)->findAll();
            $order['timeline'] = $timelineData;

            $productModel = new ProductModel();
            $orderProductsModel = new OrderProductsModel();
            $customerID = $this->request->getVar('id');
            $products = $orderProductsModel->select([
                'af_order_products.product_id AS id',
                'af_order_products.actual_price AS actual_price',
                'af_order_products.discounted_percent AS discounted_percent',
                'af_order_products.quantity AS quantity',
                'af_products.name AS name',
                'af_products.product_code AS product_code',
                'af_product_images.path_360x360 AS image',
            ])
                ->join('af_products', 'af_products.id = af_order_products.product_id')
                ->join('af_product_images', 'af_product_images.product_id = af_order_products.product_id')
                ->where('af_product_images.image_index', 0)
                ->where('af_order_products.order_id', $order_id)->findAll();


            if ($order) {
                $statusCode = 200;
                $response = [
                    'order' => $order,
                    'product' => $products,
                ];
            } else {
                $statusCode = 404;
                $response = [
                    'error' => 'Order not found.',
                ];
            }
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 404 ? 404 : 500;
            $response = [
                'error' => $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    public function cancelOrder()
    {
        $orderID = $this->request->getVar('order_id');
        $remark = $this->request->getVar('remark');
        $statusCode = 200;
        if (empty($orderID)) {
            $response['msg'] = "param order_id cannot be empty";
        } else if (empty($remark)) {
            $response['msg'] = "param remark cannot be empty";
        } else {
            try {
                $orderModel = new OrdersModel();
                $OrdersTimelineModel = new OrdersTimelineModel();
                $current_order_status = $orderModel->where('id', $orderID)->first();
                if ($current_order_status['status'] > 1) {
                    $response['msg'] = "Order Cannot be Cancelled Now as its already dispatched";
                } else {
                    $update_order_status = -1;

                    if ($orderModel->update($orderID, ['status' => $update_order_status])) {
                        $timeline_data = [
                            'order_id' => $orderID,
                            'status' => $update_order_status,
                            'timeline' => date("Y-m-d H:i:s"),
                            'remark' => $remark,
                        ];
                        $OrdersTimelineModel->insert($timeline_data);
                    }

                    $response['msg'] = "Order Cancelled Successfully";

                    $mailingController = new MailingController();
                    $mailingController->post_order_mail($orderID);
                }
            } catch (Exception $e) {
                $statusCode = 500;
                $response = [
                    'error' => $e->getMessage()
                ];
            }
        }
        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }
}
