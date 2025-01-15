<?php

namespace App\Libraries;

use App\Models\CouponModel;
use FPDF;

class InvoiceGenerator extends FPDF
{
    public function generateInvoice($headData, $products)
    {
        $this->SetFont('Arial', '', 8);
        // $y = $this->GetPageHeight();
        // $x = $this->GetPageWidth();
        // First Row
        $x = $this->GetX();
        $this->SetX($x + 2);
        $this->Cell(60, 5, "From", 0, 0, 0, 0);
        $this->Cell(60, 5, "To", 0, 0, 0, 0);
        $this->SetFont('Arial', 'b', 8);
        $this->Cell(60, 5, "Invoice: " . $headData['invoice_number'], 0, 0, 0, 0);
        $this->Ln();
        // Second Row
        $x = $this->GetX();
        $this->SetX($x + 2);
        $this->SetFont('Arial', 'b', 8);
        $this->Cell(60, 5, "Haps Pro Pvt Ltd", 0, 0, 0, 0);
        $this->Cell(60, 5, $headData['full_name'], 0, 0, 0, 0);
        $this->Cell(60, 5, "", 0, 0, 0, 0);
        $this->Ln();
        // Third Row
        $x = $this->GetX();
        $this->SetX($x + 2);
        $this->SetFont('Arial', '', 8);
        $this->Cell(60, 5, '724, Clover Hills, NIBM', 0, 0, 0, 0);
        $this->Cell(60, 5, $headData['address_line1'], 0, 0, 0, 0);
        $this->SetFont('Arial', 'b', 8);
        $this->Cell(13, 5, "Order ID:", 0, 0, 0, 0);
        $this->SetFont('Arial', '', 8);
        $this->Cell(47, 5, $headData['order_id'], 0, 0, 0, 0);
        $this->Ln();
        // Fourth Row
        $x = $this->GetX();
        $this->SetX($x + 2);
        $this->SetFont('Arial', '', 8);
        $this->Cell(60, 5, 'Pune, 411048', 0, 0, 0, 0);
        $this->Cell(60, 5, $headData['address_line2'], 0, 0, 0, 0);
        $this->SetFont('Arial', 'b', 8);
        $this->Cell(20, 5, "Invoice Date:", 0, 0, 0, 0);
        $this->SetFont('Arial', '', 8);
        $this->Cell(40, 5, $headData['invoice_date'], 0, 0, 0, 0);
        $this->Ln();
        // Fifth Row
        $x = $this->GetX();
        $this->SetX($x + 2);
        $this->SetFont('Arial', '', 8);
        $this->Cell(60, 5, 'Phone: 1 (804) 123-9876', 0, 0, 0, 0);
        $this->Cell(60, 5, 'Phone: ' . $headData['phone_no'], 0, 0, 0, 0);
        $this->SetFont('Arial', 'b', 8);
        $this->Cell(25, 5, "Payment Method: ", 0, 0, 0, 0);
        $this->SetFont('Arial', '', 8);
        $this->Cell(13, 5, "UPI", 0, 0, 0, 0);
        $this->Ln();
        // Sixth Row
        $x = $this->GetX();
        $this->SetX($x + 2);
        $this->SetFont('Arial', '', 8);
        $this->Cell(60, 5, 'Email: info@hapspro.com', 0, 0, 0, 0);
        $this->Cell(60, 5, 'Email: ' . $headData['email_id'], 0, 0, 0, 0);
        $this->Cell(60, 5, "", 0, 0, 0, 0);
        $this->Ln(10);
        // Table
        $this->SetFont('Arial', 'b', 8);
        $x = $this->GetX();
        $this->SetX($x + 2);
        $this->Cell(10, 7, 'Sr.', 'BT', 0, 0, 0);
        $this->Cell(60, 7, 'Product', 'BT', 0, 0, 0);
        $this->Cell(20, 7, 'Code', 'BT', 0, 0, 0);
        $this->Cell(35, 7, 'Description', 'BT', 0, 0, 0);
        $this->Cell(20, 7, 'Qty.', 'BT', 0, 0, 0);
        $this->Cell(20, 7, 'Price', 'BT', 0, 0, 0);
        $this->Cell(20, 7, 'Total', 'BT', 0, 0, 0);
        $this->Ln();
        $this->SetFont('Arial', '', 8);

        for ($i = 0; $i < count($products); $i++) {
            $x = $this->GetX();
            $y = $this->GetY();
            $this->SetXY($x + 2, $y + 2);
            // $this->SetX($x + 2);
            $product = $products[$i];
            $title = str_split($product['title'], 43);
            $this->Cell(10, 4, $i + 1, 0, 0, 0, 0);
            $this->Cell(60, 4, $title[0], 0, 0, 0, 0);
            $this->Cell(20, 4, $product['product_code'], 0, 0, 0, 0);
            $this->Cell(35, 4, $product['brand'], 0, 0, 0, 0);
            $this->Cell(20, 4, $product['quantity'], 0, 0, 0, 0);
            $this->Cell(20, 4, $product['price'], 0, 0, 0, 0);
            $this->Cell(20, 4, $product['total'], 0, 0, 0, 0);
            $this->Ln();

            for ($j = 1; $j < count($title); $j++) {
                $x = $this->GetX();
                $this->SetX($x + 2);
                $this->Cell(10, 4, '', 0, 0, 0, 0);
                $this->Cell(60, 4, $title[$j], 0, 0, 0, 0);
                $this->Cell(20, 4, '', 0, 0, 0, 0);
                $this->Cell(35, 4, '', 0, 0, 0, 0);
                $this->Cell(20, 4, '', 0, 0, 0, 0);
                $this->Cell(20, 4, '', 0, 0, 0, 0);
                $this->Cell(20, 4, '', 0, 0, 0, 0);
                $this->Ln();
            }
            $x = $this->GetX();
            $this->SetX($x + 2);
            if ($i < count($products) - 1)
                $this->Cell(185, 1, '', 'B', 0, 0, 0);
            $this->Ln();
        }

        // Table Complete
        $x = $this->GetX();
        $y = $this->GetY();
        $this->SetXY($x + 2, $y + 30);
        $this->SetFont('Arial', 'b', 12);
        $this->Cell(92.5, 7, 'Payment Status:', 0, 0, 0, 0);
        $this->Cell(92.5, 7, '', 'B', 0, 0, 0);
        $this->Ln();
        $stampX = $x = $this->GetX();
        $stampY = $this->GetY();

        if ($headData['is_cod']) {
            $this->Image(base_url('/unpaid.png'), $stampX + 5, $stampY, 50, 50);
        } else {

            $this->Image(base_url('/paid.png'), $stampX + 5, $stampY, 50, 50);
        }

        $this->SetX($x + 2);
        $this->Cell(92.5, 7, '', 0, 0, 0, 0);
        $this->SetFont('Arial', 'b', 8);
        $this->Cell(46.5, 7, 'Subtotal:', 'B', 0, 0, 0);
        $this->SetFont('Arial', '', 8);
        $this->Cell(46, 7, '$' . $headData['subtotal'], 'B', 0, 0, 0);
        $this->Ln();
        $x = $this->GetX();
        $this->SetX($x + 2);
        $this->Cell(92.5, 7, '', 0, 0, 0, 0);
        $this->SetFont('Arial', 'b', 8);
        $this->Cell(46.5, 7, 'CGST (9%)', 'B', 0, 0, 0);
        $this->SetFont('Arial', '', 8);
        $gst = $headData['subtotal'] / 100 * 9;
        $this->Cell(46, 7, '$' . $gst, 'B', 0, 0, 0);
        $this->Ln();
        $x = $this->GetX();
        $this->SetX($x + 2);
        $this->Cell(92.5, 7, '', 0, 0, 0, 0);
        $this->SetFont('Arial', 'b', 8);
        $this->Cell(46.5, 7, 'SGST (9%)', 'B', 0, 0, 0);
        $this->SetFont('Arial', '', 8);
        $this->Cell(46, 7, '$' . $gst, 'B', 0, 0, 0);
        $this->Ln();
        $x = $this->GetX();
        $this->SetX($x + 2);
        $this->Cell(92.5, 7, '', 0, 0, 0, 0);
        $this->SetFont('Arial', 'b', 8);
        $this->Cell(46.5, 7, 'Shipping:', 'B', 0, 0, 0);
        $this->SetFont('Arial', '', 8);
        $this->Cell(46, 7, '$500', 'B', 0, 0, 0);
        $this->Ln();
        $x = $this->GetX();
        $this->SetX($x + 2);
        $this->Cell(92.5, 7, '', 0, 0, 0, 0);
        $this->SetFont('Arial', 'b', 8);
        $this->Cell(46.5, 7, 'Total:', 0, 0, 0, 0);
        $this->SetFont('Arial', '', 8);
        $total = $headData['subtotal'] + ($gst * 2) + 500;
        $this->Cell(46, 7, '$' . $total, 0, 0, 0, 0);
        $this->Ln();
    }

    public function generateInvoice_backup()
    {
        $this->SetFont('Arial', '', 8);
        // $y = $this->GetPageHeight();
        // $x = $this->GetPageWidth();
        // First Row
        $x = $this->GetX();
        $this->SetX($x + 2);
        $this->Cell(60, 5, "From", 0, 0, 0, 0);
        $this->Cell(60, 5, "To", 0, 0, 0, 0);
        $this->SetFont('Arial', 'b', 8);
        $this->Cell(60, 5, "Invoice #007612", 0, 0, 0, 0);
        $this->Ln();
        // Second Row
        $x = $this->GetX();
        $this->SetX($x + 2);
        $this->SetFont('Arial', 'b', 8);
        $this->Cell(60, 5, "Iron Admin, Inc", 0, 0, 0, 0);
        $this->Cell(60, 5, "Iron Admin, Inc", 0, 0, 0, 0);
        $this->Cell(60, 5, "", 0, 0, 0, 0);
        $this->Ln();
        // Third Row
        $x = $this->GetX();
        $this->SetX($x + 2);
        $this->SetFont('Arial', '', 8);
        $this->Cell(60, 5, '795 Freedom Ave, Suite 600', 0, 0, 0, 0);
        $this->Cell(60, 5, '795 Freedom Ave, Suite 600', 0, 0, 0, 0);
        $this->SetFont('Arial', 'b', 8);
        $this->Cell(13, 5, "Order ID:", 0, 0, 0, 0);
        $this->SetFont('Arial', '', 8);
        $this->Cell(47, 5, "4F3S8J", 0, 0, 0, 0);
        $this->Ln();
        // Fourth Row
        $x = $this->GetX();
        $this->SetX($x + 2);
        $this->SetFont('Arial', '', 8);
        $this->Cell(60, 5, 'New York, CA 94107', 0, 0, 0, 0);
        $this->Cell(60, 5, 'New York, CA 94107', 0, 0, 0, 0);
        $this->SetFont('Arial', 'b', 8);
        $this->Cell(20, 5, "Payment Due:", 0, 0, 0, 0);
        $this->SetFont('Arial', '', 8);
        $this->Cell(40, 5, "2/22/2014", 0, 0, 0, 0);
        $this->Ln();
        // Fifth Row
        $x = $this->GetX();
        $this->SetX($x + 2);
        $this->SetFont('Arial', '', 8);
        $this->Cell(60, 5, 'Phone: 1 (804) 123-9876', 0, 0, 0, 0);
        $this->Cell(60, 5, 'Phone: 1 (804) 123-9876', 0, 0, 0, 0);
        $this->SetFont('Arial', 'b', 8);
        $this->Cell(13, 5, "Account:", 0, 0, 0, 0);
        $this->SetFont('Arial', '', 8);
        $this->Cell(47, 5, "968-34567", 0, 0, 0, 0);
        $this->Ln();
        // Sixth Row
        $x = $this->GetX();
        $this->SetX($x + 2);
        $this->SetFont('Arial', '', 8);
        $this->Cell(60, 5, 'Email: ironadmin.com', 0, 0, 0, 0);
        $this->Cell(60, 5, 'Email: ironadmin.com', 0, 0, 0, 0);
        $this->Cell(60, 5, "", 0, 0, 0, 0);
        $this->Ln(10);
        // Table
        $this->SetFont('Arial', 'b', 8);
        $x = $this->GetX();
        $this->SetX($x + 2);
        $this->Cell(10, 7, 'Qty', 'BT', 0, 0, 0);
        $this->Cell(35, 7, 'Product', 'BT', 0, 0, 0);
        $this->Cell(30, 7, 'Serial #', 'BT', 0, 0, 0);
        $this->Cell(80, 7, 'Description', 'BT', 0, 0, 0);
        $this->Cell(30, 7, 'Subtotal', 'BT', 0, 0, 0);
        $this->Ln();
        $this->SetFont('Arial', '', 8);
        $data = array(
            [
                'Call of Duty', '455-981-221',
                'El snort testosterone trophy driving gloves handsome gerry Richardson helvetica tousled street art master testosterone trophy driving gloves handsome gerry Richardson',
                '$64.50'
            ],
            [
                'Call of Duty', '455-981-221',
                'El snort testosterone trophy driving gloves handsome gerry Richardson helvetica tousled street art master testosterone trophy driving gloves handsome gerry Richardson',
                '$64.50'
            ],
            [
                'Call of Duty', '455-981-221',
                'El snort testosterone trophy driving gloves handsome gerry Richardson helvetica tousled street art master testosterone trophy driving gloves handsome gerry Richardson',
                '$64.50'
            ],
            [
                'Call of Duty', '455-981-221',
                'El snort testosterone trophy driving gloves handsome gerry Richardson helvetica tousled street art master testosterone trophy driving gloves handsome gerry Richardson',
                '$64.50'
            ],
        );
        for ($i = 0; $i < count($data); $i++) {
            $x = $this->GetX();
            $y = $this->GetY();
            $this->SetXY($x + 2, $y + 2);
            // $this->SetX($x + 2);
            $product = $data[$i];
            $description = str_split($product[2], 59);
            $this->Cell(10, 4, $i + 1, 0, 0, 0, 0);
            $this->Cell(35, 4, $product[0], 0, 0, 0, 0);
            $this->Cell(30, 4, $product[1], 0, 0, 0, 0);
            $this->Cell(80, 4, $description[0], 0, 0, 0, 0);
            $this->Cell(30, 4, $product[3], 0, 0, 0, 0);
            $this->Ln();
            for ($j = 1; $j < count($description); $j++) {
                $x = $this->GetX();
                $this->SetX($x + 2);
                $this->Cell(10, 4, '', 0, 0, 0, 0);
                $this->Cell(35, 4, '', 0, 0, 0, 0);
                $this->Cell(30, 4, '', 0, 0, 0, 0);
                $this->Cell(80, 4, $description[$j], 0, 0, 0, 0);
                $this->Cell(30, 4, '', 0, 0, 0, 0);
                $this->Ln();
            }
            $x = $this->GetX();
            $this->SetX($x + 2);
            if ($i < count($data) - 1)
                $this->Cell(185, 1, '', 'B', 0, 0, 0);
            $this->Ln();
        }

        // Table Complete
        $x = $this->GetX();
        $y = $this->GetY();
        $this->SetXY($x + 2, $y + 5);
        $this->SetFont('Arial', 'b', 12);
        $this->Cell(92.5, 7, 'Payment Methods:', 0, 0, 0, 0);
        $this->Cell(92.5, 7, 'Amount Due 2/22/2014', 'B', 0, 0, 0);
        $this->Ln();
        $x = $this->GetX();
        $this->SetX($x + 2);
        $this->Cell(92.5, 7, '', 0, 0, 0, 0);
        $this->SetFont('Arial', 'b', 8);
        $this->Cell(46.5, 7, 'Subtotal:', 'B', 0, 0, 0);
        $this->SetFont('Arial', '', 8);
        $this->Cell(46, 7, '$250.30', 'B', 0, 0, 0);
        $this->Ln();
        $x = $this->GetX();
        $this->SetX($x + 2);
        $this->Cell(92.5, 7, '', 0, 0, 0, 0);
        $this->SetFont('Arial', 'b', 8);
        $this->Cell(46.5, 7, 'CGST (9%)', 'B', 0, 0, 0);
        $this->SetFont('Arial', '', 8);
        $this->Cell(46, 7, '$10.34', 'B', 0, 0, 0);
        $this->Ln();
        $x = $this->GetX();
        $this->SetX($x + 2);
        $this->Cell(92.5, 7, '', 0, 0, 0, 0);
        $this->SetFont('Arial', 'b', 8);
        $this->Cell(46.5, 7, 'SGST (9%)', 'B', 0, 0, 0);
        $this->SetFont('Arial', '', 8);
        $this->Cell(46, 7, '$10.34', 'B', 0, 0, 0);
        $this->Ln();
        $x = $this->GetX();
        $this->SetX($x + 2);
        $this->Cell(92.5, 7, '', 0, 0, 0, 0);
        $this->SetFont('Arial', 'b', 8);
        $this->Cell(46.5, 7, 'Shipping:', 'B', 0, 0, 0);
        $this->SetFont('Arial', '', 8);
        $this->Cell(46, 7, '$5.80', 'B', 0, 0, 0);
        $this->Ln();
        $x = $this->GetX();
        $this->SetX($x + 2);
        $this->Cell(92.5, 7, '', 0, 0, 0, 0);
        $this->SetFont('Arial', 'b', 8);
        $this->Cell(46.5, 7, 'Total:', 0, 0, 0, 0);
        $this->SetFont('Arial', '', 8);
        $this->Cell(46, 7, '$265', 0, 0, 0, 0);
        $this->Ln();

        // $this->Line();
        // $this->Cell(60, 5, $this->MultiCell(55, 5, '795 Freedom Ave, Suite 600, New York, CA 94107', 0, 0, 0), 0, 0, 0, 0);
        // $this->MultiCell(60, 5, '795 Freedom Ave, Suite 600, New York, CA 94107', 0, 0, 0);
        // $this->Cell(60, 5, "", 0, 0, 0, 0);


        // $this->Cell(170, 5, "", 0, 0, 'L');
        // $this->cell(0, 5, 'Original Copy', 'B', 1, 'R');
        // $this->SetFont('Arial', 'B', 12);
        // $this->Cell(0, 7, "Invoice Number: $headData[invoice_number]", 0, 1, 'R');
        // $this->Cell(130, 7, "", 0, 0, 'L');
        // $totalInvoiceAmountPos_X = $this->GetX();
        // $totalInvoiceAmountPos_Y = $this->GetY();
        // $this->SetFillColor(201, 80, 1);
        // $this->SetTextColor(255, 255, 255);
        // $this->Cell(0, 7, "", 0, 1, 'R', true);
        // $this->SetFillColor(255);
        // $this->SetTextColor(0);
        // $this->Cell(0, 7, "Date: $headData[invoice_date]", 0, 1, 'R');
        // $this->Ln();
        // $this->SetFont('Arial', 'B', 14);
        // $this->Cell(25, 8, 'Name: ', 0, 0);
        // $this->SetFont('Arial', '', 14);
        // $this->Cell(0, 8, $headData['customer_name'], 0, 1);
        // $this->SetFont('Arial', 'B', 14);
        // $this->Cell(25, 8, 'Address: ', 0, 0);
        // $this->SetFont('Arial', '', 14);
        // $this->MultiCell(0, 8, $headData['location'], 0, 1);
        // $this->Ln();
        // $this->SetFillColor(201, 80, 1);
        // $this->SetTextColor(255, 255, 255);
        // $this->SetFont('Arial', 'B', 12);
        // $this->Cell(10, 7, '#', 0, 0, 'L', true);
        // $this->Cell(100, 7, 'Service', 0, 0, 'L', true);
        // $this->Cell(25, 7, 'Rate', 0, 0, 'L', true);
        // $this->Cell(10, 7, 'Qty', 0, 0, 'L', true);
        // $this->Cell(25, 7, 'GST(18%)', 0, 0, 'L', true);
        // $this->Cell(0, 7, 'Amount', 0, 0, 'L', true);
        // $this->Ln();

        // $this->SetFillColor(255);
        // $this->SetTextColor(0);
        // $this->SetFont('Arial', '', 12);
        // $total = $totalAmount = $totalQuantity =  $totalGST = 0;
        // for ($i = 0; $i < count($data); $i++) {
        //     $this->Cell(10, 7, $i + 1, 0, 0, 'L', true);
        //     // $this->Cell(100, 7, $data[$i][0], 1, 0, 'L');
        //     $service = str_split($data[$i][0], 47);
        //     $this->Cell(100, 7, $service[0], 0, 0, 'L');
        //     $this->Cell(25, 7, $data[$i][1], 0, 0, 'L');
        //     $this->Cell(10, 7, $data[$i][2], 0, 0, 'L');
        //     $this->Cell(25, 7, $data[$i][3], 0, 0, 'L');
        //     $this->Cell(0, 7, $data[$i][4], 0, 0, 'L');
        //     $this->Ln();
        //     for ($j = 1; $j < count($service); $j++) {
        //         $this->Cell(10, 7, '', 0, 0, 'L', true);
        //         $this->Cell(100, 7, $service[$j], 0, 0, 'L');
        //         $this->Cell(0, 7, '', 0, 0, 'L');
        //         $this->Ln();
        //     }
        //     // $this->SetY($this->GetY() + 30.0);
        //     $this->Cell(0, 1, '--------------------------------------------------------------------------------------------------------------------------------------', 0, 0, 'L');
        //     $this->Ln();
        //     $totalAmount += $data[$i][1];
        //     $totalQuantity += $data[$i][2];
        //     $totalGST += $data[$i][3];
        //     $total += $data[$i][4];
        // }
        // // $this->Write(5, 'Visasdfasdfsdfsdffasfsfit ');
        // // $this->Cell(10, 7, '', 0, 0, 'L', true);
        // $totalAmount = number_format((float)$totalAmount, 2, '.');
        // // $totalQuantity = number_format((float)$totalQuantity, 2, '.');
        // $totalGST = number_format((float)$totalGST, 2, '.');
        // $total = number_format((float)$total, 2, '.');
        // $this->SetFont('Arial', 'B', 12);
        // $this->Cell(110, 7, 'Total =', 0, 0, 'R');
        // $this->Cell(25, 7, $totalAmount, 0, 0, 'L');
        // $this->Cell(10, 7, $totalQuantity, 0, 0, 'L');
        // $this->Cell(25, 7, $totalGST, 0, 0, 'L');
        // $this->Cell(0, 7, $total, 0, 0, 'L');
        // $current_X = $this->GetX();
        // $current_Y = $this->GetY();
        // $this->SetXY($totalInvoiceAmountPos_X, $totalInvoiceAmountPos_Y);
        // $this->SetFillColor(201, 80, 1);
        // $this->SetTextColor(255, 255, 255);
        // $this->Cell(0, 7, "Invoice Amount: Rs. $total", 0, 1, 'R', true);
        // $this->SetXY($current_X, $current_Y);
    }

    public function custome_fonts()
    {
        $this->AddFont('InriaSerif', 'R', 'InriaSerif-Regular.php');
        $this->AddFont('Poppins', 'R', 'Poppins-Regular.php');
        $this->AddFont('Poppins', 'B', 'Poppins-Bold.php');
    }

    // New PDF
    public function newInvoice($headData, $productsData)
    {
        // echo '<pre>';
        // print_r($headData);
        // // // print_r($productsData);
        // die;
        // $products = [
        //     [
        //         'Sofa Cum Bed with double bouncing ', 2, 12500, 37500,
        //     ],
        //     [
        //         'Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum ', 2, 12500, 37500,
        //     ],
        // ];
        $cellWidth = $this->pxTomm(267);
        $cellHeight = $this->pxTomm(21);
        // Line 1
        // Left Section
        $this->SetXY(
            $this->pxTomm(29),
            $this->pxTomm(115)
        );
        $this->SetFont('Poppins', 'R', 14);
        $this->Cell(
            $cellWidth,
            $cellHeight,
            'To,',
            0,
            2
        );
        $this->SetFont('Poppins', 'B', 14);
        $this->MultiCell(
            $cellWidth,
            $cellHeight,
            $headData['full_name'],
        );
        $this->SetX($this->pxTomm(29));
        $this->SetFont('Poppins', 'R', 14);
        $this->MultiCell(
            $cellWidth,
            $cellHeight,
            $headData['address_line1'],

        );
        $this->SetX($this->pxTomm(29));
        $this->SetFont('Poppins', 'R', 14);
        $this->MultiCell(
            $cellWidth,
            $cellHeight,
            $headData['address_line2'],
        );
        $this->SetX($this->pxTomm(29));
        $this->MultiCell(
            $cellWidth,
            $cellHeight,
            'Email : ' . $headData['email_id'],

        );
        $this->SetX($this->pxTomm(29));
        $this->MultiCell(
            $cellWidth,
            $cellHeight,
            'Phone No. : ' . $headData['phone_no'],
        );
        // End of Left Section
        // Right Section
        $x = 354;
        $this->SetXY(
            $this->pxTomm($x),
            $this->pxTomm(115)
        );
        $cellWidth = $cellWidth - $this->pxTomm(354 - 298.5);
        $this->SetFont('Poppins', 'B', 14);
        $this->Cell(
            $cellWidth,
            $cellHeight,
            'Invoice :' . $headData['invoice_number'],
            0,
            2
        );
        $this->Ln();
        $this->SetX($this->pxTomm($x));
        $this->SetFont('Poppins', 'B', 14);
        $this->Cell(
            $this->pxTomm(72),
            $cellHeight,
            'Order ID : '
        );
        $this->SetFont('Poppins', 'R', 14);
        $this->Cell(
            $cellWidth - $this->pxTomm(72),
            $cellHeight,
            $headData['order_id'],
            0,
            1
        );
        $this->SetX($this->pxTomm($x));
        $this->SetFont('Poppins', 'R', 14);
        $this->Cell(
            $cellWidth,
            $cellHeight,
            'Date : ' . $headData['invoice_date'],
            0,
            2
        );
        $this->SetFont('Poppins', 'R', 14);
        $this->Cell(
            $cellWidth,
            $cellHeight,
            'Time : ' . $headData['invoice_time'],
            0,
            2
        );
        // $this->Ln();
        // $this->SetX($this->pxTomm(298.5));
        if ($headData['is_cod']) {
            $paymentMethod = 'COD';
        } else {

            $paymentMethod = 'UPI';
        }
        $this->SetFont('Poppins', 'B', 14);
        $this->Cell(
            $cellWidth,
            $cellHeight,
            'Payment Method : ' . $paymentMethod,
            0,
            2
        );
        // Right section end
        // Table begins
        $this->SetFont('Poppins', 'R', 12);
        $this->SetY($this->pxTomm(350));
        $cellWidth = $this->pxTomm(566);
        $cellHeight = $this->pxTomm(30);
        $this->Cell(
            $this->pxTomm(44),
            $cellHeight,
            'Sr.no',
            'B',
            0,
            'C'
        );
        $this->Cell(
            $this->pxTomm(295),
            $cellHeight,
            'Description',
            'B',
            0,
            'C'
        );
        $this->Cell(
            $this->pxTomm(54),
            $cellHeight,
            'Qty.',
            'B',
            0,
            'C'
        );
        $this->Cell(
            $this->pxTomm(85),
            $cellHeight,
            'Rate',
            'B',
            0,
            'C'
        );
        $this->Cell(
            $this->pxTomm(88),
            $cellHeight,
            'Amount',
            'B',
            0,
            'C'
        );
        $this->Ln();
        // Products
        foreach ($productsData as $key => $single_product) {
            $description = str_split($single_product['title'], 45);
            if (count($description) > 1) {
                $border = '';
                $cellHeight = $this->pxTomm(18);
            } else {
                $border = 'B';
                $cellHeight = $this->pxTomm(30);
            }
            $this->Cell(
                $this->pxTomm(44),
                $cellHeight,
                $key + 1,
                $border . '',
                0,
                'C'
            );
            $this->Cell(
                $this->pxTomm(295),
                $cellHeight,
                $description[0],
                $border,
                0,
                'L'
            );
            $this->Cell(
                $this->pxTomm(54),
                $cellHeight,
                $single_product['quantity'],
                $border,
                0,
                'C'
            );
            $this->Cell(
                $this->pxTomm(85),
                $cellHeight,
                // $single_product['price'],
                $single_product['actual_price'],
                $border,
                0,
                'C'
            );
            $this->Cell(
                $this->pxTomm(88),
                $cellHeight,
                $single_product['total'],
                $border . '',
                0,
                'C'
            );

            for ($i = 1; $i < count($description); $i++) {
                $this->Ln();
                $border = ($i < count($description) - 1) ? '' : 'B';
                $this->Cell(
                    $this->pxTomm(44),
                    $cellHeight,
                    '',
                    $border . '',
                    0,
                    'C'
                );
                $this->Cell(
                    $this->pxTomm(295),
                    $cellHeight,
                    $description[$i],
                    $border,
                    0,
                    'L'
                );
                $this->Cell(
                    $this->pxTomm(54),
                    $cellHeight,
                    '',
                    $border,
                    0,
                    'C'
                );
                $this->Cell(
                    $this->pxTomm(85),
                    $cellHeight,
                    '',
                    $border,
                    0,
                    'C'
                );
                $this->Cell(
                    $this->pxTomm(88),
                    $cellHeight,
                    '',
                    $border . '',
                    0,
                    'C'
                );
            }
            $this->Ln();
        }
        // Table End
        $y = $this->GetY();
        $this->SetY($y + $this->pxTomm(30));
        $cellWidth = $this->pxTomm(267);
        $cellHeight = $this->pxTomm(25);
        $this->SetFont('Poppins', 'B', 14);
        $this->Cell(
            $cellWidth,
            $cellHeight,
            'Payment Status:',
        );
        $this->Ln();

        $stampX = $this->GetX();
        $stampY = $this->GetY();
        $stampLen = $this->pxTomm(100);
        if ($headData['is_cod']) {
            $this->Image(base_url('/unpaid.png'), $stampX + 5, $stampY - 5, $stampLen, $stampLen);
        } else {

            $this->Image(base_url('/paid.png'), $stampX + 5, $stampY - 5, $stampLen, $stampLen);
        }

        // Right Section
        $this->SetX($this->pxTomm(298.5));
        $leftCellWidth = $this->pxTomm(125);
        $this->SetFont('Poppins', 'B', 14);
        $this->Cell(
            $leftCellWidth,
            $cellHeight,
            '',
        );
        $this->Cell(
            $cellWidth - $leftCellWidth,
            $cellHeight,
            ''
        );
        $this->Ln();

        $this->SetX($this->pxTomm(298.5));
        $this->SetFont('Poppins', 'B', 14);
        $this->Cell(
            $leftCellWidth,
            $cellHeight,
            'Subtotal'
        );
        $this->Cell(
            $cellWidth - $leftCellWidth,
            $cellHeight,
            ': ' . $headData['subtotal'] . '/-'
        );
        $this->Ln();
        $total = $headData['total'];
        $discount = $headData['discount'];
        if (!is_null($headData['discount']) && $headData['discount'] != 0) {
            $this->SetX($this->pxTomm(298.5));
            $this->SetFont('Poppins', 'B', 14);
            $this->Cell(
                $leftCellWidth,
                $cellHeight,
                'Discount'
            );
            $this->Cell(
                $cellWidth - $leftCellWidth,
                $cellHeight,
                ': ' . $discount . '/-'
            );
            $this->Ln();
        } else {
            $this->SetX($this->pxTomm(298.5));
            $this->SetFont('Poppins', 'B', 14);
            $this->Cell(
                $leftCellWidth,
                $cellHeight,
                ''
            );
            $this->Cell(
                $cellWidth - $leftCellWidth,
                $cellHeight,
                ''
            );
            $this->Ln();
        }

        $this->SetFont('Poppins', 'R', 14);
        $this->Cell(
            $leftCellWidth,
            $cellHeight,
            'Thanks for Purchasing with us!'
        );
        if (!is_null($headData['coupon']) && $headData['coupon'] != 0) {
            // $couponModel = new CouponModel();
            // $coupon = $couponModel->find($headData['coupon']);
            $couponDiscount = ceil($headData['coupon_discount']);
            /* switch ($coupon['coupon_type']) {
                case 1:
                    $couponDiscount = (($subTotal / 100 * $coupon['coupon_type_name']));
                    break;
                case 2:
                    $couponDiscount = ($coupon['coupon_type_name']);
                    break;
            } */
            // $total -= $couponDiscount;

            $this->SetX($this->pxTomm(298.5));
            $this->SetFont('Poppins', 'B', 14);
            $this->Cell(
                $leftCellWidth,
                $cellHeight,
                'Coupon Applied'
            );
            $this->Cell(
                $cellWidth - $leftCellWidth,
                $cellHeight,
                ': ' . $couponDiscount . '/-'
            );
            $total -= $couponDiscount;
            $this->Ln();
        } else {
            $this->SetX($this->pxTomm(298.5));
            $this->SetFont('Poppins', 'B', 14);
            $this->Cell(
                $leftCellWidth,
                $cellHeight,
                ''
            );
            $this->Cell(
                $cellWidth - $leftCellWidth,
                $cellHeight,
                ''
            );
            $this->Ln();
        }
        $this->SetX($this->pxTomm(298.5));
        $this->SetFont('Poppins', 'B', 14);
        $this->Cell(
            $leftCellWidth,
            $cellHeight,
            'Total'
        );
        $this->Cell(
            $cellWidth - $leftCellWidth,
            $cellHeight,
            ': ' . $total . '/-'
        );
        $this->Ln();
    }

    public function Header()
    {
        $this->custome_fonts();
        $margin = $this->pxTomm(15);
        $this->SetMargins($margin, $margin, $margin);
        // echo $this->GetPageHeight(), '\n';
        // echo $this->GetPageWidth();
        $x = $y = $this->pxTomm(8);
        $width = $this->pxTomm(579);
        $height = $this->pxTomm(826);
        // $this->Rect($x, $y, $width, $height);
        $this->SetXY(
            $this->pxTomm(54),
            $this->pxTomm(18.22)
        );

        $this->SetFont('InriaSerif', 'R', 22.17);
        $brand_name = 'ARRANGEFREE';
        $brand_name = str_split($brand_name);
        // var_dump($brand_name);die;
        foreach ($brand_name as $char) {
            $this->Cell(
                $this->pxTomm(41),
                $this->pxTomm(27),
                $char,
                0,
                0,
                'C'
            );
        }
    }

    public function Footer()
    {
        $this->SetFont('Poppins', 'R', 12);
        $y = $this->pxTomm(30);
        $this->SetY(-$y);
        $this->Cell(0, $this->pxTomm(18), 'Address: Office number 724, Clover hills plaza, NIBM road Mohmmadwadi Pune 411048', 0, 1, 'C');
    }

    public function pxTomm($value)
    {
        return $value / 72.0 * 25.4;
    }

    public function newInvoice_temp($headData, $productsData)
    {
        $products = [
            [
                'Sofa Cum Bed with double bouncing ', 2, 12500, 37500,
            ],
            [
                'Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum ', 2, 12500, 37500,
            ],
        ];
        $cellWidth = $this->pxTomm(267);
        $cellHeight = $this->pxTomm(21);
        // Line 1
        // Left Section
        $this->SetXY(
            $this->pxTomm(29),
            $this->pxTomm(115)
        );
        $this->SetFont('Poppins', 'R', 14);
        $this->Cell(
            $cellWidth,
            $cellHeight,
            'To,',
            0,
            2
        );
        $this->SetFont('Poppins', 'B', 14);
        $this->MultiCell(
            $cellWidth,
            $cellHeight,
            'Sahil Khatik',
        );
        $this->SetX($this->pxTomm(29));
        $this->SetFont('Poppins', 'R', 14);
        $this->MultiCell(
            $cellWidth,
            $cellHeight,
            '502, Silver Estate, NIBM'
        );
        $this->SetX($this->pxTomm(29));
        $this->SetFont('Poppins', 'R', 14);
        $this->MultiCell(
            $cellWidth,
            $cellHeight,
            'Pune, 411048'
        );
        $this->SetX($this->pxTomm(29));
        $this->MultiCell(
            $cellWidth,
            $cellHeight,
            'Email :- sahilhaps@gmail.com'
        );
        $this->SetX($this->pxTomm(29));
        $this->MultiCell(
            $cellWidth,
            $cellHeight,
            'Phone No. :- 9999999999'
        );
        // End of Left Section
        // Right Section
        $x = 354;
        $this->SetXY(
            $this->pxTomm($x),
            $this->pxTomm(115)
        );
        $cellWidth = $cellWidth - $this->pxTomm(354 - 298.5);
        $this->SetFont('Poppins', 'B', 14);
        $this->Cell(
            $cellWidth,
            $cellHeight,
            'Invoice : 890890',
            0,
            2
        );
        $this->Ln();
        $this->SetX($this->pxTomm($x));
        $this->SetFont('Poppins', 'B', 14);
        $this->Cell(
            $this->pxTomm(72),
            $cellHeight,
            'Order ID : '
        );
        $this->SetFont('Poppins', 'R', 14);
        $this->Cell(
            $cellWidth - $this->pxTomm(72),
            $cellHeight,
            '23123322123',
            0,
            1
        );
        $this->SetX($this->pxTomm($x));
        $this->SetFont('Poppins', 'R', 14);
        $this->Cell(
            $cellWidth,
            $cellHeight,
            'Date : 23/03/2022',
            0,
            2
        );
        $this->SetFont('Poppins', 'R', 14);
        $this->Cell(
            $cellWidth,
            $cellHeight,
            'Time : 13:20',
            0,
            2
        );
        // $this->Ln();
        // $this->SetX($this->pxTomm(298.5));
        $this->SetFont('Poppins', 'B', 14);
        $this->Cell(
            $cellWidth,
            $cellHeight,
            'Payment Method : UPI',
            0,
            2
        );
        // Right section end

        // Table begins
        $this->SetFont('Poppins', 'R', 12);
        $this->SetY($this->pxTomm(290));
        $cellWidth = $this->pxTomm(566);
        $cellHeight = $this->pxTomm(30);
        $this->Cell(
            $this->pxTomm(44),
            $cellHeight,
            'Sr.no',
            'LTB',
            0,
            'C'
        );
        $this->Cell(
            $this->pxTomm(295),
            $cellHeight,
            'Description',
            'TB',
            0,
            'C'
        );
        $this->Cell(
            $this->pxTomm(54),
            $cellHeight,
            'Qty.',
            'TB',
            0,
            'C'
        );
        $this->Cell(
            $this->pxTomm(85),
            $cellHeight,
            'Rate',
            'TB',
            0,
            'C'
        );
        $this->Cell(
            $this->pxTomm(88),
            $cellHeight,
            'Amount',
            'TBR',
            0,
            'C'
        );
        $this->Ln();
        // Products
        foreach ($products as $key => $single_product) {
            $description = str_split($single_product[0], 45);
            if (count($description) > 1) {
                $border = 'T';
                $cellHeight = $this->pxTomm(18);
            } else {
                $border = 'TB';
                $cellHeight = $this->pxTomm(30);
            }
            $this->Cell(
                $this->pxTomm(44),
                $cellHeight,
                $key + 1,
                $border . 'L',
                0,
                'C'
            );
            $this->Cell(
                $this->pxTomm(295),
                $cellHeight,
                $description[0],
                $border,
                0,
                'L'
            );
            $this->Cell(
                $this->pxTomm(54),
                $cellHeight,
                $single_product[1],
                $border,
                0,
                'C'
            );
            $this->Cell(
                $this->pxTomm(85),
                $cellHeight,
                $single_product[2],
                $border,
                0,
                'C'
            );
            $this->Cell(
                $this->pxTomm(88),
                $cellHeight,
                $single_product[3],
                $border . 'R',
                0,
                'C'
            );

            for ($i = 1; $i < count($description); $i++) {
                $this->Ln();
                $border = ($i < count($description) - 1) ? '' : 'B';
                $this->Cell(
                    $this->pxTomm(44),
                    $cellHeight,
                    '',
                    $border . 'L',
                    0,
                    'C'
                );
                $this->Cell(
                    $this->pxTomm(295),
                    $cellHeight,
                    $description[$i],
                    $border,
                    0,
                    'L'
                );
                $this->Cell(
                    $this->pxTomm(54),
                    $cellHeight,
                    '',
                    $border,
                    0,
                    'C'
                );
                $this->Cell(
                    $this->pxTomm(85),
                    $cellHeight,
                    '',
                    $border,
                    0,
                    'C'
                );
                $this->Cell(
                    $this->pxTomm(88),
                    $cellHeight,
                    '',
                    $border . 'R',
                    0,
                    'C'
                );
            }
            $this->Ln();
        }
        // Table End

        $y = $this->GetY();
        $this->SetY($y + $this->pxTomm(30));
        $cellWidth = $this->pxTomm(267);
        $cellHeight = $this->pxTomm(25);
        $this->SetFont('Poppins', 'B', 14);
        $this->Cell(
            $cellWidth,
            $cellHeight,
            'Payment Status:',
        );
        $this->Ln();

        $stampX = $this->GetX();
        $stampY = $this->GetY();
        $stampLen = $this->pxTomm(100);
        if (1) {
            $this->Image(base_url('/unpaid.png'), $stampX + 5, $stampY - 5, $stampLen, $stampLen);
        } else {

            $this->Image(base_url('/paid.png'), $stampX + 5, $stampY - 5, $stampLen, $stampLen);
        }

        // Right Section
        $this->SetX($this->pxTomm(298.5));
        $leftCellWidth = $this->pxTomm(125);
        $this->SetFont('Poppins', 'B', 14);
        $this->Cell(
            $leftCellWidth,
            $cellHeight,
            'Payment Date',
        );
        $this->Cell(
            $cellWidth - $leftCellWidth,
            $cellHeight,
            ': 2/22/2022'
        );
        $this->Ln();

        $this->SetX($this->pxTomm(298.5));
        $this->SetFont('Poppins', 'R', 14);
        $this->Cell(
            $leftCellWidth,
            $cellHeight,
            'Subtotal'
        );
        $this->Cell(
            $cellWidth - $leftCellWidth,
            $cellHeight,
            ': 10,000/-'
        );
        $this->Ln();

        $this->SetX($this->pxTomm(298.5));
        $this->SetFont('Poppins', 'R', 14);
        $this->Cell(
            $leftCellWidth,
            $cellHeight,
            'Coupon Code'
        );
        $this->Cell(
            $cellWidth - $leftCellWidth,
            $cellHeight,
            ': 2,000/-'
        );
        $this->Ln();

        $this->SetFont('Poppins', 'R', 14);
        $this->Cell(
            $leftCellWidth,
            $cellHeight,
            'Thanks for Purchasing with us!'
        );
        $this->SetX($this->pxTomm(298.5));
        $this->SetFont('Poppins', 'R', 14);
        $this->Cell(
            $leftCellWidth,
            $cellHeight,
            'Total'
        );
        $this->Cell(
            $cellWidth - $leftCellWidth,
            $cellHeight,
            ': 8,000/-'
        );
        $this->Ln();
    }
}
