<?php

namespace App\Http\Controllers\Akuntan;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class SalesReportController extends Controller
{
    private const DEFAULT_PER_PAGE = 15;

    private const MAX_PER_PAGE = 100;

    #[OA\Get(
        path: '/api/sales-reports',
        operationId: 'salesReportIndex',
        summary: 'Laporan penjualan dengan ringkasan metrik dan daftar transaksi',
        description: 'Menampilkan ringkasan penjualan (total penjualan bersih, jumlah transaksi, diskon, pajak, breakdown metode pembayaran) beserta daftar order completed dengan payment success. Mendukung filter tanggal, pencarian, dan filter metode pembayaran/kasir.',
        tags: ['Sales Reports'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(
                name: 'period',
                description: 'Filter periode: today, 7days, this_month, custom',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['today', '7days', 'this_month', 'custom']),
            ),
            new OA\QueryParameter(
                name: 'start_date',
                description: 'Tanggal awal untuk period=custom (format: Y-m-d)',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date'),
            ),
            new OA\QueryParameter(
                name: 'end_date',
                description: 'Tanggal akhir untuk period=custom (format: Y-m-d)',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date'),
            ),
            new OA\QueryParameter(
                name: 'search',
                description: 'Pencarian berdasarkan nomor struk, nomor meja, atau nama kasir',
                required: false,
                schema: new OA\Schema(type: 'string'),
            ),
            new OA\QueryParameter(
                name: 'payment_method',
                description: 'Filter tipe metode pembayaran: cash, qris, transfer, card',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['cash', 'qris', 'transfer', 'card']),
            ),
            new OA\QueryParameter(
                name: 'cashier_id',
                description: 'Filter berdasarkan ID kasir',
                required: false,
                schema: new OA\Schema(type: 'integer'),
            ),
            new OA\QueryParameter(
                name: 'page',
                description: 'Nomor halaman',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 1),
            ),
            new OA\QueryParameter(
                name: 'per_page',
                description: 'Jumlah data per halaman (default: 15, max: 100)',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Laporan penjualan dengan ringkasan dan daftar transaksi',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'summary',
                            properties: [
                                new OA\Property(property: 'net_sales', type: 'integer', example: 15000000),
                                new OA\Property(property: 'net_sales_change_percent', type: 'number', format: 'float', nullable: true, example: 12.5),
                                new OA\Property(property: 'total_transactions', type: 'integer', example: 120),
                                new OA\Property(property: 'average_per_receipt', type: 'integer', example: 125000),
                                new OA\Property(property: 'total_discount', type: 'integer', example: 500000),
                                new OA\Property(property: 'discount_order_count', type: 'integer', example: 30),
                                new OA\Property(property: 'total_tax', type: 'integer', example: 1450000),
                                new OA\Property(
                                    property: 'payment_methods',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'type', type: 'string', example: 'cash'),
                                            new OA\Property(property: 'total_amount', type: 'integer', example: 5000000),
                                            new OA\Property(property: 'transaction_count', type: 'integer', example: 40),
                                            new OA\Property(property: 'percentage', type: 'number', format: 'float', example: 33.33),
                                        ],
                                        type: 'object',
                                    ),
                                ),
                                new OA\Property(property: 'period_start', type: 'string', format: 'date-time'),
                                new OA\Property(property: 'period_end', type: 'string', format: 'date-time'),
                            ],
                            type: 'object',
                        ),
                        new OA\Property(property: 'filtered_count', type: 'integer', example: 120),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'order_number', type: 'string', example: 'ORD-20261108-143000-A1B2'),
                                    new OA\Property(property: 'completed_at', type: 'string', format: 'date-time'),
                                    new OA\Property(property: 'table_number', type: 'integer', nullable: true, example: 5),
                                    new OA\Property(property: 'order_type', type: 'string', enum: ['dine_in', 'takeaway']),
                                    new OA\Property(
                                        property: 'cashier',
                                        properties: [
                                            new OA\Property(property: 'id', type: 'integer'),
                                            new OA\Property(property: 'name', type: 'string'),
                                        ],
                                        type: 'object',
                                        nullable: true,
                                    ),
                                    new OA\Property(property: 'subtotal', type: 'integer', example: 100000),
                                    new OA\Property(property: 'discount', type: 'integer', example: 10000),
                                    new OA\Property(property: 'tax', type: 'integer', example: 9000),
                                    new OA\Property(property: 'total', type: 'integer', example: 99000),
                                    new OA\Property(property: 'payment_amount', type: 'integer', example: 99000),
                                    new OA\Property(property: 'payment_method_type', type: 'string', example: 'cash'),
                                    new OA\Property(property: 'payment_method_name', type: 'string', example: 'Tunai'),
                                ],
                                type: 'object',
                            ),
                        ),
                        new OA\Property(property: 'links', type: 'object'),
                        new OA\Property(property: 'meta', type: 'object'),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Tidak terautentikasi'),
            new OA\Response(response: 403, description: 'Tidak memiliki permission manage_payments atau akun tidak aktif'),
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
            'payment_method' => ['nullable', 'string', 'in:cash,qris,transfer,card'],
            'cashier_id' => ['nullable', 'integer', 'exists:users,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_PER_PAGE],
        ]);

        [$periodStart, $periodEnd] = $this->resolvePeriod($validated);
        [$prevStart, $prevEnd] = $this->previousPeriod($periodStart, $periodEnd);

        $baseQuery = Order::query()
            ->where('status', Order::STATUS_COMPLETED)
            ->whereHas('payment', fn ($q) => $q->where('status', 'success'))
            ->whereBetween('completed_at', [$periodStart, $periodEnd]);

        $currentMetrics = (clone $baseQuery)
            ->join('payments', 'payments.order_id', '=', 'orders.id')
            ->where('payments.status', 'success')
            ->selectRaw('COALESCE(SUM(payments.amount), 0) as net_sales')
            ->selectRaw('COUNT(orders.id) as total_transactions')
            ->selectRaw('COALESCE(SUM(orders.discount), 0) as total_discount')
            ->selectRaw('SUM(CASE WHEN orders.discount > 0 THEN 1 ELSE 0 END) as discount_order_count')
            ->selectRaw('COALESCE(SUM(orders.tax), 0) as total_tax')
            ->first();

        $prevNetSales = Order::query()
            ->where('status', Order::STATUS_COMPLETED)
            ->whereHas('payment', fn ($q) => $q->where('status', 'success'))
            ->whereBetween('completed_at', [$prevStart, $prevEnd])
            ->join('payments', 'payments.order_id', '=', 'orders.id')
            ->where('payments.status', 'success')
            ->sum('payments.amount');

        $netSalesChangePercent = $prevNetSales > 0
            ? round(((int) $currentMetrics->net_sales - $prevNetSales) / $prevNetSales * 100, 2)
            : null;

        $totalTransactions = (int) $currentMetrics->total_transactions;
        $averagePerReceipt = $totalTransactions > 0
            ? intdiv((int) $currentMetrics->net_sales, $totalTransactions)
            : 0;

        $paymentBreakdown = (clone $baseQuery)
            ->join('payments', 'payments.order_id', '=', 'orders.id')
            ->where('payments.status', 'success')
            ->join('payment_methods', 'payment_methods.id', '=', 'payments.payment_method_id')
            ->groupBy('payment_methods.type')
            ->selectRaw('payment_methods.type')
            ->selectRaw('COALESCE(SUM(payments.amount), 0) as total_amount')
            ->selectRaw('COUNT(orders.id) as transaction_count')
            ->get();

        $grandTotal = $paymentBreakdown->sum('total_amount');
        $paymentMethods = collect(['cash', 'qris', 'transfer', 'card'])
            ->map(function (string $type) use ($paymentBreakdown, $grandTotal): array {
                $row = $paymentBreakdown->firstWhere('type', $type);

                return [
                    'type' => $type,
                    'total_amount' => (int) ($row->total_amount ?? 0),
                    'transaction_count' => (int) ($row->transaction_count ?? 0),
                    'percentage' => $grandTotal > 0
                        ? round((int) ($row->total_amount ?? 0) / $grandTotal * 100, 2)
                        : 0,
                ];
            })
            ->all();

        $listQuery = Order::query()
            ->where('orders.status', Order::STATUS_COMPLETED)
            ->whereHas('payment', fn ($q) => $q->where('status', 'success'))
            ->whereBetween('orders.completed_at', [$periodStart, $periodEnd])
            ->with([
                'table:id,number',
                'payment' => fn ($q) => $q->where('status', 'success')->with([
                    'cashier:id,name',
                    'paymentMethod:id,name,type',
                ]),
            ]);

        if (filled($validated['search'] ?? null)) {
            $search = $validated['search'];
            $listQuery->where(function ($q) use ($search) {
                $q->where('orders.order_number', 'like', "%{$search}%")
                    ->orWhereHas('table', fn ($tq) => $tq->where('number', 'like', "%{$search}%"))
                    ->orWhereHas('payment', fn ($pq) => $pq->whereHas(
                        'cashier',
                        fn ($cq) => $cq->where('name', 'like', "%{$search}%"),
                    ));
            });
        }

        if (filled($validated['payment_method'] ?? null)) {
            $listQuery->whereHas('payment', fn ($q) => $q->whereHas(
                'paymentMethod',
                fn ($pmq) => $pmq->where('type', $validated['payment_method']),
            ));
        }

        if (filled($validated['cashier_id'] ?? null)) {
            $listQuery->whereHas('payment', fn ($q) => $q->where('cashier_id', $validated['cashier_id']));
        }

        $filteredCount = (clone $listQuery)->count();

        $orders = $listQuery
            ->orderByDesc('orders.completed_at')
            ->orderByDesc('orders.id')
            ->paginate(perPage: (int) ($validated['per_page'] ?? self::DEFAULT_PER_PAGE));

        return response()->json([
            'summary' => [
                'net_sales' => (int) $currentMetrics->net_sales,
                'net_sales_change_percent' => $netSalesChangePercent,
                'total_transactions' => $totalTransactions,
                'average_per_receipt' => $averagePerReceipt,
                'total_discount' => (int) $currentMetrics->total_discount,
                'discount_order_count' => (int) $currentMetrics->discount_order_count,
                'total_tax' => (int) $currentMetrics->total_tax,
                'payment_methods' => $paymentMethods,
                'period_start' => $periodStart->toISOString(),
                'period_end' => $periodEnd->toISOString(),
            ],
            'filtered_count' => $filteredCount,
            'data' => $orders->getCollection()
                ->map(fn (Order $order): array => $this->transformTransaction($order))
                ->all(),
            'links' => [
                'first' => $orders->url(1),
                'last' => $orders->url($orders->lastPage()),
                'prev' => $orders->previousPageUrl(),
                'next' => $orders->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $orders->currentPage(),
                'from' => $orders->firstItem(),
                'last_page' => $orders->lastPage(),
                'path' => $orders->path(),
                'per_page' => $orders->perPage(),
                'to' => $orders->lastItem(),
                'total' => $orders->total(),
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/sales-reports/{order}',
        operationId: 'salesReportShow',
        summary: 'Detail struk transaksi penjualan',
        description: 'Menampilkan detail lengkap transaksi: info order, daftar item, rincian bill (subtotal, diskon, dasar pengenaan pajak, PB1 10%, total bayar), dan informasi pembayaran.',
        tags: ['Sales Reports'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(
                name: 'order',
                description: 'ID order yang sudah completed',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 42),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detail struk transaksi',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 42),
                                new OA\Property(property: 'order_number', type: 'string', example: 'ORD-20261108-143000-A1B2'),
                                new OA\Property(property: 'status', type: 'string', example: 'Lunas'),
                                new OA\Property(property: 'payment_method_type', type: 'string', example: 'cash'),
                                new OA\Property(property: 'payment_method_name', type: 'string', example: 'Tunai'),
                                new OA\Property(property: 'completed_at', type: 'string', format: 'date-time'),
                                new OA\Property(property: 'table_number', type: 'integer', nullable: true, example: 5),
                                new OA\Property(property: 'order_type', type: 'string', enum: ['dine_in', 'takeaway']),
                                new OA\Property(
                                    property: 'cashier',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer'),
                                        new OA\Property(property: 'name', type: 'string'),
                                    ],
                                    type: 'object',
                                    nullable: true,
                                ),
                                new OA\Property(
                                    property: 'items',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'product_name', type: 'string', example: 'Nasi Goreng'),
                                            new OA\Property(property: 'quantity', type: 'integer', example: 2),
                                            new OA\Property(property: 'unit_price', type: 'integer', example: 25000),
                                            new OA\Property(property: 'subtotal', type: 'integer', example: 50000),
                                        ],
                                        type: 'object',
                                    ),
                                ),
                                new OA\Property(
                                    property: 'bill',
                                    properties: [
                                        new OA\Property(property: 'subtotal', type: 'integer', example: 100000),
                                        new OA\Property(property: 'discount', type: 'integer', example: 10000),
                                        new OA\Property(property: 'tax_base', type: 'integer', example: 90000),
                                        new OA\Property(property: 'tax', type: 'integer', example: 9000),
                                        new OA\Property(property: 'tax_rate', type: 'integer', example: 10),
                                        new OA\Property(property: 'total', type: 'integer', example: 99000),
                                    ],
                                    type: 'object',
                                ),
                                new OA\Property(property: 'total_menu', type: 'integer', example: 3),
                                new OA\Property(property: 'total_pcs', type: 'integer', example: 5),
                            ],
                            type: 'object',
                        ),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Tidak terautentikasi'),
            new OA\Response(response: 403, description: 'Tidak memiliki permission manage_payments atau akun tidak aktif'),
            new OA\Response(response: 404, description: 'Transaksi tidak ditemukan atau belum completed/lunas'),
        ],
    )]
    public function show(Order $order): JsonResponse
    {
        if ($order->status !== Order::STATUS_COMPLETED) {
            return response()->json(['message' => 'Transaksi tidak ditemukan.'], 404);
        }

        $order->load([
            'table:id,number',
            'items.product:id,name',
            'payment' => fn ($q) => $q->where('status', 'success')->with([
                'cashier:id,name',
                'paymentMethod:id,name,type',
            ]),
        ]);

        if (! $order->payment) {
            return response()->json(['message' => 'Transaksi tidak ditemukan.'], 404);
        }

        $subtotal = (int) $order->subtotal;
        $discount = (int) $order->discount;
        $taxBase = $subtotal - $discount;
        $tax = (int) $order->tax;
        $total = (int) $order->total;

        return response()->json([
            'data' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => 'Lunas',
                'payment_method_type' => $order->payment->paymentMethod?->type,
                'payment_method_name' => $order->payment->paymentMethod?->name,
                'completed_at' => $order->completed_at?->toISOString(),
                'table_number' => $order->table?->number,
                'order_type' => $order->order_type,
                'cashier' => $order->payment->cashier === null ? null : [
                    'id' => $order->payment->cashier->id,
                    'name' => $order->payment->cashier->name,
                ],
                'items' => $order->items
                    ->map(fn (OrderItem $item): array => [
                        'product_name' => $item->product?->name,
                        'quantity' => (int) $item->quantity,
                        'unit_price' => (int) $item->unit_price,
                        'subtotal' => (int) $item->subtotal,
                    ])
                    ->all(),
                'bill' => [
                    'subtotal' => $subtotal,
                    'discount' => $discount,
                    'tax_base' => $taxBase,
                    'tax' => $tax,
                    'tax_rate' => 10,
                    'total' => $total,
                ],
                'total_menu' => $order->items->unique('product_id')->count(),
                'total_pcs' => (int) $order->items->sum('quantity'),
            ],
        ]);
    }

    private function resolvePeriod(array $validated): array
    {
        $period = $validated['period'] ?? 'today';

        return match ($period) {
            '7days' => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
            'this_month' => [now()->startOfMonth(), now()->endOfDay()],
            'custom' => [
                Carbon::parse($validated['start_date'])->startOfDay(),
                Carbon::parse($validated['end_date'])->endOfDay(),
            ],
            default => [now()->startOfDay(), now()->endOfDay()],
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

    private function transformTransaction(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'completed_at' => $order->completed_at?->toISOString(),
            'table_number' => $order->table?->number,
            'order_type' => $order->order_type,
            'cashier' => $order->payment?->cashier === null ? null : [
                'id' => $order->payment->cashier->id,
                'name' => $order->payment->cashier->name,
            ],
            'subtotal' => (int) $order->subtotal,
            'discount' => (int) $order->discount,
            'tax' => (int) $order->tax,
            'total' => (int) $order->total,
            'payment_amount' => (int) ($order->payment?->amount ?? 0),
            'payment_method_type' => $order->payment?->paymentMethod?->type,
            'payment_method_name' => $order->payment?->paymentMethod?->name,
        ];
    }
}
