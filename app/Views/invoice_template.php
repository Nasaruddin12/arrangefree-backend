<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Noto+Sans&display=swap');

        body {
            /* font-family: 'DejaVu Sans', 'Helvetica', sans-serif; */
            font-family: 'sans-serif', 'Noto Sans', 'DejaVu Sans', 'Segoe UI', sans-serif;
            margin: 0;
            /* padding: 20px; */
            background: #f8f8f8;
        }

        .invoice-box {
            width: 100%;
            max-width: 800px;
            margin: auto;
            background: #fff;
            /* padding: 20px; */
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            display: flex !important;
            justify-content: space-between;
            align-items: center;
        }

        .header img {
            height: 60px;
            /* width: 260px; */
            object-fit: contain;

        }

        .company-details {
            /* text-align: right; */
            font-size: 14px;
        }

        .title {
            font-size: 22px;
            font-weight: bold;
            text-align: center;
            padding: 10px 0;
            border-bottom: 2px solid #ddd;
            /* margin-top: 15px; */
        }

        .details {
            margin-top: 10px;
            display: flex;
            justify-content: space-between;
        }

        .details p {
            margin: 5px 0;
            font-size: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        table th,
        table td {
            border: 1px solid #ddd;
            padding: 10px;
        }

        table th {
            background: #f2f2f2;
            font-weight: bold;
            text-align: left;
        }

        table td:last-child,
        table th:last-child {
            text-align: right;
        }

        .total-section {
            margin-top: 15px;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 5px;
        }

        .total-section p {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
            font-size: 14px;
        }

        .total-amount {
            font-size: 16px;
            font-weight: bold;
            border-top: 2px solid #ddd;
            padding-top: 10px;
        }

        .note {
            font-size: 12px;
            margin-top: 30px;
        }

        .signature {
            margin-top: 50px;
            font-size: 14px;
        }

        .signature p {
            margin: 0;
        }

        @media print {
            body {
                background: none;
            }

            .invoice-box {
                box-shadow: none;
            }
        }
    </style>
</head>

<body>
    <div class="invoice-box">
        <div class="title">INVOICE</div>
        <?php
        $logoPath = 'https://backend.seeb.in/public/logo.webp'; // Absolute or relative server path
        $logoData = base64_encode(file_get_contents($logoPath));
        $logoSrc = 'data:image/png;base64,' . $logoData;
        ?>
        <table width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 5px; border:#f8f8f8">
            <tr>
                <td width="40%" style="border: 0px;">
                    <img src="<?= $logoSrc ?>" alt="Logo" style="width:100px; height:100px;" />
                </td>
                <td width="60%" style="text-align: right; font-size: 14px; border:0px;">
                    <strong>XYZ Services Pvt Ltd</strong><br>
                    123 Business Street, Pune, India<br>
                    GSTIN: 29ABCDE1234F2Z5<br>
                    Phone: +91-9876543210<br>
                    Email: contact@seeb.in
                </td>
            </tr>
        </table>
        <div class="title"></div>
        <table width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 5px; border:#f8f8f8">
            <tr>
                <td width="50%" style="border: 0px;">
                    <div>
                        <p><strong>Invoice No:</strong> <?= $booking['booking_id'] ?></p>
                        <p><strong>Date:</strong> <?= date('d-m-Y', strtotime($booking['created_at'])) ?></p>
                        <p><strong>Due Date:</strong> <?= date('d-m-Y', strtotime($booking['due_date'] ?? '+7 days')) ?></p>
                    </div>
                </td>
                <td width="50%" style="text-align: left; font-size: 14px; border:0px;">
                    <div>
                        <p><strong>Customer Name:</strong> <?= $booking['user_name'] ?></p>
                        <p><strong>Customer Address:</strong> <?= $booking['customer_address'] ?></p>
                        <p><strong>Customer GSTIN:</strong> <?= $booking['customer_gstin'] ?? 'N/A' ?></p>
                    </div>
                </td>
            </tr>
        </table>
        <!-- 
        <div class="details">
            <div>
                <p><strong>Invoice No:</strong> <?= $booking['booking_id'] ?></p>
                <p><strong>Date:</strong> <?= date('d-m-Y', strtotime($booking['created_at'])) ?></p>
                <p><strong>Due Date:</strong> <?= date('d-m-Y', strtotime($booking['due_date'] ?? '+7 days')) ?></p>
            </div>
            <div>
                <p><strong>Customer Name:</strong> <?= $booking['user_name'] ?></p>
                <p><strong>Customer Address:</strong> <?= $booking['customer_address'] ?></p>
                <p><strong>Customer GSTIN:</strong> <?= $booking['customer_gstin'] ?? 'N/A' ?></p>
            </div>
        </div> -->

        <table>
            <tr>
                <th>Service Name</th>
                <th>Rate (₹)</th>
                <th>Qty</th>
                <th>Amount (₹)</th>
            </tr>
            <?php foreach ($services as $service) : ?>
                <tr>
                    <td>
                        <strong><?= $service['service_name'] ?></strong>

                        <?php
                        $addons = json_decode($service['addons'], true);
                        if (!empty($addons)):
                        ?>
                            <ul style="padding-left: 16px; margin: 5px 0 0 0; font-size: 13px; color: #555;">
                                <?php foreach ($addons as $addon): ?>
                                    <li>
                                        <?= htmlspecialchars($addon['name']) ?>:
                                        <?= $addon['qty'] ?> × ₹<?= number_format($addon['price'], 2) ?> =
                                        ₹<?= number_format($addon['total'], 2) ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </td>
                    <td><?= number_format($service['rate'], 2) ?></td>
                    <td><?= $service['value'] ?></td>
                    <td><?= number_format($service['amount'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>

        <div class="total-section">
            <p><strong>Subtotal:</strong> <span>₹<?= number_format($booking['total_amount'], 2) ?></span></p>
            <p><strong>Discount:</strong> <span>₹<?= number_format($booking['discount'], 2) ?></span></p>
            <p><strong>Total:</strong> <span>₹<?= number_format($booking['total_amount'] - $booking['discount'], 2) ?></span></p>
            <p><strong>CGST (9%):</strong> <span>₹<?= number_format($booking['cgst'], 2) ?></span></p>
            <p><strong>SGST (9%):</strong> <span>₹<?= number_format($booking['sgst'], 2) ?></span></p>
            <p class="total-amount"><strong>Total Amount:</strong> <span>₹<?= number_format($booking['final_amount'], 2) ?></span></p>
        </div>

        <div class="total-section">
            <p><strong>Payment Type:</strong> <span><?= ucfirst($booking['payment_type']) ?></span></p>
            <p><strong>Paid Amount:</strong> <span>₹<?= number_format($booking['paid_amount'], 2) ?></span></p>
            <p class="total-amount"><strong>Due Amount:</strong> <span>₹<?= number_format($booking['final_amount'] - $booking['paid_amount'], 2) ?></span></p>
        </div>

        <div class="note">
            <strong>Note:</strong> Thank you for your business. Please make the payment by the due date to avoid late fees. For queries, contact us at info@seeb.in.
        </div>

        <div class="signature">
            <p><strong>Authorized Signature:</strong></p>
            <p>__________________________</p>
        </div>
    </div>
</body>

</html>