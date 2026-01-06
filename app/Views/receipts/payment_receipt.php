<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Payment Receipt</title>

    <style>
        body {
            font-family: 'DejaVu Sans', 'Segoe UI', sans-serif;
            background: #fff;
        }

        .invoice-box {
            max-width: 800px;
            margin: auto;
            background: #fff;
            border-radius: 10px;
        }

        .title {
            font-size: 22px;
            font-weight: bold;
            text-align: center;
            padding: 12px 0;
            border-bottom: 2px solid #ddd;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        table th,
        table td {
            border: 1px solid #ddd;
            padding: 8px;
        }

        table th {
            background: #f2f2f2;
            text-align: left;
        }

        .summary {
            margin-top: 15px;
            padding: 10px;
            background: #f9f9f9;
        }

        .summary p {
            display: flex;
            justify-content: space-between;
            margin: 4px 0;
        }

        .total {
            font-weight: bold;
            font-size: 16px;
            border-top: 2px solid #ddd;
            padding-top: 8px;
        }

        .note {
            font-size: 12px;
            margin-top: 25px;
            text-align: center;
        }
    </style>
</head>

<body>

<?php
$logoPath = 'https://backend.seeb.in/public/logo.webp';
$logoData = base64_encode(file_get_contents($logoPath));
$logoSrc = 'data:image/webp;base64,' . $logoData;
?>

<div class="invoice-box">

    <div class="title">PAYMENT RECEIPT</div>

    <!-- HEADER -->
    <table style="border:0">
        <tr>
            <td style="border:0">
                <img src="<?= $logoSrc ?>" style="width:100px">
            </td>
            <td style="border:0; text-align:right">
                <strong>Seeb Design Pvt Ltd</strong><br>
                217, Tower-2 WTC, Kharadi<br>
                Pune, India<br>
                Phone: 18005703133<br>
                Email: contact@seeb.in
            </td>
        </tr>
    </table>

    <!-- RECEIPT INFO -->
    <table style="margin-top:15px; background: #f9fafb;">
        <tr style="background: #e8f0fe; border: 2px solid #1a73e8;">
            <td style="border: 2px solid #1a73e8; padding: 12px; font-weight: bold; color: #1a73e8; width: 50%;">Receipt Number</td>
            <td style="border: 2px solid #1a73e8; padding: 12px; color: #333; width: 50%;">#<?= $payment['id'] ?></td>
        </tr>
        <tr style="background: #fff;">
            <td style="border: 1px solid #ddd; padding: 12px; font-weight: bold; color: #555;">Booking ID</td>
            <td style="border: 1px solid #ddd; padding: 12px; color: #1a73e8; font-weight: 500;"><?= $payment['booking_id'] ?></td>
        </tr>
        <tr style="background: #f9fafb;">
            <td style="border: 1px solid #ddd; padding: 12px; font-weight: bold; color: #555;">Payment Date & Time</td>
            <td style="border: 1px solid #ddd; padding: 12px; color: #333;"><?= date('d M Y, h:i A', strtotime($payment['created_at'])) ?></td>
        </tr>
        <tr style="background: #fff;">
            <td style="border: 1px solid #ddd; padding: 12px; font-weight: bold; color: #555;">Customer Name</td>
            <td style="border: 1px solid #ddd; padding: 12px; color: #333;"><?= $payment['name'] ?></td>
        </tr>
        <tr style="background: #f9fafb;">
            <td style="border: 1px solid #ddd; padding: 12px; font-weight: bold; color: #555;">Mobile Number</td>
            <td style="border: 1px solid #ddd; padding: 12px; color: #333;"><?= $payment['mobile_no'] ?></td>
        </tr>
        <tr style="background: #fff;">
            <td style="border: 1px solid #ddd; padding: 12px; font-weight: bold; color: #555;">Transaction ID</td>
            <td style="border: 1px solid #ddd; padding: 12px; color: #1a73e8; font-weight: 500; word-break: break-all;"><?= $payment['transaction_id'] ?></td>
        </tr>
        <tr style="background: #fff;">
            <td style="border: 1px solid #ddd; padding: 12px; font-weight: bold; color: #555;">Amount</td>
            <td style="border: 1px solid #ddd; padding: 12px; color: #333; font-weight: bold;">₹<?= number_format($payment['amount'],2) ?></td>
        </tr>
        <tr style="background: #fff;">
            <td style="border: 1px solid #ddd; padding: 12px; font-weight: bold; color: #555;">Payment Mode</td>
            <td style="border: 1px solid #ddd; padding: 12px; color: #333; font-weight: bold;">Razorpay (Online)</td>
        </tr>
    </table>

    <!-- TOTALS -->
    

    <div class="note">
        This is a system-generated receipt.  
        Payment received successfully. Thank you for choosing Seeb.
    </div>

</div>

</body>
</html>
