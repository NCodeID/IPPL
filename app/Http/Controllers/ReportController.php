<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;

class ReportController extends Controller
{
   
    // menu paling laku dalam 30 hari kebelakang
    public function bestSellingItems(Request $request)
    {
        $days = $request->input('days', 30);

        $bestSellers = OrderItem::selectRaw('product_id, product_name, SUM(quantity) as total_terjual')
            ->whereHas('order', function ($query) use ($days) {
                $query->where('status', 'completed')
                    ->where('created_at', '>=', now()->subDays($days));
            })
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('total_terjual')
            ->limit(10)
            ->get();

        return response()->json($bestSellers);
    }
    // buat tampilan aja fetch 3 menu yang baru saja dipesan
    public function recentOrders()
    {
        $recentOrders = Order::with(['orderItems', 'payments'])
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get();

        return response()->json($recentOrders);
    }
}
