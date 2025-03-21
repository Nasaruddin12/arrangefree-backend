<?php

namespace App\Models;

use CodeIgniter\Model;

class BookingPaymentRequest extends Model
{
    protected $table      = 'booking_payment_requests';
    protected $primaryKey = 'id';

    protected $allowedFields = ['booking_id', 'user_id', 'amount', 'request_status', 'created_at', 'updated_at'];
    protected $useTimestamps = true;
}
