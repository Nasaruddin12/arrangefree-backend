<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            margin: 0;
            padding: 20px;
            background: #f8f8f8;
        }

        .invoice-box {
            width: 100%;
            max-width: 800px;
            margin: auto;
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }

        .title {
            font-size: 22px;
            font-weight: bold;
            text-align: center;
            padding-bottom: 10px;
            border-bottom: 2px solid #ddd;
        }

        .details {
            margin-top: 15px;
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
            text-align: left;
        }

        table th {
            background: #f2f2f2;
            font-weight: bold;
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
    </style>
</head>

<body>
    <div class="invoice-box">
        <div class="title">Invoice</div>

        <div class="details">
            <div>
                <p><strong>Invoice No:</strong> <?= $booking['booking_id'] ?></p>
                <p><strong>Date:</strong> <?= date('d-m-Y', strtotime($booking['created_at'])) ?></p>
            </div>
            <div>
                <p><strong>Customer Name:</strong> <?= $booking['user_name'] ?></p>
                <p><strong>Customer Address:</strong> <?= $booking['customer_address'] ?></p>
            </div>
        </div>

        <table>
            <tr>
                <th>Service Name</th>
                <th>Rate (₹)</th>
                <th>Qty</th>
                <th>Amount (₹)</th>
            </tr>
            <?php foreach ($services as $service) : ?>
                <tr>
                    <td><?= $service['service_name'] ?></td>
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
    </div>
</body>

</html>
