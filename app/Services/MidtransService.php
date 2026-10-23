<?php

namespace App\Services;

use Midtrans\Config;
use Midtrans\Snap;

class MidtransService
{
    public function __construct()
    {
        Config::$serverKey = config('services.midtrans.server_key');
        Config::$isProduction = config('services.midtrans.is_production');
        Config::$isSanitized = config('services.midtrans.is_sanitized');
        Config::$is3ds = config('services.midtrans.is_3ds');
    }

    /**
     * Create a Snap transaction.
     *
     * @return object{token: string, redirect_url: string}
     */
    public function createTransaction(array $params): object
    {
        return Snap::createTransaction($params);
    }

    public function getSnapToken(array $params): string
    {
        return Snap::getSnapToken($params);
    }
}
