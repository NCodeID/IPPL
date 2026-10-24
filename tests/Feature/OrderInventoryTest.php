<?php

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductRecipe;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function invSeedRoles(): void
{
    foreach (['admin', 'akuntan', 'kasir', 'dapur', 'runner', 'gudang'] as $roleName) {
        Role::firstOrCreate(['name' => $roleName]);
    }

    (new PermissionSeeder)->run();
}

function invCashier(string $email = 'inv-cashier@pos.com'): User
{
    return User::create([
        'name' => 'Kasir',
        'email' => $email,
        'password' => 'password',
        'role_id' => Role::where('name', 'kasir')->value('id'),
        'is_active' => true,
    ]);
}

function invCategory(string $name = 'Main Course'): Category
{
    return Category::create(['name' => $name, 'is_active' => true]);
}

function invIngredient(string $name, string $stock, bool $isActive = true): Ingredient
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

function invProduct(Category $category, string $name, int $price = 10000): Product
{
    return Product::create([
        'category_id' => $category->id,
        'name' => $name,
        'price' => $price,
        'is_available' => true,
        'is_active' => true,
    ]);
}

function invRecipe(Product $product, Ingredient $ingredient, string $quantity): ProductRecipe
{
    return ProductRecipe::create([
        'product_id' => $product->id,
        'ingredient_id' => $ingredient->id,
        'quantity_required' => $quantity,
    ]);
}

function invPayload(Product $product, int $quantity = 1): array
{
    return [
        'order_type' => 'takeaway',
        'customer_name' => 'Budi',
        'items' => [['product_id' => $product->id, 'quantity' => $quantity]],
    ];
}

test('successful order deducts ingredient stock', function () {
    invSeedRoles();
    $cashier = invCashier();
    $category = invCategory();
    $ingredient = invIngredient('Beras', '10.00');
    $product = invProduct($category, 'Nasi Goreng');
    invRecipe($product, $ingredient, '0.50');

    $this->actingAs($cashier, 'sanctum')
        ->postJson('/api/orders', invPayload($product, 2))
        ->assertCreated();

    expect($ingredient->fresh()->stock)->toBe('9.00')
        ->and(Order::count())->toBe(1)
        ->and(OrderItem::count())->toBe(1);
});

test('aggregate demand for a shared ingredient is calculated across products', function () {
    invSeedRoles();
    $cashier = invCashier();
    $category = invCategory();
    $shared = invIngredient('Telur', '3.00');

    $productA = invProduct($category, 'Nasi Goreng');
    invRecipe($productA, $shared, '2.00');

    $productB = invProduct($category, 'Mie Goreng');
    invRecipe($productB, $shared, '1.00');

    $this->actingAs($cashier, 'sanctum')->postJson('/api/orders', [
        'order_type' => 'takeaway',
        'customer_name' => 'Budi',
        'items' => [
            ['product_id' => $productA->id, 'quantity' => 1],
            ['product_id' => $productB->id, 'quantity' => 1],
        ],
    ])->assertCreated();

    expect($shared->fresh()->stock)->toBe('0.00')
        ->and(Order::count())->toBe(1);
});

test('order is rejected when the aggregate shared ingredient demand exceeds stock', function () {
    invSeedRoles();
    $cashier = invCashier();
    $category = invCategory();
    $shared = invIngredient('Telur', '3.00');

    $productA = invProduct($category, 'Nasi Goreng');
    invRecipe($productA, $shared, '2.00');

    $productB = invProduct($category, 'Mie Goreng');
    invRecipe($productB, $shared, '2.00');

    $this->actingAs($cashier, 'sanctum')->postJson('/api/orders', [
        'order_type' => 'takeaway',
        'customer_name' => 'Budi',
        'items' => [
            ['product_id' => $productA->id, 'quantity' => 1],
            ['product_id' => $productB->id, 'quantity' => 1],
        ],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('stock');

    expect($shared->fresh()->stock)->toBe('3.00')
        ->and(Order::count())->toBe(0)
        ->and(OrderItem::count())->toBe(0);
});

test('no stock is deducted when any cart item fails validation', function () {
    invSeedRoles();
    $cashier = invCashier();
    $category = invCategory();
    $plenty = invIngredient('Beras', '100.00');
    $scarce = invIngredient('Telur', '1.00');

    $productA = invProduct($category, 'Nasi Goreng');
    invRecipe($productA, $plenty, '0.50');

    $productB = invProduct($category, 'Mie Goreng');
    invRecipe($productB, $scarce, '5.00');

    $this->actingAs($cashier, 'sanctum')->postJson('/api/orders', [
        'order_type' => 'takeaway',
        'customer_name' => 'Budi',
        'items' => [
            ['product_id' => $productA->id, 'quantity' => 1],
            ['product_id' => $productB->id, 'quantity' => 1],
        ],
    ])->assertUnprocessable();

    expect($plenty->fresh()->stock)->toBe('100.00')
        ->and($scarce->fresh()->stock)->toBe('1.00')
        ->and(Order::count())->toBe(0)
        ->and(OrderItem::count())->toBe(0);
});

test('a second submission is rejected once stock has been consumed', function () {
    invSeedRoles();
    $cashier = invCashier();
    $category = invCategory();
    $ingredient = invIngredient('Beras', '0.50');
    $product = invProduct($category, 'Nasi Goreng');
    invRecipe($product, $ingredient, '0.50');

    $this->actingAs($cashier, 'sanctum')
        ->postJson('/api/orders', invPayload($product, 1))
        ->assertCreated();

    $this->actingAs($cashier, 'sanctum')
        ->postJson('/api/orders', invPayload($product, 1))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('items');

    expect($ingredient->fresh()->stock)->toBe('0.00')
        ->and(Order::count())->toBe(1);
});

test('inactive ingredient blocks order creation', function () {
    invSeedRoles();
    $cashier = invCashier();
    $category = invCategory();
    $ingredient = invIngredient('Beras', '10.00', false);
    $product = invProduct($category, 'Nasi Goreng');
    invRecipe($product, $ingredient, '0.50');

    $this->actingAs($cashier, 'sanctum')
        ->postJson('/api/orders', invPayload($product, 1))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('items');

    expect($ingredient->fresh()->stock)->toBe('10.00')
        ->and(Order::count())->toBe(0);
});

test('product without a recipe blocks order creation', function () {
    invSeedRoles();
    $cashier = invCashier();
    $category = invCategory();
    $product = invProduct($category, 'Nasi Goreng');

    $this->actingAs($cashier, 'sanctum')
        ->postJson('/api/orders', invPayload($product, 1))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('items');

    expect(Order::count())->toBe(0);
});
