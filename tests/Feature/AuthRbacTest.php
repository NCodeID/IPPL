<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRolesAndPermission();
});

/*
|
| Helper Functions
|
|
| Fungsi-fungsi bantuan di bawah ini digunakan untuk menyiapkan
| data awal (seed) yang dibutuhkan oleh kasus uji otentikasi
| dan kontrol akses berbasis peran (RBAC).
|
*/

/**
 * Menyiapkan peran (role) dan izin (permission) dasar untuk aplikasi POS.
 *
 * Peran yang dibuat:
 * - admin: memiliki akses penuh ke semua izin (mengelola pengguna, peran,
 *   izin, kategori, produk, unit, bahan baku, meja, pesanan, permintaan
 *   pembelian, pembayaran, metode pembayaran, dan transaksi kas).
 * - akuntan: (didaftarkan, namun belum diberi izin khusus pada seed ini).
 * - kasir: memiliki izin untuk mengelola pesanan, pembayaran, dan
 *   transaksi kas.
 * - dapur: (didaftarkan, namun belum diberi izin khusus pada seed ini).
 * - runner: (didaftarkan, namun belum diberi izin khusus pada seed ini).
 * - gudang: (didaftarkan, namun belum diberi izin khusus pada seed ini).
 *
 * Izin yang tersedia mencakup seluruh domain entitas aplikasi POS,
 * memungkinkan kontrol akses yang granular di setiap route API.
 */
function seedRolesAndPermission(): void
{
    $roles = ['admin', 'akuntan', 'kasir', 'dapur', 'runner', 'gudang'];

    foreach ($roles as $role) {
        Role::firstOrCreate(['name' => $role]);
    }

    $permissions = [
        'manage_users', 'manage_roles', 'manage_permissions',
        'manage_categories', 'manage_products', 'manage_units',
        'manage_ingredients', 'manage_tables', 'manage_orders',
        'manage_purchase_requests', 'manage_payments',
        'manage_payment_methods', 'manage_cash_transactions',
    ];

    foreach ($permissions as $perm) {
        Permission::firstOrCreate(['name' => $perm]);
    }

    // Peran admin menerima seluruh izin yang tersedia.
    $admin = Role::where('name', 'admin')->first();
    $admin->permissions()->syncWithoutDetaching(
        Permission::pluck('id')->toArray()
    );

    // Peran kasir hanya mengelola pesanan, pembayaran, dan transaksi kas.
    $kasir = Role::where('name', 'kasir')->first();
    $kasir->permissions()->syncWithoutDetaching(
        Permission::whereIn('name', ['manage_orders', 'manage_payments', 'manage_cash_transactions'])
            ->pluck('id')->toArray()
    );
}

/**
 * Membuat pengguna baru untuk keperluan pengujian.
 *
 * Pengguna yang dibuat secara otomatis ditandai sebagai aktif
 * (is_active = true) dan diberi peran sesuai parameter $role.
 * Password yang digunakan adalah 'password' untuk seluruh kasus uji.
 *
 * @param  string  $role  Nama peran yang akan diberikan kepada pengguna.
 *                        Default-nya adalah 'admin'.
 */
function createUser(string $role = 'admin'): User
{
    return User::create([
        'name' => 'admin nih bos',
        'email' => $role.'-'.uniqid().'@pos.com',
        'password' => Hash::make('password'),
        'role_id' => Role::where('name', $role)->first()->id,
        'is_active' => true,
    ]);
}

/*
|
| Authentication Tests
|
|
| Kasus uji di bawah ini memverifikasi alur otentikasi pengguna:
| - Login dengan kredensial yang valid
| - Login dengan kredensial yang tidak valid
| - Registrasi akun baru
| - Logout dan pencabutan token
|
*/

test('login returns user and token', function () {
    $user = createUser('admin');

    $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password',
    ])
        ->assertOk()
        ->assertJsonStructure(['user', 'token']);
});

test('login fails with invalid credentials', function () {
    $this->postJson('/api/login', [
        'email' => 'nonexistent@example.com',
        'password' => 'wrong',
    ])
        ->assertStatus(422);
});

test('logout revokes token', function () {
    $user = createUser('admin');
    $token = $user->createToken('auth_token')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/logout')
        ->assertOk();

    $this->assertDatabaseCount('personal_access_tokens', 0);
});

/*
|
| RBAC Route Protection Tests
|
|
| Kasus uji di bawah ini memastikan bahwa setiap route API dilindungi
| dengan benar berdasarkan peran dan izin pengguna:
| - Admin dapat mengakses resource peran dan izin
| - Non-admin ditolak (403 Forbidden)
| - Pengguna yang tidak terautentikasi ditolak (401 Unauthorized)
| - Pengguna tidak aktif ditolak (403 Forbidden)
|
*/

test('admin can access roles', function () {
    $user = createUser('admin');

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/roles')
        ->assertOk();
});

test('non admin cannot access roles', function () {
    $user = createUser('kasir');

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/roles')
        ->assertForbidden();
});

test('admin can access permissions', function () {
    $user = createUser('admin');

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/permissions')
        ->assertOk();
});

test('kasir cannot access permissions', function () {
    $user = createUser('kasir');

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/permissions')
        ->assertForbidden();
});

test('unauthenticated user cannot access protected routes', function () {
    $this->getJson('/api/roles')
        ->assertUnauthorized();
});

test('inactive user cannot access routes', function () {
    $user = User::create([
        'name' => 'Inactive User',
        'email' => 'inactive@example.com',
        'password' => Hash::make('password'),
        'role_id' => Role::where('name', 'admin')->first()->id,
        'is_active' => false,
    ]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/user')
        ->assertForbidden();
});

/*
|
| Model & State Assertions
|
|
| Kasus uji di bawah ini memverifikasi logika pada level model,
| khususnya terkait hubungan antara peran (role) dan izin (permission),
| serta kemampuan mengubah izin yang dimiliki oleh sebuah peran.
|
*/

test('role has permissions', function () {
    $admin = createUser('admin');

    expect($admin->hasPermissionTo('manage_roles'))->toBeTrue()
        ->and($admin->hasPermissionTo('manage_users'))->toBeTrue();

    $kasir = createUser('kasir');

    expect($kasir->hasPermissionTo('manage_orders'))->toBeTrue()
        ->and($kasir->hasPermissionTo('manage_roles'))->toBeFalse();
});

test('assign permissions to role', function () {
    $user = createUser('admin');
    $role = Role::where('name', 'runner')->first();
    $permIds = Permission::whereIn('name', ['manage_orders'])->pluck('id')->toArray();

    $this->actingAs($user, 'sanctum')
        ->putJson("/api/roles/{$role->id}", [
            'permissions' => $permIds,
        ])
        ->assertOk();

    expect($role->fresh()->permissions()->whereIn('name', ['manage_orders'])->exists())->toBeTrue();
});
