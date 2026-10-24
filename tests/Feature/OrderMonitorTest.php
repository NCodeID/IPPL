<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\Table;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function monSeedRoles(): void
{
    foreach (['admin', 'akuntan', 'kasir', 'dapur', 'runner', 'gudang'] as $roleName) {
        Role::firstOrCreate(['name' => $roleName]);
    }

    (new PermissionSeeder)->run();
}

function monUser(string $roleName, string $email, bool $isActive = true): User
{
    return User::create([
        'name' => ucfirst($roleName),
        'email' => $email,
        'password' => 'password',
        'role_id' => Role::where('name', $roleName)->value('id'),
        'is_active' => $isActive,
    ]);
}

function monCreateOrder(User $runner, array $attributes = [], array $items = []): Order
{
    static $counter = 100;
    $counter++;

    $order = Order::create(array_merge([
        'order_number' => 'ORD-MON-'.$counter,
        'runner_id' => $runner->id,
        'customer_name' => 'Pelanggan '.$counter,
        'order_type' => 'dine_in',
        'status' => Order::STATUS_PENDING,
        'subtotal' => 25000,
        'discount' => 0,
        'tax' => 0,
    ], $attributes));

    if (! empty($items)) {
        $order->items()->createMany($items);
    }

    return $order;
}

function monProduct(): Product
{
    $category = Category::create(['name' => 'Minuman', 'is_active' => true]);

    return Product::create([
        'category_id' => $category->id,
        'name' => 'Es Teh',
        'price' => 5000,
        'is_available' => true,
        'is_active' => true,
    ]);
}

test('guest cannot access monitor endpoint', function () {
    $this->getJson('/api/orders/monitor')->assertUnauthorized();
});

test('inactive user is forbidden from monitor endpoint', function () {
    monSeedRoles();
    $cashier = monUser('kasir', 'mon-inactive@pos.com', false);

    $this->actingAs($cashier, 'sanctum')
        ->getJson('/api/orders/monitor')
        ->assertForbidden()
        ->assertJson(['message' => 'Akun tidak dapat dipakai.']);
});

test('user without manage_orders is forbidden from monitor endpoint', function () {
    monSeedRoles();
    $gudang = monUser('gudang', 'mon-gudang@pos.com');

    $this->actingAs($gudang, 'sanctum')
        ->getJson('/api/orders/monitor')
        ->assertForbidden()
        ->assertJson(['message' => 'Tidak punya akses.']);
});

test('non cashier or runner role with manage_orders is forbidden from monitor endpoint', function () {
    monSeedRoles();
    $admin = monUser('admin', 'mon-admin@pos.com');

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/orders/monitor')
        ->assertForbidden()
        ->assertJson(['message' => 'Tidak punya akses.']);
});

test('invalid query parameters return validation errors', function () {
    monSeedRoles();
    $cashier = monUser('kasir', 'mon-cashier-val@pos.com');

    $this->actingAs($cashier, 'sanctum')
        ->getJson('/api/orders/monitor?status=invalid_status')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['status']);

    $this->actingAs($cashier, 'sanctum')
        ->getJson('/api/orders/monitor?page=0')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['page']);

    $this->actingAs($cashier, 'sanctum')
        ->getJson('/api/orders/monitor?per_page=101')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['per_page']);
});

test('runner sees only pending, cooking, and ready orders', function () {
    monSeedRoles();
    $runner = monUser('runner', 'mon-runner-vis@pos.com');
    $table = Table::create(['number' => 1, 'capacity' => 4, 'is_active' => true]);

    monCreateOrder($runner, ['status' => Order::STATUS_PENDING, 'table_id' => $table->id]);
    monCreateOrder($runner, ['status' => Order::STATUS_COOKING, 'table_id' => $table->id]);
    monCreateOrder($runner, ['status' => Order::STATUS_READY, 'table_id' => $table->id]);
    monCreateOrder($runner, ['status' => Order::STATUS_SERVED, 'table_id' => $table->id]);
    monCreateOrder($runner, ['status' => Order::STATUS_COMPLETED, 'table_id' => $table->id]);
    monCreateOrder($runner, ['status' => Order::STATUS_CANCELLED, 'table_id' => $table->id]);

    $response = $this->actingAs($runner, 'sanctum')
        ->getJson('/api/orders/monitor')
        ->assertOk();

    $data = $response->json('data');
    expect($data)->toHaveCount(3);

    $statuses = collect($data)->pluck('status')->all();
    expect($statuses)->toContain(Order::STATUS_PENDING, Order::STATUS_COOKING, Order::STATUS_READY);
    expect($statuses)->not->toContain(Order::STATUS_SERVED, Order::STATUS_COMPLETED, Order::STATUS_CANCELLED);

    $counts = $response->json('counts');
    expect($counts['total'])->toBe(3);
    expect($counts['by_status'])->toBe([
        'pending' => 1,
        'cooking' => 1,
        'ready' => 1,
    ]);
});

test('cashier sees pending, cooking, ready, and served orders', function () {
    monSeedRoles();
    $cashier = monUser('kasir', 'mon-cashier-vis@pos.com');
    $runner = monUser('runner', 'mon-runner-orders@pos.com');
    $table = Table::create(['number' => 2, 'capacity' => 2, 'is_active' => true]);

    monCreateOrder($runner, ['status' => Order::STATUS_PENDING, 'table_id' => $table->id]);
    monCreateOrder($runner, ['status' => Order::STATUS_COOKING, 'table_id' => $table->id]);
    monCreateOrder($runner, ['status' => Order::STATUS_READY, 'table_id' => $table->id]);
    monCreateOrder($runner, ['status' => Order::STATUS_SERVED, 'table_id' => $table->id]);
    monCreateOrder($runner, ['status' => Order::STATUS_COMPLETED, 'table_id' => $table->id]);
    monCreateOrder($runner, ['status' => Order::STATUS_CANCELLED, 'table_id' => $table->id]);

    $response = $this->actingAs($cashier, 'sanctum')
        ->getJson('/api/orders/monitor')
        ->assertOk();

    $data = $response->json('data');
    expect($data)->toHaveCount(4);

    $statuses = collect($data)->pluck('status')->all();
    expect($statuses)->toContain(Order::STATUS_PENDING, Order::STATUS_COOKING, Order::STATUS_READY, Order::STATUS_SERVED);
    expect($statuses)->not->toContain(Order::STATUS_COMPLETED, Order::STATUS_CANCELLED);

    $counts = $response->json('counts');
    expect($counts['total'])->toBe(4);
    expect($counts['by_status'])->toBe([
        'pending' => 1,
        'cooking' => 1,
        'ready' => 1,
        'served' => 1,
    ]);
});

test('counts include zeros for visible statuses with no orders', function () {
    monSeedRoles();
    $cashier = monUser('kasir', 'mon-cashier-zeros@pos.com');

    $response = $this->actingAs($cashier, 'sanctum')
        ->getJson('/api/orders/monitor')
        ->assertOk();

    expect($response->json('counts'))->toBe([
        'total' => 0,
        'by_status' => [
            'pending' => 0,
            'cooking' => 0,
            'ready' => 0,
            'served' => 0,
        ],
    ]);
});

test('status filter filters orders while total counts remain stable', function () {
    monSeedRoles();
    $cashier = monUser('kasir', 'mon-cashier-filter@pos.com');
    $runner = monUser('runner', 'mon-runner-filter@pos.com');

    monCreateOrder($runner, ['status' => Order::STATUS_PENDING]);
    monCreateOrder($runner, ['status' => Order::STATUS_COOKING]);
    monCreateOrder($runner, ['status' => Order::STATUS_COOKING]);

    $response = $this->actingAs($cashier, 'sanctum')
        ->getJson('/api/orders/monitor?status=cooking')
        ->assertOk();

    $data = $response->json('data');
    expect($data)->toHaveCount(2);
    expect(collect($data)->every(fn (array $card): bool => $card['status'] === Order::STATUS_COOKING))->toBeTrue();

    expect($response->json('counts.total'))->toBe(3);
    expect($response->json('counts.by_status.cooking'))->toBe(2);
    expect($response->json('counts.by_status.pending'))->toBe(1);
    expect($response->json('meta.total'))->toBe(2);
});

test('runner requesting served status returns empty data without leaking', function () {
    monSeedRoles();
    $runner = monUser('runner', 'mon-runner-leak@pos.com');

    monCreateOrder($runner, ['status' => Order::STATUS_SERVED]);

    $response = $this->actingAs($runner, 'sanctum')
        ->getJson('/api/orders/monitor?status=served')
        ->assertOk();

    expect($response->json('data'))->toBeEmpty();
    expect($response->json('meta.total'))->toBe(0);
    expect($response->json('counts.total'))->toBe(0);
    expect($response->json('counts.by_status'))->toBe([
        'pending' => 0,
        'cooking' => 0,
        'ready' => 0,
    ]);
});

test('status all returns all role visible orders', function () {
    monSeedRoles();
    $runner = monUser('runner', 'mon-runner-all@pos.com');

    monCreateOrder($runner, ['status' => Order::STATUS_PENDING]);
    monCreateOrder($runner, ['status' => Order::STATUS_COOKING]);
    monCreateOrder($runner, ['status' => Order::STATUS_SERVED]);

    $response = $this->actingAs($runner, 'sanctum')
        ->getJson('/api/orders/monitor?status=all')
        ->assertOk();

    expect($response->json('data'))->toHaveCount(2);
    expect($response->json('meta.total'))->toBe(2);
});

test('card payload contains all required attributes and sum of item quantities', function () {
    monSeedRoles();
    $cashier = monUser('kasir', 'mon-cashier-card@pos.com');
    $runner = monUser('runner', 'mon-runner-card@pos.com');
    $table = Table::create(['number' => 7, 'capacity' => 6, 'is_active' => true]);
    $product = monProduct();

    $order = monCreateOrder(
        $runner,
        [
            'order_number' => 'ORD-20261024-TEST-0001',
            'customer_name' => 'Dewi Sartika',
            'status' => Order::STATUS_COOKING,
            'table_id' => $table->id,
            'order_type' => 'dine_in',
        ],
        [
            ['product_id' => $product->id, 'unit_price' => 5000, 'quantity' => 2],
            ['product_id' => $product->id, 'unit_price' => 5000, 'quantity' => 3],
        ],
    );

    $response = $this->actingAs($cashier, 'sanctum')
        ->getJson('/api/orders/monitor')
        ->assertOk();

    $card = $response->json('data.0');

    expect($card)->toMatchArray([
        'id' => $order->id,
        'order_number' => 'ORD-20261024-TEST-0001',
        'table_number' => 7,
        'customer_name' => 'Dewi Sartika',
        'status' => 'cooking',
        'items_count' => 5,
    ]);
    expect($card['created_at'])->not->toBeNull();
});

test('takeaway order returns null table_number on card payload', function () {
    monSeedRoles();
    $cashier = monUser('kasir', 'mon-cashier-takeaway@pos.com');
    $runner = monUser('runner', 'mon-runner-takeaway@pos.com');

    monCreateOrder($runner, [
        'customer_name' => 'Ahmad',
        'status' => Order::STATUS_PENDING,
        'table_id' => null,
        'order_type' => 'takeaway',
    ]);

    $response = $this->actingAs($cashier, 'sanctum')
        ->getJson('/api/orders/monitor')
        ->assertOk();

    $card = $response->json('data.0');
    expect($card['table_number'])->toBeNull();
    expect($card['customer_name'])->toBe('Ahmad');
});

test('monitor endpoint supports pagination', function () {
    monSeedRoles();
    $cashier = monUser('kasir', 'mon-cashier-page@pos.com');
    $runner = monUser('runner', 'mon-runner-page@pos.com');

    for ($i = 1; $i <= 5; $i++) {
        monCreateOrder($runner, [
            'order_number' => 'ORD-PAGE-'.$i,
            'status' => Order::STATUS_PENDING,
        ]);
    }

    $responsePage1 = $this->actingAs($cashier, 'sanctum')
        ->getJson('/api/orders/monitor?per_page=2&page=1')
        ->assertOk();

    expect($responsePage1->json('data'))->toHaveCount(2);
    expect($responsePage1->json('meta.current_page'))->toBe(1);
    expect($responsePage1->json('meta.per_page'))->toBe(2);
    expect($responsePage1->json('meta.total'))->toBe(5);
    expect($responsePage1->json('meta.last_page'))->toBe(3);

    $responsePage2 = $this->actingAs($cashier, 'sanctum')
        ->getJson('/api/orders/monitor?per_page=2&page=2')
        ->assertOk();

    expect($responsePage2->json('data'))->toHaveCount(2);
    expect($responsePage2->json('meta.current_page'))->toBe(2);

    $responsePage3 = $this->actingAs($cashier, 'sanctum')
        ->getJson('/api/orders/monitor?per_page=2&page=3')
        ->assertOk();

    expect($responsePage3->json('data'))->toHaveCount(1);
    expect($responsePage3->json('meta.current_page'))->toBe(3);
});

test('order model scopes filter properly', function () {
    monSeedRoles();
    $runner = monUser('runner', 'mon-runner-scopes@pos.com');

    monCreateOrder($runner, ['status' => Order::STATUS_PENDING]);
    monCreateOrder($runner, ['status' => Order::STATUS_COOKING]);
    monCreateOrder($runner, ['status' => Order::STATUS_READY]);
    monCreateOrder($runner, ['status' => Order::STATUS_SERVED]);
    monCreateOrder($runner, ['status' => Order::STATUS_COMPLETED]);
    monCreateOrder($runner, ['status' => Order::STATUS_CANCELLED]);

    expect(Order::query()->forRunner()->count())->toBe(3);
    expect(Order::query()->forCashier()->count())->toBe(4);
    expect(Order::query()->withStatus(Order::STATUS_READY)->count())->toBe(1);
});
