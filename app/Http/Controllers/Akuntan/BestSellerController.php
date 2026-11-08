<?php

namespace App\Http\Controllers\Akuntan;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class BestSellerController extends Controller
{
    private const DEFAULT_PER_PAGE = 15;

    private const MAX_PER_PAGE = 100;

    #[OA\Get(
        path: '/api/best-sellers',
        operationId: 'bestSellerIndex',
        summary: 'Peringkat produk terlaris',
        description: 'Menampilkan peringkat produk berdasarkan volume penjualan dari order completed dengan payment success. Mendukung filter tanggal, pencarian, filter kategori, dan pengurutan.',
        tags: ['Best Sellers'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(name: 'period', required: false, schema: new OA\Schema(type: 'string', enum: ['today', '7days', 'this_month', 'custom'])),
            new OA\QueryParameter(name: 'start_date', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\QueryParameter(name: 'end_date', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\QueryParameter(name: 'search', required: false, schema: new OA\Schema(type: 'string')),
            new OA\QueryParameter(name: 'category_id', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\QueryParameter(name: 'sort', required: false, schema: new OA\Schema(type: 'string', enum: ['quantity', 'revenue', 'contribution'])),
            new OA\QueryParameter(name: 'page', required: false, schema: new OA\Schema(type: 'integer', minimum: 1)),
            new OA\QueryParameter(name: 'per_page', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Peringkat produk terlaris dengan metrik'),
            new OA\Response(response: 401, description: 'Tidak terautentikasi'),
            new OA\Response(response: 403, description: 'Tidak memiliki permission'),
            new OA\Response(response: 422, description: 'Parameter query tidak valid'),
        ],
    )]
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period' => ['nullable', 'string', 'in:today,7days,this_month,custom'],
            'start_date' => ['nullable', 'date', 'required_if:period,custom'],
            'end_date' => ['nullable', 'date', 'required_if:period,custom', 'after_or_equal:start_date'],
            'search' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'sort' => ['nullable', 'string', 'in:quantity,revenue,contribution'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_PER_PAGE],
        ]);

        [$periodStart, $periodEnd] = $this->resolvePeriod($validated);
        [$prevStart, $prevEnd] = $this->previousPeriod($periodStart, $periodEnd);
        $dayCount = max(1, $periodStart->diffInDays($periodEnd) + 1);

        $completedOrderIds = Order::where('status', Order::STATUS_COMPLETED)
            ->whereHas('payment', fn ($q) => $q->where('status', 'success'))
            ->whereBetween('completed_at', [$periodStart, $periodEnd])
            ->pluck('id');

        $productStats = OrderItem::query()
            ->whereIn('order_id', $completedOrderIds)
            ->select('product_id')
            ->selectRaw('SUM(quantity) as total_quantity')
            ->selectRaw('SUM(subtotal) as total_revenue')
            ->groupBy('product_id');

        if (filled($validated['search'] ?? null)) {
            $search = $validated['search'];
            $productStats->whereHas('product', fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhereHas('category', fn ($cq) => $cq->where('name', 'like', "%{$search}%"))
            );
        }

        if (filled($validated['category_id'] ?? null)) {
            $productStats->whereHas('product', fn ($q) => $q->where('category_id', $validated['category_id']));
        }

        $sortColumn = match ($validated['sort'] ?? 'quantity') {
            'revenue', 'contribution' => 'total_revenue',
            default => 'total_quantity',
        };

        $allStats = $productStats->orderByDesc($sortColumn)->get();

        $grandTotalQuantity = (int) $allStats->sum('total_quantity');
        $grandTotalRevenue = (int) $allStats->sum('total_revenue');

        $topProduct = $allStats->first();
        $topProductModel = $topProduct ? Product::with('category:id,name')->find($topProduct->product_id) : null;

        $prevOrderIds = Order::where('status', Order::STATUS_COMPLETED)
            ->whereHas('payment', fn ($q) => $q->where('status', 'success'))
            ->whereBetween('completed_at', [$prevStart, $prevEnd])
            ->pluck('id');

        $prevTotalQuantity = (int) OrderItem::whereIn('order_id', $prevOrderIds)->sum('quantity');

        $topProductPrevQuantity = 0;
        if ($topProduct) {
            $topProductPrevQuantity = (int) OrderItem::whereIn('order_id', $prevOrderIds)
                ->where('product_id', $topProduct->product_id)
                ->sum('quantity');
        }

        $topProductChangePercent = $topProductPrevQuantity > 0
            ? round(((int) ($topProduct?->total_quantity ?? 0) - $topProductPrevQuantity) / $topProductPrevQuantity * 100, 2)
            : null;

        $topCategoryStats = OrderItem::query()
            ->whereIn('order_id', $completedOrderIds)
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->select('categories.id', 'categories.name')
            ->selectRaw('SUM(order_items.quantity) as category_quantity')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('category_quantity')
            ->first();

        $activeCategoryCount = DB::table('categories')->where('is_active', true)->count();

        $perPage = (int) ($validated['per_page'] ?? self::DEFAULT_PER_PAGE);
        $currentPage = (int) ($validated['page'] ?? 1);
        $total = $allStats->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $offset = ($currentPage - 1) * $perPage;

        $pageItems = $allStats->slice($offset, $perPage)->values();
        $productIds = $pageItems->pluck('product_id')->all();
        $products = Product::with('category:id,name')->whereIn('id', $productIds)->get()->keyBy('id');

        $data = $pageItems->map(function ($stat, $index) use ($products, $grandTotalQuantity, $grandTotalRevenue, $offset): array {
            $rank = $offset + $index + 1;
            $product = $products->get($stat->product_id);
            $quantity = (int) $stat->total_quantity;
            $revenue = (int) $stat->total_revenue;
            $quantityPercent = $grandTotalQuantity > 0 ? round($quantity / $grandTotalQuantity * 100, 2) : 0;
            $revenuePercent = $grandTotalRevenue > 0 ? round($revenue / $grandTotalRevenue * 100, 2) : 0;

            return [
                'rank' => $rank,
                'product' => $product === null ? null : [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => (int) $product->price,
                    'category' => $product->category?->name,
                ],
                'total_quantity' => $quantity,
                'quantity_percent' => $quantityPercent,
                'total_revenue' => $revenue,
                'revenue_percent' => $revenuePercent,
                'badge' => $rank <= 3 ? "Top {$rank}" : ($revenuePercent >= 5 ? 'Laris' : null),
            ];
        })->all();

        $basePath = $request->url();

        return response()->json([
            'summary' => [
                'top_product' => $topProductModel === null ? null : [
                    'name' => $topProductModel->name,
                    'category' => $topProductModel->category?->name,
                    'total_quantity' => (int) ($topProduct?->total_quantity ?? 0),
                    'change_percent' => $topProductChangePercent,
                    'contribution_percent' => $grandTotalQuantity > 0
                        ? round((int) ($topProduct?->total_quantity ?? 0) / $grandTotalQuantity * 100, 2)
                        : 0,
                ],
                'total_quantity' => $grandTotalQuantity,
                'average_per_day' => $dayCount > 0 ? intdiv($grandTotalQuantity, $dayCount) : 0,
                'total_revenue' => $grandTotalRevenue,
                'active_category_count' => $activeCategoryCount,
                'top_category' => $topCategoryStats === null ? null : [
                    'name' => $topCategoryStats->name,
                    'total_quantity' => (int) $topCategoryStats->category_quantity,
                    'percent' => $grandTotalQuantity > 0
                        ? round((int) $topCategoryStats->category_quantity / $grandTotalQuantity * 100, 2)
                        : 0,
                ],
                'period_start' => $periodStart->toISOString(),
                'period_end' => $periodEnd->toISOString(),
            ],
            'data' => $data,
            'links' => [
                'first' => $basePath.'?page=1',
                'last' => $basePath.'?page='.$lastPage,
                'prev' => $currentPage > 1 ? $basePath.'?page='.($currentPage - 1) : null,
                'next' => $currentPage < $lastPage ? $basePath.'?page='.($currentPage + 1) : null,
            ],
            'meta' => [
                'current_page' => $currentPage,
                'from' => $total > 0 ? $offset + 1 : null,
                'last_page' => $lastPage,
                'path' => $basePath,
                'per_page' => $perPage,
                'to' => $total > 0 ? min($offset + $perPage, $total) : null,
                'total' => $total,
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/best-sellers/{product}',
        operationId: 'bestSellerShow',
        summary: 'Detail analisis produk terlaris',
        description: 'Menampilkan analisis detail produk: kuantitas terjual, kontribusi omzet, rata-rata harian, informasi harga & profitabilitas (HPP bahan baku, gross margin), dan waktu penjualan teramai (peak hours).',
        tags: ['Best Sellers'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'product', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\QueryParameter(name: 'period', required: false, schema: new OA\Schema(type: 'string', enum: ['today', '7days', 'this_month', 'custom'])),
            new OA\QueryParameter(name: 'start_date', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\QueryParameter(name: 'end_date', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detail analisis produk'),
            new OA\Response(response: 401, description: 'Tidak terautentikasi'),
            new OA\Response(response: 403, description: 'Tidak memiliki permission'),
            new OA\Response(response: 404, description: 'Produk tidak ditemukan'),
        ],
    )]
    public function show(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'period' => ['nullable', 'string', 'in:today,7days,this_month,custom'],
            'start_date' => ['nullable', 'date', 'required_if:period,custom'],
            'end_date' => ['nullable', 'date', 'required_if:period,custom', 'after_or_equal:start_date'],
        ]);

        [$periodStart, $periodEnd] = $this->resolvePeriod($validated);
        [$prevStart, $prevEnd] = $this->previousPeriod($periodStart, $periodEnd);
        $dayCount = max(1, $periodStart->diffInDays($periodEnd) + 1);

        $product->load(['category:id,name', 'recipes.ingredient:id,name,unit_cost']);

        $completedOrderIds = Order::where('status', Order::STATUS_COMPLETED)
            ->whereHas('payment', fn ($q) => $q->where('status', 'success'))
            ->whereBetween('completed_at', [$periodStart, $periodEnd])
            ->pluck('id');

        $productSales = OrderItem::whereIn('order_id', $completedOrderIds)
            ->where('product_id', $product->id)
            ->selectRaw('SUM(quantity) as total_quantity, SUM(subtotal) as total_revenue')
            ->first();

        $totalQuantity = (int) ($productSales->total_quantity ?? 0);
        $totalRevenue = (int) ($productSales->total_revenue ?? 0);

        $grandTotalQuantity = (int) OrderItem::whereIn('order_id', $completedOrderIds)->sum('quantity');
        $grandTotalRevenue = (int) OrderItem::whereIn('order_id', $completedOrderIds)->sum('subtotal');

        $prevOrderIds = Order::where('status', Order::STATUS_COMPLETED)
            ->whereHas('payment', fn ($q) => $q->where('status', 'success'))
            ->whereBetween('completed_at', [$prevStart, $prevEnd])
            ->pluck('id');

        $prevQuantity = (int) OrderItem::whereIn('order_id', $prevOrderIds)
            ->where('product_id', $product->id)
            ->sum('quantity');

        $prevDayCount = max(1, $prevStart->diffInDays($prevEnd) + 1);
        $avgDaily = $dayCount > 0 ? round($totalQuantity / $dayCount, 1) : 0;
        $prevAvgDaily = $prevDayCount > 0 ? round($prevQuantity / $prevDayCount, 1) : 0;
        $avgDailyChangePercent = $prevAvgDaily > 0
            ? round(($avgDaily - $prevAvgDaily) / $prevAvgDaily * 100, 2)
            : null;

        $hpp = 0;
        foreach ($product->recipes as $recipe) {
            $hpp += (int) round((float) $recipe->quantity_required * (int) $recipe->ingredient->unit_cost);
        }

        $price = (int) $product->price;
        $grossProfit = $price - $hpp;
        $marginPercent = $price > 0 ? round($grossProfit / $price * 100, 2) : 0;

        $peakHours = OrderItem::query()
            ->whereIn('order_items.order_id', $completedOrderIds)
            ->where('order_items.product_id', $product->id)
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->selectRaw("
                CASE
                    WHEN HOUR(orders.created_at) >= 11 AND (HOUR(orders.created_at) < 14 OR (HOUR(orders.created_at) = 14 AND MINUTE(orders.created_at) = 0)) THEN 'lunch'
                    WHEN HOUR(orders.created_at) >= 18 AND HOUR(orders.created_at) < 21 THEN 'dinner'
                    ELSE 'other'
                END as time_slot
            ")
            ->selectRaw('SUM(order_items.quantity) as slot_quantity')
            ->groupBy('time_slot')
            ->get()
            ->keyBy('time_slot');

        $lunchQty = (int) ($peakHours->get('lunch')?->slot_quantity ?? 0);
        $dinnerQty = (int) ($peakHours->get('dinner')?->slot_quantity ?? 0);
        $otherQty = (int) ($peakHours->get('other')?->slot_quantity ?? 0);
        $peakTotal = $lunchQty + $dinnerQty + $otherQty;

        $allProducts = OrderItem::whereIn('order_id', $completedOrderIds)
            ->select('product_id')
            ->selectRaw('SUM(quantity) as qty')
            ->groupBy('product_id')
            ->orderByDesc('qty')
            ->get();

        $rank = $allProducts->search(fn ($item) => $item->product_id === $product->id);
        $rank = $rank !== false ? $rank + 1 : null;

        return response()->json([
            'data' => [
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'category' => $product->category?->name,
                    'price' => $price,
                ],
                'rank' => $rank,
                'badge' => $rank !== null && $rank <= 3 ? "Top {$rank} Terlaris" : null,
                'sales' => [
                    'total_quantity' => $totalQuantity,
                    'quantity_percent' => $grandTotalQuantity > 0 ? round($totalQuantity / $grandTotalQuantity * 100, 2) : 0,
                    'total_revenue' => $totalRevenue,
                    'revenue_percent' => $grandTotalRevenue > 0 ? round($totalRevenue / $grandTotalRevenue * 100, 2) : 0,
                    'average_daily' => $avgDaily,
                    'average_daily_change_percent' => $avgDailyChangePercent,
                ],
                'profitability' => [
                    'price' => $price,
                    'hpp' => $hpp,
                    'gross_profit' => $grossProfit,
                    'margin_percent' => $marginPercent,
                    'cost_ratio' => $price > 0 ? round($hpp / $price * 100, 2) : 0,
                ],
                'peak_hours' => [
                    [
                        'label' => 'Makan Siang (11:30 - 14:00)',
                        'slot' => 'lunch',
                        'quantity' => $lunchQty,
                        'percent' => $peakTotal > 0 ? round($lunchQty / $peakTotal * 100, 2) : 0,
                    ],
                    [
                        'label' => 'Makan Malam (18:00 - 21:00)',
                        'slot' => 'dinner',
                        'quantity' => $dinnerQty,
                        'percent' => $peakTotal > 0 ? round($dinnerQty / $peakTotal * 100, 2) : 0,
                    ],
                    [
                        'label' => 'Di Luar Jam Sibuk',
                        'slot' => 'other',
                        'quantity' => $otherQty,
                        'percent' => $peakTotal > 0 ? round($otherQty / $peakTotal * 100, 2) : 0,
                    ],
                ],
                'period_start' => $periodStart->toISOString(),
                'period_end' => $periodEnd->toISOString(),
            ],
        ]);
    }

    private function resolvePeriod(array $validated): array
    {
        $period = $validated['period'] ?? 'this_month';

        return match ($period) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            '7days' => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
            'custom' => [
                Carbon::parse($validated['start_date'])->startOfDay(),
                Carbon::parse($validated['end_date'])->endOfDay(),
            ],
            default => [now()->startOfMonth(), now()->endOfDay()],
        };
    }

    private function previousPeriod(Carbon $start, Carbon $end): array
    {
        $days = $start->diffInDays($end) + 1;

        return [
            (clone $start)->subDays($days),
            (clone $start)->subSecond(),
        ];
    }
}
