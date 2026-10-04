<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;


/**
 * @group Sales
 */
class SalesController extends Controller
{
    // khusus hari ini
    public function todaySales()
    {
        $todaySales = Order::whereDate('created_at', today())
            ->where('status', 'completed')
            ->sum('total');

        $todayOrders = Order::whereDate('created_at', today())
            ->where('status', 'completed')
            ->count();

        return response()->json([
            'total_sales' => $todaySales,
            'total_orders' => $todayOrders,
            'currency' => 'IDR',
        ]);
    }

    // 7 hari kebelakang
    public function salesTrend(Request $request)
    {
        $days = $request->input('days', 7);

        $trend = Order::selectRaw('DATE(created_at) as date, SUM(total) as total_sales, COUNT(*) as order_count')
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json($trend);
    }
}
