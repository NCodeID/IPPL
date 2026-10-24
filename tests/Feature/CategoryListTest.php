<?php

use App\Models\Category;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function catSeedRoles(): void
{
    foreach (['admin', 'akuntan', 'kasir', 'dapur', 'runner', 'gudang'] as $roleName) {
        Role::firstOrCreate(['name' => $roleName]);
    }

    (new PermissionSeeder)->run();
}

function catUser(string $roleName, string $email, bool $isActive = true): User
{
    return User::create([
        'name' => ucfirst($roleName),
        'email' => $email,
        'password' => 'password',
        'role_id' => Role::where('name', $roleName)->value('id'),
        'is_active' => $isActive,
    ]);
}

function catCategory(string $name, bool $isActive = true): Category
{
    return Category::create(['name' => $name, 'is_active' => $isActive]);
}

test('guest cannot list categories', function () {
    $this->getJson('/api/categories')->assertUnauthorized();
});

test('inactive user cannot list categories', function () {
    catSeedRoles();
    $user = catUser('kasir', 'cat-inactive@pos.com', false);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/categories')
        ->assertForbidden()
        ->assertJson(['message' => 'Akun tidak dapat dipakai.']);
});

test('user without manage orders cannot list categories', function () {
    catSeedRoles();
    $user = catUser('gudang', 'cat-gudang@pos.com');

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/categories')
        ->assertForbidden()
        ->assertJson(['message' => 'Tidak punya akses.']);
});

test('cashier receives only active categories ordered by name', function () {
    catSeedRoles();
    catCategory('Dessert');
    catCategory('Beverage');
    catCategory('Archive', false);

    $response = $this->actingAs(catUser('kasir', 'cat-kasir@pos.com'), 'sanctum')
        ->getJson('/api/categories')
        ->assertOk()
        ->assertJsonStructure(['data' => [['id', 'name']]]);

    expect(collect($response->json('data'))->pluck('name')->all())
        ->toBe(['Beverage', 'Dessert']);
});
