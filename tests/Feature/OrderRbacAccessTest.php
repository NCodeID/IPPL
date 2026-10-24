<?php

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\ProductRecipe;
use App\Models\Role;
use App\Models\Table;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function rbacxSeedRoles(): void
{
    foreach (['admin', 'akuntan', 'kasir', 'dapur', 'runner', 'gudang'] as $roleName) {
        Role::firstOrCreate(['name' => $roleName]);
    }

    (new PermissionSeeder)->run();
}

function rbacxUser(string $roleName, string $email, bool $isActive = true): User
{
    return User::create([
        'name' => ucfirst($roleName),
        'email' => $email,
        'password' => 'password',
        'role_id' => Role::where('name', $roleName)->value('id'),
        'is_active' => $isActive,
    ]);
}

function rbacxMenu(): array
{
    $category = Category::create(['name' => 'Main Course', 'is_active' => true]);
    $unit = Unit::firstOrCreate(['code' => 'gram'], ['name' => 'Gram']);
    $ingredient = Ingredient::create([
        'unit_id' => $unit->id,
        'name' => 'Beras',
        'unit_cost' => 1000,
        'stock' => '10.00',
        'is_active' => true,
    ]);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Nasi Goreng',
        'price' => 25000,
        'is_available' => true,
        'is_active' => true,
    ]);
    ProductRecipe::create([
        'product_id' => $product->id,
        'ingredient_id' => $ingredient->id,
        'quantity_required' => '0.50',
    ]);
    $table = Table::create(['number' => 1, 'capacity' => 4, 'is_active' => true]);

    return [$product, $table];
}

test('guest cannot access any new order endpoint', function () {
    $this->getJson('/api/categories')->assertUnauthorized();
    $this->getJson('/api/products')->assertUnauthorized();
    $this->postJson('/api/orders', [])->assertUnauthorized();
});

test('inactive user is blocked on every new order endpoint', function () {
    rbacxSeedRoles();
    $user = rbacxUser('kasir', 'rbacx-inactive@pos.com', false);
    [$product, $table] = rbacxMenu();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/categories')
        ->assertForbidden()
        ->assertJson(['message' => 'Akun tidak dapat dipakai.']);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/products')
        ->assertForbidden();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/orders', [
            'order_type' => 'takeaway',
            'customer_name' => 'Budi',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])
        ->assertForbidden();
});

test('user without manage orders is blocked on every new order endpoint', function () {
    rbacxSeedRoles();
    $user = rbacxUser('gudang', 'rbacx-gudang@pos.com');
    [$product, $table] = rbacxMenu();

    $this->actingAs($user, 'sanctum')->getJson('/api/categories')->assertForbidden();
    $this->actingAs($user, 'sanctum')->getJson('/api/products')->assertForbidden();
    $this->actingAs($user, 'sanctum')
        ->postJson('/api/orders', [
            'order_type' => 'takeaway',
            'customer_name' => 'Budi',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])
        ->assertForbidden();
});

test('cashier can use every new order endpoint', function () {
    rbacxSeedRoles();
    $cashier = rbacxUser('kasir', 'rbacx-kasir@pos.com');
    [$product, $table] = rbacxMenu();

    $this->actingAs($cashier, 'sanctum')->getJson('/api/categories')->assertOk();
    $this->actingAs($cashier, 'sanctum')->getJson('/api/products')->assertOk();

    $this->actingAs($cashier, 'sanctum')
        ->postJson('/api/orders', [
            'order_type' => 'dine_in',
            'customer_name' => 'Budi',
            'table_id' => $table->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])
        ->assertCreated();
});

test('runner can create a takeaway order', function () {
    rbacxSeedRoles();
    $runner = rbacxUser('runner', 'rbacx-runner@pos.com');
    [$product] = rbacxMenu();

    $this->actingAs($runner, 'sanctum')
        ->postJson('/api/orders', [
            'order_type' => 'takeaway',
            'customer_name' => 'Budi',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])
        ->assertCreated()
        ->assertJsonPath('data.runner.id', $runner->id);
});
