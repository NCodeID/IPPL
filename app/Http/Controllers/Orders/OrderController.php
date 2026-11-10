<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use App\Models\CashCategory;
use App\Models\CashTransaction;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Encryption\Encrypter;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class OrderController extends Controller
{
    private const DEFAULT_PER_PAGE = 15;

    private const MAX_PER_PAGE = 100;

    #[OA\Get(
        path: '/api/orders/monitor',
        operationId: 'orderMonitor',
        summary: 'Monitoring daftar order aktif untuk kasir dan runner',
        description: 'Menampilkan daftar order yang sedang berjalan dengan filter status dan pagination. Runner hanya melihat pending, cooking, dan ready. Kasir melihat pending, cooking, ready, dan served. Menyertakan total order aktif dan rincian per status.',
        tags: ['Orders'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(
                name: 'status',
                description: 'Filter status order: all, pending, cooking, ready, served, completed, cancelled',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['all', 'pending', 'cooking', 'ready', 'served', 'completed', 'cancelled']),
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
                description: 'Daftar order aktif dan statistik status',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'counts',
                            properties: [
                                new OA\Property(property: 'total', type: 'integer', example: 42),
                                new OA\Property(
                                    property: 'by_status',
                                    type: 'object',
                                    additionalProperties: new OA\AdditionalProperties(type: 'integer'),
                                ),
                            ],
                            type: 'object',
                        ),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 101),
                                    new OA\Property(property: 'order_number', type: 'string', example: 'ORD-20261024-143000-A1B2'),
                                    new OA\Property(property: 'table_number', type: 'integer', nullable: true, example: 5),
                                    new OA\Property(property: 'customer_name', type: 'string', example: 'Budi'),
                                    new OA\Property(property: 'status', type: 'string', example: 'cooking'),
                                    new OA\Property(property: 'items_count', type: 'integer', example: 3),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                ],
                                type: 'object',
                            ),
                        ),
                        new OA\Property(
                            property: 'links',
                            properties: [
                                new OA\Property(property: 'first', type: 'string', nullable: true),
                                new OA\Property(property: 'last', type: 'string', nullable: true),
                                new OA\Property(property: 'prev', type: 'string', nullable: true),
                                new OA\Property(property: 'next', type: 'string', nullable: true),
                            ],
                            type: 'object',
                        ),
                        new OA\Property(
                            property: 'meta',
                            properties: [
                                new OA\Property(property: 'current_page', type: 'integer'),
                                new OA\Property(property: 'from', type: 'integer', nullable: true),
                                new OA\Property(property: 'last_page', type: 'integer'),
                                new OA\Property(property: 'path', type: 'string'),
                                new OA\Property(property: 'per_page', type: 'integer'),
                                new OA\Property(property: 'to', type: 'integer', nullable: true),
                                new OA\Property(property: 'total', type: 'integer'),
                            ],
                            type: 'object',
                        ),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Tidak terautentikasi'),
            new OA\Response(response: 403, description: 'Tidak memiliki hak akses'),
            new OA\Response(response: 422, description: 'Parameter query tidak valid'),
        ],
    )]

    #[OA\Post(
        path: '/api/orders',
        operationId: 'orderStore',
        summary: 'Membuat order baru',
        description: 'Membuat order beserta item-nya. Runner dan kasir dine_in wajib membawa hold_token miliknya yang valid; hold dikonsumsi atomik bersama order dan stok. Role lain yang berizin tetap dapat memesan meja tanpa hold, tetapi tidak dapat melewati hold aktif. Takeaway tidak memerlukan hold.',
        tags: ['Orders'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['order_type', 'customer_name', 'items'],
                properties: [
                    new OA\Property(property: 'order_type', type: 'string', enum: ['dine_in', 'takeaway']),
                    new OA\Property(property: 'customer_name', type: 'string', example: 'Budi'),
                    new OA\Property(property: 'table_id', type: 'integer', nullable: true, description: 'Wajib untuk dine_in dan tidak boleh diisi untuk takeaway'),
                    new OA\Property(property: 'note', type: 'string', nullable: true, example: 'Tanpa pedas'),
                    new OA\Property(property: 'hold_token', type: 'string', format: 'password', minLength: 64, maxLength: 64, nullable: true, description: 'Wajib untuk Runner dan kasir dine_in; diperoleh dari POST /api/tables/{table}/hold'),
                    new OA\Property(
                        property: 'items',
                        type: 'array',
                        items: new OA\Items(
                            required: ['product_id', 'quantity'],
                            properties: [
                                new OA\Property(property: 'product_id', type: 'integer'),
                                new OA\Property(property: 'quantity', type: 'integer', minimum: 1),
                            ],
                            type: 'object',
                        ),
                    ),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Order berhasil dibuat',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'order_number', type: 'string'),
                                new OA\Property(property: 'customer_name', type: 'string'),
                                new OA\Property(property: 'order_type', type: 'string', enum: ['dine_in', 'takeaway']),
                                new OA\Property(property: 'status', type: 'string', example: 'pending'),
                                new OA\Property(property: 'note', type: 'string', nullable: true),
                                new OA\Property(property: 'cancellation_reason', type: 'string', nullable: true),
                                new OA\Property(
                                    property: 'table',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer'),
                                        new OA\Property(property: 'number', type: 'integer'),
                                    ],
                                    type: 'object',
                                    nullable: true,
                                ),
                                new OA\Property(
                                    property: 'runner',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer'),
                                        new OA\Property(property: 'name', type: 'string'),
                                    ],
                                    type: 'object',
                                ),
                                new OA\Property(
                                    property: 'chef',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer'),
                                        new OA\Property(property: 'name', type: 'string'),
                                    ],
                                    type: 'object',
                                    nullable: true,
                                ),
                                new OA\Property(property: 'subtotal', type: 'integer'),
                                new OA\Property(property: 'discount', type: 'integer'),
                                new OA\Property(property: 'tax', type: 'integer'),
                                new OA\Property(property: 'total', type: 'integer'),
                                new OA\Property(
                                    property: 'items',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'id', type: 'integer'),
                                            new OA\Property(
                                                property: 'product',
                                                properties: [
                                                    new OA\Property(property: 'id', type: 'integer'),
                                                    new OA\Property(property: 'name', type: 'string'),
                                                ],
                                                type: 'object',
                                            ),
                                            new OA\Property(property: 'unit_price', type: 'integer'),
                                            new OA\Property(property: 'quantity', type: 'integer'),
                                            new OA\Property(property: 'subtotal', type: 'integer'),
                                        ],
                                        type: 'object',
                                    ),
                                ),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                            ],
                            type: 'object',
                        ),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Tidak terautentikasi'),
            new OA\Response(response: 403, description: 'Tidak memiliki permission manage_orders atau akun tidak aktif'),
            new OA\Response(response: 422, description: 'Validasi gagal, meja tidak tersedia, produk tidak dapat dipesan, atau stok tidak mencukupi'),
            new OA\Response(response: 409, description: 'Runner atau kasir dine_in tidak memiliki hold/token yang cocok, atau meja memiliki hold aktif milik instance lain'),
            new OA\Response(response: 503, description: 'Penyimpanan hold tidak tersedia; order dine_in tidak dibuat'),
        ],
    )]
    #[OA\Get(
        path: '/api/orders/{order}',
        operationId: 'orderBillSummary',
        summary: 'Mengambil ringkasan bill pesanan',
        description: 'Mengembalikan detail pesanan dan rincian bill untuk role kasir dan runner. Harga item menggunakan harga transaksi yang tersimpan. Biaya layanan 5% dan pajak 10% dihitung dari subtotal item; endpoint hanya membaca data.',
        tags: ['Orders'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(
                name: 'order',
                description: 'ID pesanan dari field id pada daftar Pantau Pesanan',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 42),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Ringkasan bill pesanan',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 42),
                                new OA\Property(property: 'customer_name', type: 'string', example: 'Budi'),
                                new OA\Property(property: 'order_number', type: 'string', example: 'ORD-20261024-143000-A1B2'),
                                new OA\Property(property: 'order_type', type: 'string', enum: ['dine in', 'takeaway']),
                                new OA\Property(property: 'total_menu', type: 'integer', example: 2),
                                new OA\Property(property: 'total_item', type: 'integer', example: 3),
                                new OA\Property(property: 'note', type: 'string', example: 'Tidak ada'),
                                new OA\Property(
                                    property: 'items',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'menu_name', type: 'string', nullable: true),
                                            new OA\Property(property: 'unit_price', type: 'integer', example: 25000),
                                            new OA\Property(property: 'quantity', type: 'integer', example: 2),
                                            new OA\Property(property: 'subtotal', type: 'integer', example: 50000),
                                        ],
                                        type: 'object',
                                    ),
                                ),
                                new OA\Property(
                                    property: 'bill',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'subtotal', type: 'integer', example: 50000),
                                        new OA\Property(property: 'discount', type: 'integer', example: 0),
                                        new OA\Property(property: 'service_charge', type: 'integer', example: 2500),
                                        new OA\Property(property: 'service_rate', type: 'integer', example: 5),
                                        new OA\Property(property: 'tax', type: 'integer', example: 5000),
                                        new OA\Property(property: 'tax_rate', type: 'integer', example: 10),
                                        new OA\Property(property: 'tax_basis', type: 'string', enum: ['subtotal']),
                                        new OA\Property(property: 'total', type: 'integer', example: 57500),
                                    ],
                                ),
                            ],
                        ),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Tidak terautentikasi'),
            new OA\Response(response: 403, description: 'Akun tidak aktif, tidak memiliki permission, atau role bukan kasir/runner'),
            new OA\Response(response: 404, description: 'Pesanan tidak ditemukan'),
        ],
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_type' => ['required', 'string', 'in:dine_in,takeaway'],
            'customer_name' => ['required', 'string', 'max:255'],
            'table_id' => [
                'nullable',
                'integer',
                'exists:tables,id',
                'required_if:order_type,dine_in',
                'prohibited_if:order_type,takeaway',
            ],
            'note' => ['nullable', 'string', 'max:1000'],
            'hold_token' => ['nullable', 'string', 'size:64'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $runner = $request->user();

        $order = DB::transaction(function () use ($validated, $runner): Order {
            $table = null;

            if ($validated['order_type'] === 'dine_in') {
                $table = Table::query()
                    ->whereKey($validated['table_id'])
                    ->lockForUpdate()
                    ->first();

                if (! $table || ! $table->is_active) {
                    throw ValidationException::withMessages([
                        'table_id' => 'Meja tidak tersedia.',
                    ]);
                }

                $hold = $table->currentHold();
                if ($hold !== null || $runner->hasRole(['runner', 'kasir'])) {
                    abort_unless($runner->hasRole(['runner', 'kasir']) && $table->ownsHold($hold, $runner->id, $validated['hold_token'] ?? ''), 409, 'Hold meja tidak valid atau sudah berakhir.');
                }

                if ($table->activeOrders()->lockForUpdate()->exists()) {
                    throw ValidationException::withMessages([
                        'table_id' => 'Meja sedang digunakan.',
                    ]);
                }
            }

            $products = $this->reserveStock($validated['items']);

            $subtotal = 0;
            $orderItems = [];

            foreach ($validated['items'] as $item) {
                $product = $products->get((int) $item['product_id']);
                $quantity = (int) $item['quantity'];
                $unitPrice = (int) $product->price;
                $subtotal += $unitPrice * $quantity;

                $orderItems[] = [
                    'product_id' => $product->id,
                    'unit_price' => $unitPrice,
                    'quantity' => $quantity,
                ];
            }

            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'runner_id' => $runner->id,
                'table_id' => $table?->id,
                'customer_name' => $validated['customer_name'],
                'order_type' => $validated['order_type'],
                'status' => Order::STATUS_PENDING,
                'note' => $validated['note'] ?? null,
                'subtotal' => $subtotal,
                'discount' => 0,
                'tax' => 0,
            ]);

            $order->items()->createMany($orderItems);

            if ($table !== null && $hold !== null) {
                $table->deleteHold();
            }

            return $order->fresh(['items.product', 'table', 'runner', 'chef']);
        }, attempts: 3);

        return response()->json([
            'data' => $this->transformOrder($order),
        ], 201);
    }

    public function monitor(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasRole('runner')) {
            $allowedStatuses = Order::RUNNER_STATUSES;
        } elseif ($user->hasRole('kasir')) {
            $allowedStatuses = Order::CASHIER_STATUSES;
        } else {
            return response()->json(['message' => 'Tidak punya akses.'], 403);
        }

        $validated = $request->validate([
            'status' => [
                'nullable',
                'string',
                Rule::in([
                    'all',
                    Order::STATUS_PENDING,
                    Order::STATUS_COOKING,
                    Order::STATUS_READY,
                    Order::STATUS_SERVED,
                    Order::STATUS_COMPLETED,
                    Order::STATUS_CANCELLED,
                ]),
            ],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_PER_PAGE],
        ]);

        $baseCountQuery = Order::query()->whereIn('status', $allowedStatuses);

        $rawCounts = (clone $baseCountQuery)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->all();

        $byStatus = [];
        foreach ($allowedStatuses as $statusKey) {
            $byStatus[$statusKey] = (int) ($rawCounts[$statusKey] ?? 0);
        }

        $ordersQuery = Order::query()
            ->select(['id', 'order_number', 'table_id', 'customer_name', 'status', 'created_at'])
            ->whereIn('status', $allowedStatuses)
            ->with(['table:id,number'])
            ->withSum('items as items_count', 'quantity');

        $statusFilter = filled($validated['status'] ?? null) ? $validated['status'] : 'all';
        if ($statusFilter !== 'all') {
            $ordersQuery->where('status', $statusFilter);
        }

        $orders = $ordersQuery
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(perPage: (int) ($validated['per_page'] ?? self::DEFAULT_PER_PAGE));

        return response()->json([
            'counts' => [
                'total' => array_sum($byStatus),
                'by_status' => $byStatus,
            ],
            'data' => $orders->getCollection()
                ->map(fn (Order $order): array => $this->transformCard($order))
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
                'links' => $orders->linkCollection()->toArray(),
                'path' => $orders->path(),
                'per_page' => $orders->perPage(),
                'to' => $orders->lastItem(),
                'total' => $orders->total(),
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/cashier/checkout',
        operationId: 'cashierCheckout',
        summary: 'Dashboard order belum dibayar dan antrean checkout served',
        description: 'Read-only untuk kasir/admin aktif dengan manage_orders DAN manage_payments. Unpaid berarti tanpa payment success; pending tetap unpaid dan memblokir kesiapan inisiasi. Semua nominal adalah preview IDR, bukan quote beku atau otorisasi pembayaran. Summary independen dari view/pagination. Tidak membuat intent, menyimpan biaya atau menyelesaikan order.',
        tags: ['Orders'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(name: 'view', in: 'query', schema: new OA\Schema(type: 'string', enum: ['all_unpaid', 'served'], default: 'served')),
            new OA\QueryParameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1)),
            new OA\QueryParameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Preview checkout dengan urutan created_at DESC, id DESC', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'view', type: 'string'),
                    new OA\Property(property: 'pricing', type: 'object', properties: [
                        new OA\Property(property: 'currency', type: 'string', example: 'IDR'),
                        new OA\Property(property: 'kind', type: 'string', example: 'preview'),
                    ]),
                    new OA\Property(property: 'summary', type: 'object', description: 'all_unpaid, served, ready_to_initiate: masing-masing count dan preview_total. Ready adalah served tanpa pending attempt.'),
                    new OA\Property(property: 'anomalies', type: 'object', properties: [
                        new OA\Property(property: 'completed_without_success_count', type: 'integer'),
                    ]),
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object', properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'order_number', type: 'string'),
                        new OA\Property(property: 'customer_name', type: 'string'),
                        new OA\Property(property: 'order_type', type: 'string', enum: ['dine_in', 'takeaway']),
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'table', type: 'object', nullable: true, description: 'id dan number bila terkait meja'),
                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                        new OA\Property(property: 'bill_preview', type: 'object', description: 'Rincian bill sama dengan detail order; subtotal dari item historis, service/tax independen, bukan orders.total provisional.'),
                        new OA\Property(property: 'payment_in_progress', type: 'boolean'),
                        new OA\Property(property: 'can_initiate_payment', type: 'boolean', description: 'Kesiapan menurut status/attempt saat dibaca, bukan jaminan transaksi atau endpoint pembayaran yang sudah tersedia.'),
                    ])),
                    new OA\Property(property: 'links', type: 'object'),
                    new OA\Property(property: 'meta', type: 'object'),
                ],
            )),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Inactive, wrong role, or missing either permission'),
            new OA\Response(response: 422, description: 'Invalid view or pagination'),
        ],
    )]
    public function checkout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'view' => ['nullable', 'string', Rule::in(['all_unpaid', 'served'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_PER_PAGE],
        ]);
        $view = $validated['view'] ?? 'served';
        $perPage = (int) ($validated['per_page'] ?? self::DEFAULT_PER_PAGE);
        $successful = fn (Builder $query): Builder => $query->where('status', 'success');
        $query = Order::query()
            ->select(['id', 'order_number', 'table_id', 'customer_name', 'order_type', 'status', 'discount', 'created_at'])
            ->active()
            ->whereDoesntHave('payments', $successful)
            ->withSum('items as item_subtotal', 'subtotal')
            ->withExists(['payments as payment_in_progress' => fn (Builder $query): Builder => $query->where('status', 'pending')]);

        $empty = ['count' => 0, 'preview_total' => 0];
        $summary = ['all_unpaid' => $empty, 'served' => $empty, 'ready_to_initiate' => $empty];
        foreach ((clone $query)->cursor() as $order) {
            $due = $this->previewBill((int) $order->item_subtotal, (int) $order->discount)['total'];
            $groups = ['all_unpaid'];
            if ($order->status === Order::STATUS_SERVED) {
                $groups[] = 'served';
                if (! $order->payment_in_progress) {
                    $groups[] = 'ready_to_initiate';
                }
            }
            foreach ($groups as $group) {
                $summary[$group]['count']++;
                $summary[$group]['preview_total'] += $due;
            }
        }

        $ordersQuery = (clone $query)->with(['table:id,number']);
        if ($view === 'served') {
            $ordersQuery->where('status', Order::STATUS_SERVED);
        }
        $orders = $ordersQuery->orderByDesc('created_at')->orderByDesc('id')
            ->paginate(perPage: $perPage)->appends(['view' => $view, 'per_page' => $perPage]);

        return response()->json([
            'view' => $view,
            'pricing' => ['currency' => 'IDR', 'kind' => 'preview'],
            'summary' => $summary,
            'anomalies' => ['completed_without_success_count' => Order::query()
                ->where('status', Order::STATUS_COMPLETED)->whereDoesntHave('payments', $successful)->count()],
            'data' => $orders->getCollection()->map(fn (Order $order): array => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->customer_name,
                'order_type' => $order->order_type,
                'status' => $order->status,
                'table' => $order->table === null ? null : ['id' => $order->table->id, 'number' => $order->table->number],
                'created_at' => $order->created_at?->toISOString(),
                'bill_preview' => $this->previewBill((int) $order->item_subtotal, (int) $order->discount),
                'payment_in_progress' => (bool) $order->payment_in_progress,
                'can_initiate_payment' => $order->status === Order::STATUS_SERVED && ! $order->payment_in_progress,
            ])->all(),
            'links' => [
                'first' => $orders->url(1), 'last' => $orders->url($orders->lastPage()),
                'prev' => $orders->previousPageUrl(), 'next' => $orders->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $orders->currentPage(), 'from' => $orders->firstItem(),
                'last_page' => $orders->lastPage(), 'links' => $orders->linkCollection()->toArray(),
                'path' => $orders->path(), 'per_page' => $orders->perPage(),
                'to' => $orders->lastItem(), 'total' => $orders->total(),
            ],
        ]);
    }

    public function show(Order $order): JsonResponse
    {
        $order->load('items.product:id,name');

        $itemSubtotal = (int) $order->items->sum(fn (OrderItem $item): int => (int) $item->subtotal);

        return response()->json([
            'data' => [
                'id' => $order->id,
                'customer_name' => $order->customer_name,
                'order_number' => $order->order_number,
                'order_type' => $order->order_type === 'dine_in' ? 'dine in' : 'takeaway',
                'total_menu' => $order->items->unique('product_id')->count(),
                'total_item' => (int) $order->items->sum('quantity'),
                'note' => $order->note ?: 'Tidak ada',
                'items' => $order->items->map(fn (OrderItem $item): array => [
                    'menu_name' => $item->product?->name,
                    'unit_price' => (int) $item->unit_price,
                    'quantity' => (int) $item->quantity,
                    'subtotal' => (int) $item->subtotal,
                ])->all(),
                'bill' => $this->previewBill($itemSubtotal, (int) $order->discount),
            ],
        ]);
    }

    #[OA\Post(
        path: '/api/orders/{order}/serve',
        operationId: 'orderServe',
        summary: 'Runner menandai pesanan ready sebagai served',
        description: 'Memerlukan akun aktif, role runner dan permission manage_orders. Runner tidak harus pembuat pesanan. Berlaku untuk dine_in dan takeaway; hanya ready ke served, tanpa request body. Permintaan berulang atau status lain ditolak 409.',
        tags: ['Orders'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'order', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Pesanan berhasil disajikan', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', type: 'object', properties: [
                    new OA\Property(property: 'id', type: 'integer'),
                    new OA\Property(property: 'status', type: 'string', enum: ['served']),
                ]),
            ])),
            new OA\Response(response: 401, description: 'Tidak terautentikasi'),
            new OA\Response(response: 403, description: 'Akun tidak aktif, bukan runner, atau tidak memiliki manage_orders'),
            new OA\Response(response: 404, description: 'Pesanan tidak ditemukan'),
            new OA\Response(response: 409, description: 'Status pesanan bukan ready, termasuk sudah served'),
        ],
    )]
    public function serve(Order $order): JsonResponse
    {
        return DB::transaction(function () use ($order): JsonResponse {
            $updated = DB::table('orders')
                ->where('id', $order->id)
                ->where('status', Order::STATUS_READY)
                ->update(['status' => Order::STATUS_SERVED]);

            abort_unless($updated === 1, 409, 'Pesanan tidak siap untuk disajikan.');

            return response()->json(['data' => ['id' => $order->id, 'status' => Order::STATUS_SERVED]]);
        }, attempts: 3);
    }

    private function reserveStock(array $items): Collection
    {
        $quantities = [];

        foreach ($items as $item) {
            $productId = (int) $item['product_id'];
            $quantities[$productId] = ($quantities[$productId] ?? 0) + (int) $item['quantity'];
        }

        $products = Product::query()
            ->with(['category', 'recipes.ingredient'])
            ->whereIn('id', array_keys($quantities))
            ->get()
            ->keyBy('id');

        $errors = [];
        $demand = [];
        $ingredientNames = [];

        foreach ($quantities as $productId => $quantity) {
            $product = $products->get($productId);

            if (! $product) {
                $errors[] = "Produk dengan ID {$productId} tidak ditemukan.";

                continue;
            }

            $evaluation = $this->evaluateOrderability($product, $quantity);

            if (! $evaluation['is_orderable']) {
                $errors[] = $this->unavailableMessage($product->name, $evaluation['unavailable_reason']);

                continue;
            }

            foreach ($product->recipes as $recipe) {
                $ingredientId = $recipe->ingredient_id;
                $required = bcmul((string) $recipe->quantity_required, (string) $quantity, 2);
                $demand[$ingredientId] = bcadd($demand[$ingredientId] ?? '0', $required, 2);
                $ingredientNames[$ingredientId] = $recipe->ingredient->name;
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages(['items' => $errors]);
        }

        if ($demand === []) {
            return $products;
        }

        $ingredientIds = array_keys($demand);
        sort($ingredientIds);

        $ingredients = Ingredient::query()
            ->whereIn('id', $ingredientIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $stockErrors = [];

        foreach ($demand as $ingredientId => $required) {
            $ingredient = $ingredients->get($ingredientId);

            if (! $ingredient || ! $ingredient->is_active) {
                $stockErrors[] = "Bahan baku {$ingredientNames[$ingredientId]} tidak dapat digunakan.";

                continue;
            }

            if (bccomp($required, (string) $ingredient->stock, 2) > 0) {
                $stockErrors[] = "Stok {$ingredient->name} tidak mencukupi untuk pesanan ini.";
            }
        }

        if ($stockErrors !== []) {
            throw ValidationException::withMessages(['stock' => $stockErrors]);
        }

        foreach ($demand as $ingredientId => $required) {
            $affected = DB::update(
                'UPDATE ingredients SET stock = stock - ? WHERE id = ? AND stock >= ?',
                [$required, $ingredientId, $required],
            );

            if ($affected === 0) {
                throw ValidationException::withMessages([
                    'stock' => ["Stok {$ingredientNames[$ingredientId]} tidak mencukupi untuk pesanan ini."],
                ]);
            }
        }

        return $products;
    }

    private function evaluateOrderability(Product $product, int $quantity = 1): array
    {
        if (! $product->is_active) {
            return $this->unavailable('product_inactive');
        }

        if (! $product->category || ! $product->category->is_active) {
            return $this->unavailable('category_inactive');
        }

        if (! $product->is_available) {
            return $this->unavailable('not_available');
        }

        $recipes = $product->recipes;

        if ($recipes->isEmpty()) {
            return $this->unavailable('recipe_missing');
        }

        foreach ($recipes as $recipe) {
            if (! $recipe->ingredient || bccomp((string) $recipe->quantity_required, '0', 2) <= 0) {
                return $this->unavailable('recipe_invalid');
            }
        }

        foreach ($recipes as $recipe) {
            if (! $recipe->ingredient->is_active) {
                return $this->unavailable('ingredient_inactive');
            }
        }

        foreach ($recipes as $recipe) {
            $required = bcmul((string) $recipe->quantity_required, (string) $quantity, 2);

            if (bccomp($required, (string) $recipe->ingredient->stock, 2) > 0) {
                return $this->unavailable('insufficient_stock');
            }
        }

        return [
            'is_orderable' => true,
            'unavailable_reason' => null,
        ];
    }

    #[OA\Post(
        path: '/api/orders/{order}/payments',
        operationId: 'orderCashPayment',
        summary: 'Pembayaran penuh CASH atau QRIS-only Snap Sandbox',
        description: 'Kasir/admin aktif dengan manage_orders DAN manage_payments. CASH: method=cash dan tendered_amount JSON integer, success/gross/completed atomik. QRIS: hanya method=qris, intent pending dan quote dibekukan sebelum HTTP; success hanya dari verifikasi provider. Replay CASH tetap; QRIS token hanya untuk aktor awal. Riwayat ambigu memblokir CASH. Tanpa split bill atau fee provider asumsi.',
        tags: ['Orders'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'order', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            type: 'object', required: ['method'], properties: [
                new OA\Property(property: 'method', type: 'string', enum: ['cash', 'qris']),
                new OA\Property(property: 'tendered_amount', type: 'integer', minimum: 0, maximum: PHP_INT_MAX, example: 200000, description: 'JSON integer; bukan string numerik, boolean atau pecahan'),
            ], additionalProperties: false, description: 'CASH wajib tendered_amount; QRIS melarang tendered_amount dan seluruh kolom tambahan.',
        )),
        responses: [
            new OA\Response(response: 201, description: 'CASH: success/pemasukan/completed atomik. QRIS: pending dengan quote beku dan checkout token/redirect Sandbox, belum pelunasan. Respons memakai data.payment/order; QRIS menyertakan quote, fee UNKNOWN/null dan reconciliation_required'),
            new OA\Response(response: 200, description: 'Replay setara; fakta hasil asli, tanpa penulisan keuangan baru'),
            new OA\Response(response: 202, description: 'QRIS pending tanpa token yang terbukti; rekonsiliasi diperlukan, jangan buat charge pengganti'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Inactive, wrong role or missing either permission'),
            new OA\Response(response: 404, description: 'Order not found'),
            new OA\Response(response: 409, description: 'Ineligible/ambiguous/conflicting financial state or unavailable CASH/sales configuration'),
            new OA\Response(response: 422, description: 'Invalid input/extra fields/insufficient tender or unsupported integer amount'),
            new OA\Response(response: 503, description: 'Konfigurasi Sandbox/provider/pencatatan belum tersedia; pending yang sudah dibuat tidak dibatalkan'),
        ],
    )]
    public function payCash(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'method' => ['required', 'string', Rule::in(['cash'])],
            'tendered_amount' => ['required', 'integer:strict', 'min:0', 'max:'.PHP_INT_MAX],
        ]);
        $unexpected = array_diff(array_keys($request->all()), ['method', 'tendered_amount']);
        if ($unexpected !== []) {
            throw ValidationException::withMessages(array_fill_keys($unexpected, 'Kolom ini tidak diperbolehkan.'));
        }
        $tender = $validated['tendered_amount'];
        $actorId = (int) $request->user()->id;
        $tableId = $order->table_id === null ? null : (int) $order->table_id;

        return DB::transaction(function () use ($order, $tableId, $tender, $actorId): JsonResponse {
            $table = $tableId === null ? null : Table::query()->whereKey($tableId)->lockForUpdate()->first();
            $current = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $currentTableId = $current->table_id === null ? null : (int) $current->table_id;
            abort_unless($currentTableId === $tableId && ($tableId === null || $table !== null), 409, 'Relasi meja berubah; muat ulang pesanan.');
            $attempts = $current->payments()->orderBy('id')->lockForUpdate()->get();
            abort_if($attempts->contains('status', 'pending'), 409, 'Percobaan pembayaran belum terselesaikan; rekonsiliasi diperlukan.');
            $accepted = $attempts->firstWhere('status', 'success');

            if ($accepted !== null) {
                $method = PaymentMethod::query()->whereKey($accepted->payment_method_id)->sharedLock()->first();
                $ledger = CashTransaction::query()->where('payment_id', $accepted->id)->lockForUpdate()->first();
                $category = $ledger === null ? null : CashCategory::query()->whereKey($ledger->cash_category_id)->sharedLock()->first();
                $due = (int) $accepted->amount;
                abort_unless(
                    $current->status === Order::STATUS_COMPLETED && $current->completed_at !== null
                    && $accepted->paid_at !== null && $accepted->paid_at->equalTo($current->completed_at)
                    && $method?->type === 'cash' && (int) $accepted->cashier_id === $actorId
                    && (string) $accepted->getRawOriginal('tendered_amount') === (string) $tender && $tender >= $due
                    && (string) $accepted->getRawOriginal('amount') === (string) $due
                    && (string) $current->getRawOriginal('total') === (string) $due
                    && (string) $accepted->getRawOriginal('change_amount') === (string) ($tender - $due)
                    && filled($accepted->payment_reference)
                    && $ledger !== null && $category?->type === 'in'
                    && (string) $ledger->getRawOriginal('amount') === (string) $due
                    && (int) $ledger->recorded_by === $actorId && $ledger->fee_payment_id === null
                    && $ledger->purchase_request_id === null && $ledger->transacted_at->equalTo($accepted->paid_at),
                    409, 'Pembayaran yang diterima tidak cocok dengan replay atau fakta keuangannya tidak konsisten.',
                );

                return $this->cashPaymentResponse($current, $accepted, 200);
            }

            abort_unless($current->status === Order::STATUS_SERVED, 409, 'Pesanan belum memenuhi syarat pembayaran.');
            abort_unless((int) $current->discount === 0, 409, 'Diskon lama memerlukan peninjauan; pembayaran tidak diubah.');
            abort_if($attempts->isNotEmpty(), 409, 'Quote dari percobaan lama tidak terbukti beku; rekonsiliasi diperlukan.');
            $subtotal = 0;
            $limit = intdiv(PHP_INT_MAX - 50, 10);
            foreach ($current->items()->lockForUpdate()->get() as $item) {
                $lineSubtotal = (int) $item->subtotal;
                if ($lineSubtotal > $limit - $subtotal) {
                    throw ValidationException::withMessages(['items' => 'Nilai pesanan melampaui batas perhitungan integer.']);
                }
                $subtotal += $lineSubtotal;
            }
            $bill = $this->previewBill($subtotal, 0);
            if ($tender < $bill['total']) {
                throw ValidationException::withMessages(['tendered_amount' => 'Uang diterima kurang dari total tagihan.']);
            }
            $method = PaymentMethod::query()->where('type', 'cash')->where('is_active', true)->orderBy('id')->sharedLock()->first();
            $category = CashCategory::query()->where('name', 'Penjualan')->where('type', 'in')->where('is_active', true)->sharedLock()->first();
            abort_if($method === null || $category === null, 409, 'Metode CASH atau kategori Penjualan tidak tersedia/valid.');

            // Avoid normalizing unrelated legacy attributes through Order::saving.
            DB::table('orders')->where('id', $current->id)->update([
                'subtotal' => $subtotal, 'discount' => 0, 'service_charge' => $bill['service_charge'],
                'tax' => $bill['tax'], 'updated_at' => now(),
            ]);
            $current->refresh();
            abort_unless((int) $current->total === $bill['total'], 409, 'Total tersimpan tidak sesuai tagihan server.');
            $paidAt = now();
            $payment = Payment::create([
                'order_id' => $current->id, 'payment_method_id' => $method->id, 'cashier_id' => $actorId,
                'amount' => $bill['total'], 'tendered_amount' => $tender, 'change_amount' => $tender - $bill['total'],
                'status' => 'success', 'payment_reference' => 'CASH-'.Str::uuid(), 'paid_at' => $paidAt,
            ]);
            CashTransaction::create([
                'cash_category_id' => $category->id, 'payment_id' => $payment->id, 'recorded_by' => $actorId,
                'amount' => $bill['total'], 'description' => 'Pembayaran tunai '.$current->order_number,
                'transacted_at' => $paidAt,
            ]);
            DB::table('orders')->where('id', $current->id)->update([
                'status' => Order::STATUS_COMPLETED, 'completed_at' => $paidAt, 'updated_at' => $paidAt,
            ]);

            return $this->cashPaymentResponse($current->fresh(), $payment->fresh(), 201);
        }, attempts: 3);
    }

    private function cashPaymentResponse(Order $order, Payment $payment, int $status): JsonResponse
    {
        return response()->json(['data' => [
            'payment' => [
                'id' => $payment->id, 'reference' => $payment->payment_reference,
                'method' => 'cash', 'currency' => 'IDR', 'status' => $payment->status,
                'amount' => $payment->amount, 'tendered_amount' => $payment->tendered_amount,
                'change_amount' => $payment->change_amount, 'paid_at' => $payment->paid_at?->toISOString(),
            ],
            'order' => [
                'id' => $order->id, 'order_number' => $order->order_number,
                'status' => $order->status, 'completed_at' => $order->completed_at?->toISOString(),
            ],
        ]], $status);
    }

    public function pay(Request $request, Order $order): JsonResponse
    {
        if ($request->input('method') === 'qris') {
            try {
                return $this->initiateQris($request, $order);
            } catch (\PDOException) {
                abort(503, 'Pencatatan belum berhasil; rekonsiliasi diperlukan.');
            }
        }

        return $this->payCash($request, $order);
    }

    private function qrisSettings(): array
    {
        $settings = config('services.midtrans', []);
        $key = config('app.key');
        abort_unless(is_string($key), 503, 'Konfigurasi Sandbox tidak tersedia.');
        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7), true);
        }
        abort_unless(is_string($key) && Encrypter::supported($key, config('app.cipher')), 503, 'Konfigurasi Sandbox tidak tersedia.');
        abort_unless(is_string($settings['server_key'] ?? null) && str_starts_with($settings['server_key'], 'SB-Mid-server-')
            && is_string($settings['merchant_id'] ?? null) && preg_match('/\A[A-Za-z0-9_-]{1,64}\z/', $settings['merchant_id'])
            && preg_match('/\A[1-9][0-9]*\z/', (string) ($settings['payment_method_id'] ?? '')), 503, 'Konfigurasi Sandbox tidak tersedia.');
        $settings['quote_key'] = hash_hkdf('sha256', $key, 32, 'pos:midtrans:sandbox:quote:v1');

        return $settings;
    }

    private function qrisReference(Order $order, Payment $payment, string $nonce, array $settings): string
    {
        $quote = array_map(fn (string $field): string => (string) $order->getRawOriginal($field), ['subtotal', 'discount', 'service_charge', 'tax', 'total']);
        $message = json_encode(['QSB1', 'sandbox', $settings['merchant_id'], (string) $order->id,
            (string) $payment->payment_method_id, $nonce, ...$quote, (string) $payment->getAttributes()['amount']], JSON_THROW_ON_ERROR);
        $tag = rtrim(strtr(base64_encode(substr(hash_hmac('sha256', $message, $settings['quote_key'], true), 0, 16)), '+/', '-_'), '=');

        return 'QSB1-'.$nonce.'-'.$tag;
    }

    private function assertQrisQuote(Order $order, Payment $payment, array $settings): void
    {
        $reference = $payment->payment_reference;
        abort_unless(is_string($reference) && preg_match('/\AQSB1-([A-Za-z0-9_-]{22})-[A-Za-z0-9_-]{22}\z/', $reference, $match)
            && hash_equals($this->qrisReference($order, $payment, $match[1], $settings), $reference)
            && (string) $order->getRawOriginal('discount') === '0'
            && (string) $order->getRawOriginal('total') === (string) $payment->getAttributes()['amount'], 409, 'Quote tidak terbukti atau tidak konsisten; rekonsiliasi diperlukan.');
    }

    private function withQrisOrder(Order $order, \Closure $action): mixed
    {
        $tableId = $order->table_id === null ? null : (int) $order->table_id;

        return DB::transaction(function () use ($order, $tableId, $action): mixed {
            $table = $tableId === null ? null : Table::query()->whereKey($tableId)->lockForUpdate()->first();
            $current = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            abort_unless(($current->table_id === null ? null : (int) $current->table_id) === $tableId
                && ($tableId === null || $table !== null), 409, 'Relasi meja berubah; muat ulang pesanan.');

            return $action($current, $current->payments()->orderBy('id')->lockForUpdate()->get());
        }, attempts: 3);
    }

    private function initiateQris(Request $request, Order $order): JsonResponse
    {
        if (array_diff(array_keys($request->all()), ['method']) !== []) {
            throw ValidationException::withMessages(['method' => 'QRIS hanya menerima method.']);
        }
        $settings = $this->qrisSettings();
        $actor = (int) $request->user()->id;
        [$current, $payment, $created] = $this->withQrisOrder($order, function (Order $current, Collection $attempts) use ($settings, $actor): array {
            foreach ($attempts as $attempt) {
                $this->assertQrisQuote($current, $attempt, $settings);
            }
            $accepted = $attempts->firstWhere('status', 'success');
            if ($accepted !== null) {
                abort_unless((int) $accepted->cashier_id === $actor, 409, 'Percobaan milik kasir lain.');
                $this->assertQrisSuccess($current, $accepted);

                return [$current, $accepted, false];
            }
            abort_unless($current->status === Order::STATUS_SERVED, 409, 'Pesanan belum memenuhi syarat pembayaran.');
            $pending = $attempts->where('status', 'pending');
            abort_if($pending->count() > 1, 409, 'Riwayat pembayaran ambigu.');
            if ($pending->isNotEmpty()) {
                $payment = $pending->first();
                abort_unless((int) $payment->cashier_id === $actor, 409, 'Percobaan milik kasir lain.');

                return [$current, $payment, false];
            }
            $method = PaymentMethod::query()->whereKey($settings['payment_method_id'])->sharedLock()->first();
            $category = CashCategory::query()->where('name', 'Penjualan')->where('type', 'in')->where('is_active', true)->sharedLock()->first();
            abort_unless($method?->type === 'qris' && $method->is_active && $category !== null, 503, 'Metode QRIS/kategori pemasukan tidak valid.');
            if ($attempts->isEmpty()) {
                abort_unless((int) $current->discount === 0, 409, 'Diskon lama memerlukan rekonsiliasi.');
                $subtotal = 0;
                $limit = intdiv(PHP_INT_MAX - 50, 10);
                foreach ($current->items()->lockForUpdate()->get() as $item) {
                    abort_if((int) $item->subtotal > $limit - $subtotal, 422, 'Nilai pesanan melampaui batas integer.');
                    $subtotal += (int) $item->subtotal;
                }
                $bill = $this->previewBill($subtotal, 0);
                abort_unless($bill['total'] > 0, 422, 'Nilai QRIS harus positif.');
                DB::table('orders')->where('id', $current->id)->update([
                    'subtotal' => $subtotal, 'discount' => 0, 'service_charge' => $bill['service_charge'], 'tax' => $bill['tax'], 'updated_at' => now(),
                ]);
                $current->refresh();
                abort_unless($current->total === $bill['total'], 409, 'Total tersimpan tidak sesuai tagihan.');
            }
            $payment = new Payment(['order_id' => $current->id, 'payment_method_id' => $method->id, 'cashier_id' => $actor,
                'amount' => $current->total, 'status' => 'pending']);
            $nonce = rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=');
            $payment->payment_reference = $this->qrisReference($current, $payment, $nonce, $settings);
            $payment->save();

            return [$current, $payment, true];
        });
        if (! $created) {
            return $this->qrisResponse($current, $payment, $settings, $payment->status === 'success' || $payment->snap_token !== null ? 200 : 202, true);
        }
        try {
            $response = $this->qrisHttp($settings)->post('https://app.sandbox.midtrans.com/snap/v1/transactions', [
                'transaction_details' => ['order_id' => $payment->payment_reference, 'gross_amount' => $payment->amount],
                'enabled_payments' => ['other_qris'], 'customer_imposed_payment_fee' => ['enable' => false],
            ]);
        } catch (ConnectionException) {
            // shortcut: lost responses stay pending; recovery needs a reviewed dispatch protocol.
            return $this->qrisResponse($current, $payment, $settings, 202);
        }
        $token = $response->json('token');
        if ($response->status() !== 201 || ! is_string($token) || ! preg_match('/\A[A-Za-z0-9_-]{1,255}\z/', $token)
            || $response->json('redirect_url') !== 'https://app.sandbox.midtrans.com/snap/v2/vtweb/'.$token) {
            return $this->qrisResponse($current, $payment, $settings, $response->serverError() || $response->status() === 401 ? 503 : 202);
        }

        return $this->withQrisOrder($current, function (Order $locked, Collection $attempts) use ($payment, $actor, $token, $settings): JsonResponse {
            $stored = $attempts->firstWhere('id', $payment->id);
            abort_unless($stored !== null && (int) $stored->cashier_id === $actor && $stored->payment_reference === $payment->payment_reference, 409, 'Percobaan berubah.');
            $this->assertQrisQuote($locked, $stored, $settings);
            if ($stored->status === 'success') {
                $this->assertQrisSuccess($locked, $stored);

                return $this->qrisResponse($locked, $stored, $settings, 200);
            }
            abort_unless($stored->status === 'pending' && $locked->status === Order::STATUS_SERVED
                && ! $attempts->contains('status', 'success') && ($stored->snap_token === null || $stored->snap_token === $token), 409, 'Percobaan berubah.');
            if ($stored->snap_token === null) {
                $stored->update(['snap_token' => $token]);
            }

            return $this->qrisResponse($locked, $stored, $settings, 201, true);
        });
    }

    private function qrisHttp(array $settings): PendingRequest
    {
        return Http::withBasicAuth($settings['server_key'], '')->acceptJson()->asJson()
            ->connectTimeout(3)->timeout(10)->withoutRedirecting()->withOptions(['verify' => true]);
    }

    #[OA\Get(
        path: '/api/orders/{order}/payments/{payment}', operationId: 'qrisPaymentStatus',
        summary: 'Status QRIS lokal tanpa HTTP provider atau token checkout', tags: ['Orders'], security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'order', required: true, schema: new OA\Schema(type: 'integer')), new OA\PathParameter(name: 'payment', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'data.payment/order/quote; fee UNKNOWN/null, reconciliation_required; kasir/admin aktif dengan kedua permission'), new OA\Response(response: 401, description: 'Guest'), new OA\Response(response: 403, description: 'Akses ditolak'), new OA\Response(response: 404, description: 'Order/payment tidak cocok'), new OA\Response(response: 409, description: 'Provenance tidak valid'), new OA\Response(response: 503, description: 'Konfigurasi tidak tersedia')],
    )]
    public function showPayment(Order $order, Payment $payment): JsonResponse
    {
        abort_unless((int) $payment->order_id === (int) $order->id, 404);

        return $this->qrisResponse($order, $payment, $this->qrisSettings(), 200);
    }

    #[OA\Post(
        path: '/api/orders/{order}/payments/{payment}/reconcile', operationId: 'qrisPaymentReconcile',
        summary: 'Rekonsiliasi request-driven melalui Status Sandbox, tanpa membuat token/charge', tags: ['Orders'], security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'order', required: true, schema: new OA\Schema(type: 'integer')), new OA\PathParameter(name: 'payment', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Status terverifikasi; success/gross/completed atomik atau no-op'), new OA\Response(response: 202, description: 'Status belum terbukti, termasuk Snap 404'), new OA\Response(response: 401, description: 'Guest'), new OA\Response(response: 403, description: 'Akses ditolak'), new OA\Response(response: 404, description: 'Order/payment tidak cocok'), new OA\Response(response: 409, description: 'Konflik finansial/provenance'), new OA\Response(response: 422, description: 'Rekonsiliasi tidak menerima input pembayaran'), new OA\Response(response: 503, description: 'Provider/pencatatan belum tersedia')],
    )]
    public function reconcileQris(Request $request, Order $order, Payment $payment): JsonResponse
    {
        abort_unless((int) $payment->order_id === (int) $order->id, 404);
        if ($request->all() !== []) {
            throw ValidationException::withMessages(['payment' => 'Rekonsiliasi tidak menerima input pembayaran.']);
        }

        return $this->verifyQris($order, $payment, $this->qrisSettings(), false);
    }

    #[OA\Post(
        path: '/api/payments/midtrans/notifications', operationId: 'qrisMidtransNotification',
        summary: 'Webhook publik: signature SHA512 dan verifikasi Status Sandbox independen', tags: ['Orders'], security: [],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'object', required: ['order_id', 'status_code', 'gross_amount', 'signature_key'], properties: [new OA\Property(property: 'order_id', type: 'string'), new OA\Property(property: 'status_code', type: 'string'), new OA\Property(property: 'gross_amount', type: 'string'), new OA\Property(property: 'signature_key', type: 'string')], additionalProperties: true)),
        responses: [new OA\Response(response: 200, description: 'Status tersimpan atau duplikat terverifikasi'), new OA\Response(response: 202, description: 'State ambigu tetap aman; tinjau manual'), new OA\Response(response: 400, description: 'Payload tidak valid'), new OA\Response(response: 403, description: 'Signature tidak valid'), new OA\Response(response: 404, description: 'Reference tidak ditemukan'), new OA\Response(response: 409, description: 'Konflik integritas'), new OA\Response(response: 503, description: 'Verifikasi/pencatatan gagal; retry diperlukan')],
    )]
    public function notifyQris(Request $request): JsonResponse
    {
        $settings = $this->qrisSettings();
        try {
            // Request input has already passed TrimStrings; signatures require the original values.
            $payload = json_decode($request->getContent(), true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            abort(400, 'Notifikasi tidak valid.');
        }
        abort_unless(is_array($payload), 400, 'Notifikasi tidak valid.');
        foreach (['order_id', 'status_code', 'gross_amount', 'signature_key'] as $field) {
            abort_unless(is_string($payload[$field] ?? null) && strlen($payload[$field]) <= 255, 400, 'Notifikasi tidak valid.');
        }
        abort_unless(preg_match('/\A[0-9]{3}\z/', $payload['status_code'])
            && preg_match('/\A[0-9]+(?:\.[0-9]{1,2})?\z/', $payload['gross_amount']), 400, 'Notifikasi tidak valid.');
        $signature = hash('sha512', $payload['order_id'].$payload['status_code'].$payload['gross_amount'].$settings['server_key']);
        abort_unless(hash_equals($signature, $payload['signature_key']), 403, 'Signature tidak valid.');
        $payment = Payment::query()->where('payment_reference', $payload['order_id'])->firstOrFail();
        abort_unless($payment->payment_reference === $payload['order_id'], 404);
        abort_unless(preg_match('/\A(0|[1-9][0-9]*)(?:\.0{1,2})?\z/', $payload['gross_amount'], $amount)
            && $amount[1] === (string) $payment->getRawOriginal('amount'), 409, 'Nominal notifikasi tidak cocok.');

        return $this->verifyQris($payment->order, $payment, $settings, true);
    }

    private function verifyQris(Order $order, Payment $payment, array $settings, bool $notification): JsonResponse
    {
        $this->assertQrisQuote($order, $payment, $settings);
        try {
            $response = $this->qrisHttp($settings)->get('https://api.sandbox.midtrans.com/v2/'.$payment->payment_reference.'/status');
        } catch (ConnectionException) {
            abort(503, 'Verifikasi provider belum tersedia.');
        }
        if ($response->status() === 404 || ($response->successful() && $response->json('status_code') === '404')) {
            abort_if($notification, 503, 'Status provider belum tersedia.');

            return $this->qrisResponse($order->fresh(), $payment->fresh(), $settings, 202);
        }
        abort_unless($response->successful(), 503, 'Verifikasi provider belum tersedia.');
        $data = $response->json();
        abort_unless(is_array($data), 503, 'Respons provider tidak valid.');
        $gross = $data['gross_amount'] ?? null;
        abort_unless(is_string($gross) && preg_match('/\A(0|[1-9][0-9]*)(?:\.0{1,2})?\z/', $gross, $match)
            && $match[1] === (string) $payment->getRawOriginal('amount')
            && ($data['order_id'] ?? null) === $payment->payment_reference && ($data['currency'] ?? null) === 'IDR'
            && ($data['payment_type'] ?? null) === 'qris'
            && (! array_key_exists('merchant_id', $data) || $data['merchant_id'] === $settings['merchant_id'])
            && (! array_key_exists('fraud_status', $data) || $data['fraud_status'] === 'accept'), 409, 'Fakta provider tidak cocok; rekonsiliasi diperlukan.');
        $state = $data['transaction_status'] ?? null;
        abort_if($state === 'settlement' && ($data['status_code'] ?? null) !== '200', 409, 'Status provider tidak konsisten.');
        if (! in_array($state, ['settlement', 'expire', 'pending'], true)) {
            Log::warning('QRIS requires manual reconciliation.', ['payment_id' => $payment->id, 'order_id' => $order->id, 'reason' => 'unsupported_provider_state']);
        }
        try {
            return $this->withQrisOrder($order, function (Order $current, Collection $attempts) use ($payment, $settings, $state): JsonResponse {
                $stored = $attempts->firstWhere('id', $payment->id);
                abort_unless($stored !== null && $stored->payment_reference === $payment->payment_reference, 409, 'Percobaan berubah.');
                foreach ($attempts as $attempt) {
                    $this->assertQrisQuote($current, $attempt, $settings);
                }
                if ($stored->status === 'success') {
                    $this->assertQrisSuccess($current, $stored);
                    if ($state !== 'settlement') {
                        Log::warning('QRIS requires manual reconciliation.', ['payment_id' => $stored->id, 'order_id' => $current->id, 'reason' => 'accepted_state_conflict']);
                    }
                    abort_unless($state === 'settlement', 409, 'Status pembayaran diterima bertentangan; tinjau manual.');

                    return $this->qrisResponse($current, $stored, $settings, 200);
                }
                if ($attempts->contains('status', 'success') || $attempts->where('status', 'pending')->where('id', '!=', $stored->id)->isNotEmpty()) {
                    Log::warning('QRIS requires manual reconciliation.', ['payment_id' => $stored->id, 'order_id' => $current->id, 'reason' => 'competing_attempt_conflict']);
                    abort(409, 'Riwayat pembayaran bertentangan.');
                }
                abort_unless($current->status === Order::STATUS_SERVED, 409, 'Status pesanan berubah.');
                if ($state === 'settlement') {
                    $method = PaymentMethod::query()->whereKey($stored->payment_method_id)->sharedLock()->first();
                    $category = CashCategory::query()->where('name', 'Penjualan')->where('type', 'in')->sharedLock()->first();
                    abort_unless($method?->type === 'qris' && $category !== null, 503, 'Konfigurasi pencatatan tidak valid.');
                    abort_if(CashTransaction::query()->where('payment_id', $stored->id)->lockForUpdate()->exists(), 409, 'Pemasukan lama tidak konsisten.');
                    $paidAt = now();
                    $stored->update(['status' => 'success', 'paid_at' => $paidAt]);
                    CashTransaction::create(['cash_category_id' => $category->id, 'payment_id' => $stored->id,
                        'recorded_by' => $stored->cashier_id, 'amount' => $stored->amount,
                        'description' => 'Pembayaran QRIS '.$current->order_number, 'transacted_at' => $paidAt]);
                    DB::table('orders')->where('id', $current->id)->update(['status' => Order::STATUS_COMPLETED, 'completed_at' => $paidAt, 'updated_at' => $paidAt]);
                } elseif ($state === 'expire' && $stored->status === 'pending') {
                    // Only current whole-order expiry closes a session; deny/cancel/failure may be subattempts.
                    $stored->update(['status' => 'expired']);
                }

                return $this->qrisResponse($current->fresh(), $stored->fresh(), $settings, in_array($state, ['settlement', 'expire', 'pending'], true) ? 200 : 202);
            });
        } catch (\PDOException) {
            abort(503, 'Pencatatan belum berhasil; rekonsiliasi diperlukan.');
        }
    }

    private function assertQrisSuccess(Order $order, Payment $payment): void
    {
        $method = PaymentMethod::query()->whereKey($payment->payment_method_id)->sharedLock()->first();
        $ledger = CashTransaction::query()->where('payment_id', $payment->id)->lockForUpdate()->first();
        $category = $ledger === null ? null : CashCategory::query()->whereKey($ledger->cash_category_id)->sharedLock()->first();
        abort_unless($method?->type === 'qris' && $order->status === Order::STATUS_COMPLETED && $order->completed_at !== null && $payment->paid_at !== null
            && $order->completed_at->equalTo($payment->paid_at) && $ledger !== null && $category?->type === 'in'
            && (string) $ledger->getRawOriginal('amount') === (string) $payment->getRawOriginal('amount')
            && $ledger->recorded_by === $payment->cashier_id && $ledger->fee_payment_id === null && $ledger->purchase_request_id === null
            && $ledger->transacted_at->equalTo($payment->paid_at), 409, 'Fakta pembayaran diterima tidak konsisten.');
    }

    private function qrisResponse(Order $order, Payment $payment, array $settings, int $status, bool $checkout = false): JsonResponse
    {
        $this->assertQrisQuote($order, $payment, $settings);
        $data = [
            'payment' => ['id' => $payment->id, 'reference' => $payment->payment_reference, 'method' => 'qris', 'currency' => 'IDR',
                'status' => $payment->status, 'amount' => $payment->amount, 'paid_at' => $payment->paid_at?->toISOString()],
            'order' => ['id' => $order->id, 'order_number' => $order->order_number, 'status' => $order->status, 'completed_at' => $order->completed_at?->toISOString()],
            'quote' => ['kind' => 'frozen', 'subtotal' => $order->subtotal, 'discount' => $order->discount, 'service_charge' => $order->service_charge, 'tax' => $order->tax, 'total' => $order->total],
            'fee' => ['status' => 'UNKNOWN', 'amount' => null], 'reconciliation_required' => $payment->status === 'pending' || $status === 202,
        ];
        if ($checkout && $payment->status === 'pending' && $payment->snap_token !== null) {
            abort_unless(preg_match('/\A[A-Za-z0-9_-]{1,255}\z/', $payment->snap_token), 409, 'Token lama tidak valid.');
            $data['checkout'] = ['snap_token' => $payment->snap_token, 'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/'.$payment->snap_token];
        }

        return response()->json(['data' => $data], $status)->header('Cache-Control', 'private, no-store');
    }

    private function previewBill(int $itemSubtotal, int $discount): array
    {
        $discount = min($discount, $itemSubtotal);
        $serviceCharge = intdiv($itemSubtotal * 5 + 50, 100);
        $tax = intdiv($itemSubtotal * 10 + 50, 100);

        return [
            'subtotal' => $itemSubtotal,
            'discount' => $discount,
            'service_charge' => $serviceCharge,
            'service_rate' => 5,
            'tax' => $tax,
            'tax_rate' => 10,
            'tax_basis' => 'subtotal',
            'total' => $itemSubtotal - $discount + $serviceCharge + $tax,
        ];
    }

    private function transformCard(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'table_number' => $order->table?->number,
            'customer_name' => $order->customer_name,
            'status' => $order->status,
            'items_count' => (int) ($order->items_count ?? 0),
            'created_at' => $order->created_at?->toISOString(),
        ];
    }

    private function transformOrder(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'customer_name' => $order->customer_name,
            'order_type' => $order->order_type,
            'status' => $order->status,
            'note' => $order->note,
            'cancellation_reason' => $order->cancellation_reason,
            'table' => $order->table === null ? null : [
                'id' => $order->table->id,
                'number' => $order->table->number,
            ],
            'runner' => $order->runner === null ? null : [
                'id' => $order->runner->id,
                'name' => $order->runner->name,
            ],
            'chef' => $order->chef === null ? null : [
                'id' => $order->chef->id,
                'name' => $order->chef->name,
            ],
            'subtotal' => $order->subtotal,
            'discount' => $order->discount,
            'tax' => $order->tax,
            'total' => $order->total,
            'items' => $order->items
                ->map(fn (OrderItem $item): array => $this->transformOrderItem($item))
                ->all(),
            'created_at' => $order->created_at?->toISOString(),
        ];
    }

    private function transformOrderItem(OrderItem $item): array
    {
        return [
            'id' => $item->id,
            'product' => [
                'id' => $item->product?->id,
                'name' => $item->product?->name,
            ],
            'unit_price' => $item->unit_price,
            'quantity' => $item->quantity,
            'subtotal' => $item->subtotal,
        ];
    }

    private function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'ORD-'.now()->format('Ymd-His').'-'.Str::upper(Str::random(4));
        } while (Order::query()->where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    private function unavailable(string $reason): array
    {
        return [
            'is_orderable' => false,
            'unavailable_reason' => $reason,
        ];
    }

    private function unavailableMessage(string $productName, string $reason): string
    {
        return match ($reason) {
            'product_inactive' => "Produk {$productName} tidak aktif.",
            'category_inactive' => "Kategori produk {$productName} tidak aktif.",
            'not_available' => "Produk {$productName} sedang tidak tersedia.",
            'recipe_missing' => "Resep produk {$productName} belum dikonfigurasi.",
            'recipe_invalid' => "Resep produk {$productName} tidak valid.",
            'ingredient_inactive' => "Bahan baku produk {$productName} tidak aktif.",
            default => "Stok bahan baku produk {$productName} tidak mencukupi.",
        };
    }
}
