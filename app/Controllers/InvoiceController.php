<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Controllers\MailingController;
use App\Libraries\InvoiceGenerator;
use App\Models\BookingsModel;
use App\Models\CartModel;
use App\Models\CustomerAddressModel;
use App\Models\CustomerModel;
use App\Models\InvoiceModel;
use App\Models\OrderProductsModel;
use Dompdf\Dompdf;
use Dompdf\Options;

class InvoiceController extends BaseController
{
    public function index()
    {
        $invoice = new InvoiceGenerator();
        $invoice->AddPage();
        // $invoice->SetMargins();
        // 'category_service.service_name AS service', 'category_service.service_rate AS service_rate'])
        $bookingData = [
            'id' => 1,
            'location' => 'Clover Hills, NIBM',
            'customer_id' => 'C101',
            'customer_name' => 'Test',
            'product' => 'Sofa L shape',
            'discounted_price' => 20000,
        ];
        $invoiceData = [
            'amount' => 20000,
            'gst_amount' => 20000 / 100 * 18,
            'total_amount' => 20000 / 100 * 18 + 20000,
        ];
        $tableHeader = array('Sr. No.', 'Service', 'Rate', 'GST', 'Qty', 'Total Amt.');
        $invoicePDF_Data = [
            [$bookingData['service'], $invoiceData['amount'], 1, $invoiceData['gst_amount'], $invoiceData['total_amount']],
        ];
        $invoiceNumber = 'CIC' . str_pad(101, 4, '0', STR_PAD_LEFT);
        $headData = [
            'invoice_number' => $invoiceNumber,
            // 'invoice_amount' => number_format((float)$invoiceData['total_amount'], 2, '.'),
            // 'invoice_amount' => number_format((float)$invoiceData['total_amount'], 2, '.'),
            'invoice_amount' => (float)$invoiceData['total_amount'],
            'invoice_date' => date('Y-m-d'),
            'customer_name' => $bookingData['customer_name'],
            'location' => $bookingData['location'],
        ];
        $invoice->generateInvoice($tableHeader, $invoicePDF_Data, $headData);

        foreach (glob("C:\Users\Admin\Downloads\SampleInvoice*.pdf") as $filename) {
            unlink($filename);
        }
        $invoice->Output('D', 'SampleInvoice.pdf');
    }

    public function makeInvoice_old($headData = array(), $productsData = array())
    {
        $invoice = new InvoiceGenerator();
        $invoice->AddPage();
        $invoice->newInvoice($headData, $productsData);
        // $invoice->newInvoice_temp($headData, $productsData);
        foreach (glob("C:\Users\Admin\Downloads\SampleInvoice*.pdf") as $filename) {
            unlink($filename);
        }
        $path = 'public/invoice/' . $headData['invoice_number'] . '.pdf';
        // $path = 'SampleInvoice.pdf';
        $invoice->Output('F', $path);
        // $invoice->Output('D', $path);
        return $path;
    }

    public function makeInvoice($orderData, &$ordersModel)
    {
        $orderID = $orderData['id'];
        $invoiceModel = new InvoiceModel();
        $validation = &$invoiceModel;
        $invoiceModel->insert(['invoice_number' => '']);
        $invoiceID = $invoiceModel->db->insertID();
        $invoiceNumber = 'DRF' . str_pad($invoiceID, 4, '0', STR_PAD_LEFT);

        // die(var_dump($orderData));
        $ordersModel->set(['invoice_id' => $invoiceID])->where('razorpay_order_id', $orderData['razorpay_order_id'])->update();
        // Invoice
        $customerID = $orderData['customer_id'];
        $subTotal = $orderData['subtotal'];
        $total = $orderData['total'];
        $discount = $orderData['discount'];
        $couponDiscount = $orderData['coupon_discount'];
        // $orderID = $orderData['razorpay_order_id'];
        $customerModel = new CustomerModel();
        $validation = &$customerModel;
        $customerData = $customerModel->find($customerID);
        $customerAddressModel = new CustomerAddressModel();
        $validation = &$customerAddressModel;
        $addressData = $customerAddressModel->find($orderData['address_id']);
        $email = $addressData['email'];
        // $address = $
        $headData = [
            'full_name' => $addressData['first_name'] . ' ' . $addressData['last_name'],
            'address_line1' => $addressData['street_address'],
            'address_line2' => implode(', ', [$addressData['city'], $addressData['state'], $addressData['pincode']]),
            'phone_no' => $addressData['phone'],
            'email_id' => $addressData['email'],
            'invoice_number' => $invoiceNumber,
            'order_id' => $orderData['razorpay_order_id'],
            'invoice_date' => date('Y-m-d'),
            'invoice_time' => date('H:i:s'),
            'customer_id' => $customerID,
            'total' => $total,
            'discount' => $discount,
            'coupon_discount' => $couponDiscount,
            'subtotal' => $subTotal,
            'is_cod' => $orderData['is_cod'],
            'coupon' => $orderData['coupon'],
        ];
        $orderProductsModel = new OrderProductsModel();
        $validation = &$orderProductsModel;
        $invoiceProductsData = $orderProductsModel->select([
            'af_products.name AS title',
            'af_brands.id AS brand_id',
            'af_brands.name AS brand_name',
            'af_products.product_code AS product_code',
            'af_order_products.actual_price AS actual_price',
            'af_order_products.increase_percent AS increase_percent',
            'af_order_products.discounted_percent AS discounted_percent',
            'af_order_products.quantity AS quantity',
        ])->join('af_products', 'af_products.id = af_order_products.product_id')
            ->join('af_brands', 'af_brands.id = af_products.brand_id')
            ->where('af_order_products.order_id', $orderData['id'])->findAll();
        helper('products');
        // print_r($invoiceProductsData);
        $invoiceProductsData = array_map('get_discounted_price', $invoiceProductsData);

        foreach ($invoiceProductsData as &$product) {
            // $product['price'] = $product['actual_price'] - ($product['actual_price'] / 100 * $product['discounted_percent']);
            $product['total'] = $product['actual_price'] * $product['quantity'];
            // unset($product['actual_price'], $product['discounted_percent']);
        }

        $invoice = new InvoiceGenerator();
        $invoice->AddPage();

        $invoice->newInvoice($headData, $invoiceProductsData);
        // $invoice->newInvoice_temp($headData, $productsData);
        foreach (glob("C:\Users\Admin\Downloads\SampleInvoice*.pdf") as $filename) {
            unlink($filename);
        }
        $path = 'public/invoice/' . $headData['invoice_number'] . '.pdf';
        // $path = 'SampleInvoice.pdf';
        $invoice->Output('F', $path);
        $invoicePath = $path;
        // $invoiceController = new InvoiceController();
        // $invoicePath = $invoiceController->makeInvoice($headData, $invoiceProductsData);

        $invoiceModel->set(['invoice_number' => $invoiceNumber, 'invoice_path' => $invoicePath])->update($invoiceID);

        // $emailControler = new EmailController();
        // $email = $emailControler->order_confirmed($orderID, $email);

        $mailingController = new MailingController();
        //    $email =  $mailingController->post_order_mail($orderID);

        // empty Cart
        $cartModel = new CartModel();
        // $cartModel->where('customer_id', $customerID)->delete();


        return $invoicePath;
    }
    public function generateInvoice($bookingId)
    {
        $bookingsModel = new BookingsModel();

        // Fetch booking details with customer name & address
        $booking = $bookingsModel
            ->select('bookings.*, af_customers.name as user_name, af_customers.email as user_email, 
                  customer_addresses.address as customer_address')
            ->join('af_customers', 'af_customers.id = bookings.user_id', 'left')
            ->join('customer_addresses', 'customer_addresses.id = bookings.address_id', 'left')
            ->where('bookings.id', $bookingId)
            ->first();

        if (!$booking) {
            return $this->response->setJSON(['status' => 404, 'message' => 'Booking not found']);
        }

        // Load booking services with service names
        $bookingServicesModel = new \App\Models\BookingServicesModel();
        $services = $bookingServicesModel
            ->select('booking_services.*, services.name as service_name')
            ->join('services', 'services.id = booking_services.service_id', 'left')
            ->where('booking_services.booking_id', $bookingId)
            ->findAll();

        // Prepare data for the PDF
        $data = [
            'booking' => $booking,
            'services' => $services,
        ];

        // Load HTML view for the invoice
        $html = view('invoice_template', $data);

        // Initialize Dompdf with options
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans'); // Supports ₹ symbol

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Output the PDF for download
        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="invoice_' . $bookingId . '.pdf"')
            ->setBody($dompdf->output());
    }
}
