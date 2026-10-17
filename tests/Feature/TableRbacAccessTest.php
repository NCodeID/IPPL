<?php

use App\Models\Order;
use App\Models\Role;
use App\Models\Table;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Tujuan: Menyediakan helper untuk menyiapkan role dan izin dasar pada pengujian akses meja.
 * Skenario: Dipanggil untuk membuat seluruh role lalu menjalankan PermissionSeeder.
 * Hasil yang diharapkan: Role admin, akuntan, kasir, dapur, runner, dan gudang tersedia beserta izin yang terhubung.
 */
function rbacSeedRolesAndPermissions(): void
{
    foreach (['admin', 'akuntan', 'kasir', 'dapur', 'runner', 'gudang'] as $role) {
        Role::firstOrCreate(['name' => $role]);
    }

    (new PermissionSeeder)->run();
}

/**
 * Tujuan: Menyediakan helper untuk membuat pengguna dengan role tertentu.
 * Skenario: Dipanggil dengan nama role dan email unik.
 * Hasil yang diharapkan: Mengembalikan instance User aktif yang terhubung ke role sesuai nama role.
 */
function rbacUser(string $roleName, string $email): User
{
    return User::create([
        'name' => ucfirst($roleName),
        'email' => $email,
        'password' => 'password',
        'role_id' => Role::where('name', $roleName)->value('id'),
        'is_active' => true,
    ]);
}

/**
 * Tujuan: Menyediakan helper untuk membuat data meja pada pengujian akses dan konsistensi.
 * Skenario: Dipanggil dengan nomor meja dan status keaktifan opsional.
 * Hasil yang diharapkan: Mengembalikan instance Table yang tersimpan dengan nomor dan is_active sesuai argumen.
 */
function rbacTable(int $number, bool $isActive = true): Table
{
    return Table::create(['number' => $number, 'is_active' => $isActive]);
}

/**
 * Tujuan: Menyediakan helper untuk membuat order pada sebuah meja.
 * Skenario: Dipanggil dengan meja, runner, status order, dan nomor order tertentu.
 * Hasil yang diharapkan: Mengembalikan instance Order yang tersimpan dan terhubung ke meja terkait.
 */
function rbacOrder(Table $table, User $runner, string $status, string $number): Order
{
    return Order::create([
        'order_number' => $number,
        'runner_id' => $runner->id,
        'table_id' => $table->id,
        'status' => $status,
    ]);
}

/**
 * Tujuan: Menguji akses admin ke endpoint meja.
 * Skenario: Pengguna dengan role admin yang memiliki izin view_tables mengakses endpoint daftar dan ringkasan meja.
 * Hasil yang diharapkan: Kedua endpoint merespons dengan status HTTP 200.
 */
test('admin can access both table endpoints', function () {
    rbacSeedRolesAndPermissions();
    $admin = rbacUser('admin', 'admin-tables@pos.com');

    $this->actingAs($admin, 'sanctum')->getJson('/api/tables')->assertOk();
    $this->actingAs($admin, 'sanctum')->getJson('/api/tables/summary')->assertOk();
});

/**
 * Tujuan: Menguji akses kasir ke endpoint meja.
 * Skenario: Pengguna dengan role kasir yang memiliki izin view_tables mengakses endpoint daftar dan ringkasan meja.
 * Hasil yang diharapkan: Kedua endpoint merespons dengan status HTTP 200.
 */
test('cashier with view_tables can access both table endpoints', function () {
    rbacSeedRolesAndPermissions();
    $kasir = rbacUser('kasir', 'kasir-tables@pos.com');

    $this->actingAs($kasir, 'sanctum')->getJson('/api/tables')->assertOk();
    $this->actingAs($kasir, 'sanctum')->getJson('/api/tables/summary')->assertOk();
});

/**
 * Tujuan: Menguji akses runner ke endpoint meja.
 * Skenario: Pengguna dengan role runner yang memiliki izin view_tables mengakses endpoint daftar dan ringkasan meja.
 * Hasil yang diharapkan: Kedua endpoint merespons dengan status HTTP 200.
 */
test('runner with view_tables can access both table endpoints', function () {
    rbacSeedRolesAndPermissions();
    $runner = rbacUser('runner', 'runner-tables@pos.com');

    $this->actingAs($runner, 'sanctum')->getJson('/api/tables')->assertOk();
    $this->actingAs($runner, 'sanctum')->getJson('/api/tables/summary')->assertOk();
});

/**
 * Tujuan: Menguji penolakan pengguna tanpa izin view_tables.
 * Skenario: Pengguna terautentikasi dengan role gudang mengakses endpoint daftar dan ringkasan meja.
 * Hasil yang diharapkan: Kedua endpoint menolak request dengan status HTTP 403.
 */
test('authenticated user without view_tables is denied on both endpoints', function () {
    rbacSeedRolesAndPermissions();
    $gudang = rbacUser('gudang', 'gudang-tables@pos.com');

    $this->actingAs($gudang, 'sanctum')->getJson('/api/tables')->assertForbidden();
    $this->actingAs($gudang, 'sanctum')->getJson('/api/tables/summary')->assertForbidden();
});

/**
 * Tujuan: Menguji keutuhan izin seluruh role setelah penambahan izin view_tables.
 * Skenario: PermissionSeeder dijalankan lalu daftar izin setiap role dibandingkan dengan kumpulan izin yang diharapkan.
 * Hasil yang diharapkan: Setiap role memiliki tepat izin yang sesuai, termasuk view_tables pada admin, kasir, dan runner tanpa menghapus izin lain.
 */
test('adding view_tables preserves every existing role permission set', function () {
    rbacSeedRolesAndPermissions();

    $permissions = fn (string $role): array => Role::where('name', $role)
        ->first()
        ->permissions()
        ->pluck('name')
        ->sort()
        ->values()
        ->all();

    expect($permissions('admin'))->toBe([
        'manage_cash_transactions',
        'manage_categories',
        'manage_ingredients',
        'manage_orders',
        'manage_payment_methods',
        'manage_payments',
        'manage_permissions',
        'manage_products',
        'manage_purchase_requests',
        'manage_roles',
        'manage_tables',
        'manage_units',
        'manage_users',
        'view_tables',
    ])
        ->and($permissions('akuntan'))->toBe([
            'manage_cash_transactions',
            'manage_orders',
            'manage_payment_methods',
            'manage_payments',
            'manage_purchase_requests',
        ])
        ->and($permissions('kasir'))->toBe([
            'manage_cash_transactions',
            'manage_orders',
            'manage_payments',
            'view_tables',
        ])
        ->and($permissions('dapur'))->toBe([
            'manage_ingredients',
            'manage_orders',
            'manage_products',
        ])
        ->and($permissions('runner'))->toBe([
            'manage_orders',
            'view_tables',
        ])
        ->and($permissions('gudang'))->toBe([
            'manage_ingredients',
            'manage_purchase_requests',
        ]);
});

/**
 * Tujuan: Menguji idempotensi PermissionSeeder.
 * Skenario: Seeder dijalankan, snapshot izin setiap role diambil, lalu seeder dijalankan kembali.
 * Hasil yang diharapkan: Kumpulan izin setiap role setelah seeder dijalankan ulang sama dengan snapshot sebelumnya.
 */
test('re-running the seeder keeps role permission sets stable', function () {
    rbacSeedRolesAndPermissions();

    $snapshot = Role::with('permissions')->get()
        ->mapWithKeys(fn (Role $role): array => [
            $role->name => $role->permissions->pluck('name')->sort()->values()->all(),
        ])
        ->all();

    rbacSeedRolesAndPermissions();

    $after = Role::with('permissions')->get()
        ->mapWithKeys(fn (Role $role): array => [
            $role->name => $role->permissions->pluck('name')->sort()->values()->all(),
        ])
        ->all();

    expect($after)->toBe($snapshot);
});

/**
 * Tujuan: Menguji konsistensi ringkasan dengan status pada endpoint daftar meja.
 * Skenario: Terdapat meja dengan order pending, cooking, ready, served, completed, dan cancelled, satu meja tanpa order, serta satu meja nonaktif ber-order pending.
 * Hasil yang diharapkan: total_tables sama dengan jumlah data daftar, occupied_tables sama dengan jumlah meja processing/occupied, available_tables sama dengan jumlah meja available, dan invarian jumlah meja terpenuhi dengan nilai 7, 4, dan 3.
 */
test('summary counts agree with the statuses returned by the list endpoint', function () {
    rbacSeedRolesAndPermissions();
    $user = rbacUser('admin', 'admin-consistency@pos.com');

    rbacTable(1);
    $pending = rbacTable(2);
    $cooking = rbacTable(3);
    $ready = rbacTable(4);
    $served = rbacTable(5);
    $completed = rbacTable(6);
    $cancelled = rbacTable(7);
    $inactive = rbacTable(8, false);

    rbacOrder($pending, $user, Order::STATUS_PENDING, 'ORD-1');
    rbacOrder($cooking, $user, Order::STATUS_COOKING, 'ORD-2');
    rbacOrder($ready, $user, Order::STATUS_READY, 'ORD-3');
    rbacOrder($served, $user, Order::STATUS_SERVED, 'ORD-4');
    rbacOrder($completed, $user, Order::STATUS_COMPLETED, 'ORD-5');
    rbacOrder($cancelled, $user, Order::STATUS_CANCELLED, 'ORD-6');
    rbacOrder($inactive, $user, Order::STATUS_PENDING, 'ORD-7');

    $summary = $this->actingAs($user, 'sanctum')
        ->getJson('/api/tables/summary')
        ->assertOk()
        ->json();

    $list = $this->actingAs($user, 'sanctum')
        ->getJson('/api/tables?per_page=100')
        ->assertOk()
        ->json();

    $statuses = collect($list['data'])->pluck('status');

    expect($summary['total_tables'])->toBe(count($list['data']))
        ->and($summary['occupied_tables'])->toBe($statuses->filter(
            fn (string $status): bool => in_array($status, ['processing', 'occupied'], true),
        )->count())
        ->and($summary['available_tables'])->toBe($statuses->filter(
            fn (string $status): bool => $status === 'available',
        )->count())
        ->and($summary['total_tables'])->toBe($summary['occupied_tables'] + $summary['available_tables'])
        ->and($summary['total_tables'])->toBe(7)
        ->and($summary['occupied_tables'])->toBe(4)
        ->and($summary['available_tables'])->toBe(3);
});
