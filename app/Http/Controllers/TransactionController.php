<?php

namespace App\Http\Controllers;

use App\Models\Payment;

class TransactionController extends Controller
{
    // tranksaksi hari ini
    public function todayTransactions()
    {
        $transactions = Payment::with(['order', 'paymentMethod', 'cashier'])
            ->whereDate('created_at', today())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'transactions' => $transactions,
            'count' => $transactions->count(),
        ]);
    }
}