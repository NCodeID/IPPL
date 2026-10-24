<?php

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductRecipe;
use App\Models\Role;
use App\Models\Table;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function ordSeedRoles(): void
{
    foreach (['admin', 'akuntan', 'kasir', 'dapur', 'runner', 'gudang'] as $roleName) {
        Role::firstOrCreate(['name' => $roleName]);
    }

    (new PermissionSeeder)->run();
}

function ordUser(string $roleName, string $email, bool $isActive = true): User
{
    return User::create([
        'name' => ucfirst($roleName),
        'email' => $email,
        'password' => 'password',
        'role_id' => Role::where('name', $roleName)->value('id'),
        'is_active' => $isActive,
    ]);
}

function ordCashier(string $email = 'ord-cashier@pos.com'): User
{
    return ordUser('kasir', $email);
}

function ordCategory(string $name = 'Main Course', bool $isActive = true): Category
{
    return Category::create(['name' => $name, 'is_active' => $isActive]);
}

function ordIngredient(string $name = 'Beras', string $stock = '10.00', bool $isActive = true): Ingredient
{
    $unit = Unit::firstOrCreate(['code' => 'gram'], ['name' => 'Gram']);

    return Ingredient::create([
        'unit_id' => $unit->id,
        'name' => $name,
        'unit_cost' => 1000,
        'stock' => $stock,
        'is_active' => $isActive,
    ]);
}

function ordProduct(
    Category $category,
    string $name = 'Nasi Goreng',
    int $price = 25000,
    bool $isAvailable = true,
    bool $isActive = true,
): Product {
    return Product::create([
        'category_id' => $category->id,
        'name' => $name,
        'price' => $price,
        'is_available' => $isAvailable,
        'is_active' => $isActive,
    ]);
}

function ordRecipe(Product $product, Ingredient $ingredient, string $quantity = '0.50'): ProductRecipe
{
    return ProductRecipe::create([
        'product_id' => $product->id,
        'ingredient_id' => $ingredient->id,
        'quantity_required' => $quantity,
    ]);
}

function ordTable(int $number = 1, bool $isActive = true): Table
{
    return Table::create(['number' => $number, 'capacity' => 4, 'is_active' => $isActive]);
}

test('dine in order is created with items and pending status', function () {
    ordSeedRoles();
    $cashier = ordCashier();
    $category = ordCategory();
    $ingredient = ordIngredient();
    $product = ordProduct($category);
    ordRecipe($product, $ingredient);
    $table = ordTable();

    $response = $this->actingAs($cashier, 'sanctum')->postJson('/api/orders', [
        'order_type' => 'dine_in',
        'customer_name' => '  Budi  ',
        'table_id' => $table->id,
        'note' => 'Tanpa pedas',
        'items' => [['product_id' => $product->id, 'quantity' => 2]],
    ])->assertCreated();

    $response
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.order_type', 'dine_in')
        ->assertJsonPath('data.customer_name', 'Budi')
        ->assertJsonPath('data.note', 'Tanpa pedas')
        ->assertJsonPath('data.table.id', $table->id)
        ->assertJsonPath('data.runner.id', $cashier->id)
        ->assertJsonPath('data.chef', null)
        ->assertJsonPath('data.subtotal', 50000)
        ->assertJsonPath('data.discount', 0)
        ->assertJsonPath('data.tax', 0)
        ->assertJsonPath('data.total', 50000)
        ->assertJsonPath('data.items.0.product.id', $product->id)
        ->assertJsonPath('data.items.0.unit_price', 25000)
        ->assertJsonPath('data.items.0.quantity', 2)
        ->assertJsonPath('data.items.0.subtotal', 50000);

    expect($response->json('data.order_number'))->toMatch('/^ORD-\d{8}-\d{6}-[A-Z0-9]{4}$/');

    $this->assertDatabaseHas('orders', [
        'runner_id' => $cashier->id,
        'table_id' => $table->id,
        'status' => 'pending',
        'subtotal' => 50000,
    ]);
});

test('dine in order is rejected when the table is occupied', function () {
    ordSeedRoles();
    $cashier = ordCashier();
    $category = ordCategory();
    $ingredient = ordIngredient();
    $product = ordProduct($category);
    ordRecipe($product, $ingredient);
    $table = ordTable();

    Order::create([
        'order_number' => 'ORD-OCCUPIED-1',
        'runner_id' => $cashier->id,
        'table_id' => $table->id,
        'customer_name' => 'Pelanggan Lain',
        'order_type' => 'dine_in',
        'status' => Order::STATUS_PENDING,
    ]);

    $this->actingAs($cashier, 'sanctum')->postJson('/api/orders', [
        'order_type' => 'dine_in',
        'customer_name' => 'Budi',
        'table_id' => $table->id,
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('table_id');

    expect(Order::count())->toBe(1);
});

test('dine in order is rejected when the table is inactive', function () {
    ordSeedRoles();
    $cashier = ordCashier();
    $category = ordCategory();
    $ingredient = ordIngredient();
    $product = ordProduct($category);
    ordRecipe($product, $ingredient);
    $table = ordTable(1, false);

    $this->actingAs($cashier, 'sanctum')->postJson('/api/orders', [
        'order_type' => 'dine_in',
        'customer_name' => 'Budi',
        'table_id' => $table->id,
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('table_id');

    expect(Order::count())->toBe(0);
});

test('dine in order requires a table', function () {
    ordSeedRoles();
    $cashier = ordCashier();
    $category = ordCategory();
    $ingredient = ordIngredient();
    $product = ordProduct($category);
    ordRecipe($product, $ingredient);

    $this->actingAs($cashier, 'sanctum')->postJson('/api/orders', [
        'order_type' => 'dine_in',
        'customer_name' => 'Budi',
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('table_id');
});

test('dine in order is rejected when the table does not exist', function () {
    ordSeedRoles();
    $cashier = ordCashier();
    $category = ordCategory();
    $ingredient = ordIngredient();
    $product = ordProduct($category);
    ordRecipe($product, $ingredient);

    $this->actingAs($cashier, 'sanctum')->postJson('/api/orders', [
        'order_type' => 'dine_in',
        'customer_name' => 'Budi',
        'table_id' => 999,
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('table_id');
});

test('takeaway order is created without a table', function () {
    ordSeedRoles();
    $cashier = ordCashier();
    $category = ordCategory();
    $ingredient = ordIngredient();
    $product = ordProduct($category);
    ordRecipe($product, $ingredient);

    $response = $this->actingAs($cashier, 'sanctum')->postJson('/api/orders', [
        'order_type' => 'takeaway',
        'customer_name' => 'Budi',
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ])->assertCreated();

    expect($response->json('data.table'))->toBeNull();

    $this->assertDatabaseHas('orders', [
        'id' => $response->json('data.id'),
        'table_id' => null,
        'order_type' => 'takeaway',
    ]);
});

test('takeaway order rejects a table association', function () {
    ordSeedRoles();
    $cashier = ordCashier();
    $category = ordCategory();
    $ingredient = ordIngredient();
    $product = ordProduct($category);
    ordRecipe($product, $ingredient);
    $table = ordTable();

    $this->actingAs($cashier, 'sanctum')->postJson('/api/orders', [
        'order_type' => 'takeaway',
        'customer_name' => 'Budi',
        'table_id' => $table->id,
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('table_id');

    expect(Order::count())->toBe(0);
});

test('customer name is required and cannot be blank', function (?string $customerName) {
    ordSeedRoles();
    $cashier = ordCashier();
    $category = ordCategory();
    $ingredient = ordIngredient();
    $product = ordProduct($category);
    ordRecipe($product, $ingredient);

    $payload = [
        'order_type' => 'takeaway',
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ];

    if ($customerName !== null) {
        $payload['customer_name'] = $customerName;
    }

    $this->actingAs($cashier, 'sanctum')->postJson('/api/orders', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('customer_name');

    expect(Order::count())->toBe(0);
})->with([
    'missing' => [null],
    'blank' => ['   '],
]);

test('order note is stored at the order level only', function () {
    ordSeedRoles();
    $cashier = ordCashier();
    $category = ordCategory();
    $ingredient = ordIngredient();
    $product = ordProduct($category);
    ordRecipe($product, $ingredient);

    $response = $this->actingAs($cashier, 'sanctum')->postJson('/api/orders', [
        'order_type' => 'takeaway',
        'customer_name' => 'Budi',
        'note' => 'Tanpa pedas',
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ])->assertCreated();

    $item = OrderItem::first();

    expect(Order::first()->note)->toBe('Tanpa pedas')
        ->and(array_key_exists('note', $item->getAttributes()))->toBeFalse()
        ->and($response->json('data.items.0'))->not->toHaveKey('note');
});

test('order with an empty cart is rejected', function () {
    ordSeedRoles();
    $cashier = ordCashier();

    $this->actingAs($cashier, 'sanctum')->postJson('/api/orders', [
        'order_type' => 'takeaway',
        'customer_name' => 'Budi',
        'items' => [],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('items');

    expect(Order::count())->toBe(0);
});

test('order with an invalid product is rejected', function () {
    ordSeedRoles();
    $cashier = ordCashier();

    $this->actingAs($cashier, 'sanctum')->postJson('/api/orders', [
        'order_type' => 'takeaway',
        'customer_name' => 'Budi',
        'items' => [['product_id' => 999, 'quantity' => 1]],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('items.0.product_id');

    expect(Order::count())->toBe(0);
});

test('order with an invalid quantity is rejected', function (int|float $quantity) {
    ordSeedRoles();
    $cashier = ordCashier();
    $category = ordCategory();
    $ingredient = ordIngredient();
    $product = ordProduct($category);
    ordRecipe($product, $ingredient);

    $this->actingAs($cashier, 'sanctum')->postJson('/api/orders', [
        'order_type' => 'takeaway',
        'customer_name' => 'Budi',
        'items' => [['product_id' => $product->id, 'quantity' => $quantity]],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('items.0.quantity');

    expect(Order::count())->toBe(0);
})->with([
    'zero' => [0],
    'negative' => [-1],
    'fractional' => [1.5],
]);

test('order with duplicate products is rejected', function () {
    ordSeedRoles();
    $cashier = ordCashier();
    $category = ordCategory();
    $ingredient = ordIngredient();
    $product = ordProduct($category);
    ordRecipe($product, $ingredient);

    $this->actingAs($cashier, 'sanctum')->postJson('/api/orders', [
        'order_type' => 'takeaway',
        'customer_name' => 'Budi',
        'items' => [
            ['product_id' => $product->id, 'quantity' => 1],
            ['product_id' => $product->id, 'quantity' => 2],
        ],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('items.0.product_id');

    expect(Order::count())->toBe(0);
});

test('unavailable, inactive, and inactive-category products are rejected', function () {
    ordSeedRoles();
    $cashier = ordCashier();
    $category = ordCategory();
    $ingredient = ordIngredient();

    $unavailable = ordProduct($category, 'Mie Goreng', 22000, isAvailable: false);
    ordRecipe($unavailable, $ingredient);

    $inactive = ordProduct($category, 'Nasi Uduk', 20000, isActive: false);
    ordRecipe($inactive, $ingredient);

    $inactiveCategory = ordCategory('Archive', false);
    $inactiveCategoryProduct = ordProduct($inactiveCategory, 'Es Teh Manis', 5000);
    ordRecipe($inactiveCategoryProduct, $ingredient);

    foreach ([$unavailable, $inactive, $inactiveCategoryProduct] as $product) {
        $this->actingAs($cashier, 'sanctum')->postJson('/api/orders', [
            'order_type' => 'takeaway',
            'customer_name' => 'Budi',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items');
    }

    expect(Order::count())->toBe(0);
});

test('server snapshots prices and ignores forged order fields', function () {
    ordSeedRoles();
    $cashier = ordCashier();
    $category = ordCategory();
    $ingredient = ordIngredient();
    $product = ordProduct($category, price: 25000);
    ordRecipe($product, $ingredient);
    $table = ordTable();

    $response = $this->actingAs($cashier, 'sanctum')->postJson('/api/orders', [
        'order_type' => 'dine_in',
        'customer_name' => 'Budi',
        'table_id' => $table->id,
        'order_number' => 'FORGED-ORDER',
        'runner_id' => 999,
        'status' => 'completed',
        'price' => 1,
        'subtotal' => 1,
        'total' => 1,
        'discount' => 999,
        'tax' => 999,
        'items' => [[
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 1,
            'subtotal' => 1,
        ]],
    ])->assertCreated();

    $response
        ->assertJsonPath('data.runner.id', $cashier->id)
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.subtotal', 50000)
        ->assertJsonPath('data.discount', 0)
        ->assertJsonPath('data.tax', 0)
        ->assertJsonPath('data.total', 50000)
        ->assertJsonPath('data.items.0.unit_price', 25000)
        ->assertJsonPath('data.items.0.subtotal', 50000);

    expect($response->json('data.order_number'))->not->toBe('FORGED-ORDER');
});

test('order number is generated by the server and unique', function () {
    ordSeedRoles();
    $cashier = ordCashier();
    $category = ordCategory();
    $ingredient = ordIngredient('Beras', '10.00');
    $product = ordProduct($category);
    ordRecipe($product, $ingredient, '0.10');

    $first = $this->actingAs($cashier, 'sanctum')->postJson('/api/orders', [
        'order_type' => 'takeaway',
        'customer_name' => 'Budi',
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ])->assertCreated();

    $second = $this->actingAs($cashier, 'sanctum')->postJson('/api/orders', [
        'order_type' => 'takeaway',
        'customer_name' => 'Budi',
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ])->assertCreated();

    expect($first->json('data.order_number'))->not->toBe($second->json('data.order_number'))
        ->and(Order::count())->toBe(2);
});

test('table list and summary reflect the new order', function () {
    ordSeedRoles();
    $cashier = ordCashier();
    $category = ordCategory();
    $ingredient = ordIngredient();
    $product = ordProduct($category);
    ordRecipe($product, $ingredient);
    $table = ordTable();

    $before = $this->actingAs($cashier, 'sanctum')
        ->getJson('/api/tables/summary')
        ->assertOk()
        ->json();

    $this->actingAs($cashier, 'sanctum')->postJson('/api/orders', [
        'order_type' => 'dine_in',
        'customer_name' => 'Budi',
        'table_id' => $table->id,
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ])->assertCreated();

    $available = $this->actingAs($cashier, 'sanctum')
        ->getJson('/api/tables?status=available&per_page=100')
        ->assertOk()
        ->json('data');

    $after = $this->actingAs($cashier, 'sanctum')
        ->getJson('/api/tables/summary')
        ->assertOk()
        ->json();

    expect(collect($available)->pluck('id')->all())->not->toContain($table->id)
        ->and($after['occupied_tables'])->toBe($before['occupied_tables'] + 1)
        ->and($after['available_tables'])->toBe($before['available_tables'] - 1);
});
