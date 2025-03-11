<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; }
        .invoice-box { width: 100%; padding: 20px; border: 1px solid #ddd; }
        .title { font-size: 20px; font-weight: bold; text-align: center; }
        .details { margin-top: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 8px; text-align: left; }
        .total { font-weight: bold; }
    </style>
</head>
<body>
    <div class="invoice-box">
        <div class="title">Invoice</div>
        <p><strong>Invoice No:</strong> <?= $booking['booking_id'] ?></p>
        <p><strong>Date:</strong> <?= date('d-m-Y', strtotime($booking['created_at'])) ?></p>
        <p><strong>Customer:</strong> <?= $booking['user_id'] ?></p>

        <table>
            <tr>
                <th>Service</th>
                <th>Rate</th>
                <th>Qty</th>
                <th>Amount</th>
            </tr>
            <?php foreach ($services as $service) : ?>
            <tr>
                <td><?= $service['service_id'] ?></td>
                <td>\u20B9<?= number_format($service['rate'], 2) ?></td>
                <td><?= $service['value'] ?></td>
                <td>&#8377;<?= number_format($service['amount'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>

        <p class="details"><strong>Subtotal:</strong> \u20B9<?= number_format($booking['total_amount'], 2) ?></p>
        <p class="details"><strong>Discount:</strong> ₹<?= number_format($booking['discount'], 2) ?></p>

        <p class="details total"><strong>Total Amount:</strong> ₹<?= number_format($booking['final_amount'], 2) ?></p>
    </div>
</body>
</html>
