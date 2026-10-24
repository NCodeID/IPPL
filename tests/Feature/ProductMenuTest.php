<?php

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\ProductRecipe;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function menuSeedRoles(): void
{
    foreach (['admin', 'akuntan', 'kasir', 'dapur', 'runner', 'gudang'] as $roleName) {
        Role::firstOrCreate(['name' => $roleName]);
    }

    (new PermissionSeeder)->run();
}

function menuUser(string $roleName, string $email): User
{
    return User::create([
        'name' => ucfirst($roleName),
        'email' => $email,
        'password' => 'password',
        'role_id' => Role::where('name', $roleName)->value('id'),
        'is_active' => true,
    ]);
}

function menuCategory(string $name, bool $isActive = true): Category
{
    return Category::create(['name' => $name, 'is_active' => $isActive]);
}

function menuIngredient(string $name, string $stock, bool $isActive = true): Ingredient
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

function menuProduct(
    Category $category,
    string $name,
    int $price,
    bool $isAvailable = true,
    bool $isActive = true,
    ?string $description = null,
): Product {
    return Product::create([
        'category_id' => $category->id,
        'name' => $name,
        'description' => $description,
        'price' => $price,
        'is_available' => $isAvailable,
        'is_active' => $isActive,
    ]);
}

function menuRecipe(Product $product, Ingredient $ingredient, string $quantity): ProductRecipe
{
    return ProductRecipe::create([
        'product_id' => $product->id,
        'ingredient_id' => $ingredient->id,
        'quantity_required' => $quantity,
    ]);
}

test('guest cannot list products', function () {
    $this->getJson('/api/products')->assertUnauthorized();
});

test('user without manage orders cannot list products', function () {
    menuSeedRoles();

    $this->actingAs(menuUser('gudang', 'menu-gudang@pos.com'), 'sanctum')
        ->getJson('/api/products')
        ->assertForbidden();
});

test('menu search matches product name and description', function () {
    menuSeedRoles();
    $category = menuCategory('Main Course');
    $ingredient = menuIngredient('Beras', '100.00');

    $nasi = menuProduct($category, 'Nasi Goreng', 25000, description: 'pedas manis');
    menuRecipe($nasi, $ingredient, '0.20');

    $mie = menuProduct($category, 'Mie Goreng', 22000);
    menuRecipe($mie, $ingredient, '0.20');

    $cashier = menuUser('kasir', 'menu-search@pos.com');

    $byName = $this->actingAs($cashier, 'sanctum')->getJson('/api/products?search=mie')->assertOk();
    expect(collect($byName->json('data'))->pluck('name')->all())->toBe(['Mie Goreng']);

    $byDescription = $this->actingAs($cashier, 'sanctum')->getJson('/api/products?search=pedas')->assertOk();
    expect(collect($byDescription->json('data'))->pluck('name')->all())->toBe(['Nasi Goreng']);
});

test('menu can be filtered by category', function () {
    menuSeedRoles();
    $main = menuCategory('Main Course');
    $beverage = menuCategory('Beverage');
    $ingredient = menuIngredient('Beras', '100.00');

    $nasi = menuProduct($main, 'Nasi Goreng', 25000);
    menuRecipe($nasi, $ingredient, '0.20');

    $teh = menuProduct($beverage, 'Es Teh Manis', 5000);
    menuRecipe($teh, $ingredient, '0.01');

    $cashier = menuUser('kasir', 'menu-filter@pos.com');

    $response = $this->actingAs($cashier, 'sanctum')
        ->getJson("/api/products?category_id={$beverage->id}")
        ->assertOk();

    expect(collect($response->json('data'))->pluck('name')->all())->toBe(['Es Teh Manis']);

    $this->actingAs($cashier, 'sanctum')
        ->getJson('/api/products?category_id=999')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('category_id');
});

test('inactive products and products in inactive categories are excluded', function () {
    menuSeedRoles();
    $active = menuCategory('Main Course');
    $inactive = menuCategory('Archive', false);
    $ingredient = menuIngredient('Beras', '100.00');

    $visible = menuProduct($active, 'Nasi Goreng', 25000);
    menuRecipe($visible, $ingredient, '0.20');

    $inactiveProduct = menuProduct($active, 'Mie Goreng', 22000, isActive: false);
    menuRecipe($inactiveProduct, $ingredient, '0.20');

    $inactiveCategoryProduct = menuProduct($inactive, 'Nasi Uduk', 20000);
    menuRecipe($inactiveCategoryProduct, $ingredient, '0.20');

    $response = $this->actingAs(menuUser('kasir', 'menu-inactive@pos.com'), 'sanctum')
        ->getJson('/api/products')
        ->assertOk();

    expect(collect($response->json('data'))->pluck('name')->all())->toBe(['Nasi Goreng']);
});

test('product with sufficient stock is orderable', function () {
    menuSeedRoles();
    $category = menuCategory('Main Course');
    $ingredient = menuIngredient('Beras', '10.00');
    $product = menuProduct($category, 'Nasi Goreng', 25000);
    menuRecipe($product, $ingredient, '0.50');

    $response = $this->actingAs(menuUser('kasir', 'menu-ok@pos.com'), 'sanctum')
        ->getJson('/api/products')
        ->assertOk();

    expect($response->json('data.0'))->toMatchArray([
        'id' => $product->id,
        'price' => 25000,
        'is_available' => true,
        'is_orderable' => true,
        'unavailable_reason' => null,
    ]);
});

test('product with insufficient stock is not orderable', function () {
    menuSeedRoles();
    $category = menuCategory('Main Course');
    $ingredient = menuIngredient('Beras', '0.10');
    $product = menuProduct($category, 'Nasi Goreng', 25000);
    menuRecipe($product, $ingredient, '0.50');

    $response = $this->actingAs(menuUser('kasir', 'menu-stock@pos.com'), 'sanctum')
        ->getJson('/api/products')
        ->assertOk();

    expect($response->json('data.0.is_orderable'))->toBeFalse()
        ->and($response->json('data.0.unavailable_reason'))->toBe('insufficient_stock');
});

test('unavailable product is not orderable while the persisted flag stays untouched', function () {
    menuSeedRoles();
    $category = menuCategory('Main Course');
    $ingredient = menuIngredient('Beras', '10.00');
    $product = menuProduct($category, 'Nasi Goreng', 25000, isAvailable: false);
    menuRecipe($product, $ingredient, '0.50');

    $response = $this->actingAs(menuUser('kasir', 'menu-flag@pos.com'), 'sanctum')
        ->getJson('/api/products')
        ->assertOk();

    expect($response->json('data.0.is_available'))->toBeFalse()
        ->and($response->json('data.0.is_orderable'))->toBeFalse()
        ->and($response->json('data.0.unavailable_reason'))->toBe('not_available')
        ->and($product->fresh()->is_available)->toBeFalse();
});

test('product without a recipe is not orderable', function () {
    menuSeedRoles();
    $category = menuCategory('Main Course');
    $product = menuProduct($category, 'Nasi Goreng', 25000);

    $response = $this->actingAs(menuUser('kasir', 'menu-recipe-missing@pos.com'), 'sanctum')
        ->getJson('/api/products')
        ->assertOk();

    expect($response->json('data.0.is_orderable'))->toBeFalse()
        ->and($response->json('data.0.unavailable_reason'))->toBe('recipe_missing')
        ->and($product->fresh()->is_available)->toBeTrue();
});

test('product with an invalid recipe is not orderable', function () {
    menuSeedRoles();
    $category = menuCategory('Main Course');
    $ingredient = menuIngredient('Beras', '10.00');
    $product = menuProduct($category, 'Nasi Goreng', 25000);
    menuRecipe($product, $ingredient, '0.00');

    $response = $this->actingAs(menuUser('kasir', 'menu-recipe-invalid@pos.com'), 'sanctum')
        ->getJson('/api/products')
        ->assertOk();

    expect($response->json('data.0.is_orderable'))->toBeFalse()
        ->and($response->json('data.0.unavailable_reason'))->toBe('recipe_invalid');
});

test('product with an inactive ingredient is not orderable', function () {
    menuSeedRoles();
    $category = menuCategory('Main Course');
    $ingredient = menuIngredient('Beras', '10.00', false);
    $product = menuProduct($category, 'Nasi Goreng', 25000);
    menuRecipe($product, $ingredient, '0.50');

    $response = $this->actingAs(menuUser('kasir', 'menu-ingredient@pos.com'), 'sanctum')
        ->getJson('/api/products')
        ->assertOk();

    expect($response->json('data.0.is_orderable'))->toBeFalse()
        ->and($response->json('data.0.unavailable_reason'))->toBe('ingredient_inactive');
});

test('menu list is paginated', function () {
    menuSeedRoles();
    $category = menuCategory('Main Course');
    $ingredient = menuIngredient('Beras', '100.00');

    foreach (['Nasi Goreng', 'Mie Goreng', 'Nasi Uduk'] as $name) {
        $product = menuProduct($category, $name, 20000);
        menuRecipe($product, $ingredient, '0.20');
    }

    $response = $this->actingAs(menuUser('kasir', 'menu-page@pos.com'), 'sanctum')
        ->getJson('/api/products?per_page=2&page=1')
        ->assertOk()
        ->assertJsonStructure(['data', 'links', 'meta']);

    expect($response->json('data'))->toHaveCount(2)
        ->and($response->json('meta.total'))->toBe(3)
        ->and($response->json('meta.per_page'))->toBe(2)
        ->and($response->json('meta.last_page'))->toBe(2);
});
