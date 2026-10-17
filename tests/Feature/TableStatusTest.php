<?php

use App\Models\Order;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Table;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Tujuan: Menyediakan helper untuk membuat data meja pada pengujian status.
 * Skenario: Dipanggil dengan nomor meja dan status keaktifan opsional.
 * Hasil yang diharapkan: Mengembalikan instance Table yang tersimpan dengan nomor dan is_active sesuai argumen.
 */
function statusTable(int $number, bool $isActive = true): Table
{
    return Table::create(['number' => $number, 'is_active' => $isActive]);
}

/**
 * Tujuan: Menyediakan helper pengguna kasir yang memiliki izin view_tables.
 * Skenario: Dipanggil untuk menyiapkan role kasir beserta izin view_tables sebelum request status dijalankan.
 * Hasil yang diharapkan: Mengembalikan instance User aktif yang terhubung ke role kasir dengan izin view_tables.
 */
function statusViewer(): User
{
    $permission = Permission::firstOrCreate(['name' => 'view_tables'], ['description' => 'View restaurant tables']);
    $role = Role::firstOrCreate(['name' => 'kasir']);
    $role->permissions()->syncWithoutDetaching([$permission->id]);

    return User::create([
        'name' => 'Kasir',
        'email' => 'kasir-status@pos.com',
        'password' => 'password',
        'role_id' => $role->id,
        'is_active' => true,
    ]);
}

/**
 * Tujuan: Menyediakan helper untuk membuat order pada sebuah meja.
 * Skenario: Dipanggil dengan meja, runner, status order, dan nomor order tertentu.
 * Hasil yang diharapkan: Mengembalikan instance Order yang tersimpan dan terhubung ke meja terkait.
 */
function statusOrder(Table $table, User $runner, string $status, string $number): Order
{
    return Order::create([
        'order_number' => $number,
        'runner_id' => $runner->id,
        'table_id' => $table->id,
        'status' => $status,
    ]);
}

/**
 * Tujuan: Menguji pemetaan status tampilan meja dari setiap status order.
 * Skenario: Setiap kasus dataset membuat meja dengan satu order berstatus pending, cooking, ready, served, completed, atau cancelled.
 * Hasil yang diharapkan: Status meja sesuai pemetaan, yaitu processing untuk pending/cooking/ready, occupied untuk served, dan available untuk completed/cancelled.
 */
test('table display status maps from each order status', function (string $orderStatus, string $expected) {
    $user = statusViewer();
    $table = statusTable(1);
    statusOrder($table, $user, $orderStatus, 'ORD-1');

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/tables')
        ->assertOk()
        ->assertJsonPath('data.0.status', $expected);
})->with([
    'pending is processing' => ['pending', 'processing'],
    'cooking is processing' => ['cooking', 'processing'],
    'ready is processing' => ['ready', 'processing'],
    'served is occupied' => ['served', 'occupied'],
    'completed is available' => ['completed', 'available'],
    'cancelled is available' => ['cancelled', 'available'],
]);

/**
 * Tujuan: Menguji status meja yang tidak memiliki order.
 * Skenario: Terdapat satu meja aktif tanpa order apa pun.
 * Hasil yang diharapkan: Status meja bernilai available dan detail order bernilai null.
 */
test('table without orders is available', function () {
    $user = statusViewer();
    statusTable(1);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/tables')
        ->assertOk()
        ->assertJsonPath('data.0.status', 'available')
        ->assertJsonPath('data.0.order', null);
});

/**
 * Tujuan: Menguji pengecualian meja nonaktif dari daftar meja.
 * Skenario: Terdapat satu meja aktif dan satu meja nonaktif yang masing-masing memiliki order pending.
 * Hasil yang diharapkan: Respons hanya memuat satu data yaitu meja aktif bernomor 1.
 */
test('inactive tables are excluded from the list', function () {
    $user = statusViewer();
    $active = statusTable(1);
    $inactive = statusTable(2, false);
    statusOrder($active, $user, Order::STATUS_PENDING, 'ORD-1');
    statusOrder($inactive, $user, Order::STATUS_PENDING, 'ORD-2');

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/tables')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.number', 1);
});

/**
 * Tujuan: Menguji kombinasi metrik ringkasan processing, occupied, dan available.
 * Skenario: Terdapat meja ber-order ready, meja ber-order served, meja ber-order completed, meja tanpa order, dan meja nonaktif ber-order pending.
 * Hasil yang diharapkan: total_tables 4, occupied_tables 2, available_tables 2, dan data_integrity_issues 0.
 */
test('summary metrics combine processing occupied and available', function () {
    $user = statusViewer();
    $processing = statusTable(1);
    $occupied = statusTable(2);
    $completed = statusTable(3);
    statusTable(4);
    $inactive = statusTable(5, false);

    statusOrder($processing, $user, Order::STATUS_READY, 'ORD-1');
    statusOrder($occupied, $user, Order::STATUS_SERVED, 'ORD-2');
    statusOrder($completed, $user, Order::STATUS_COMPLETED, 'ORD-3');
    statusOrder($inactive, $user, Order::STATUS_PENDING, 'ORD-4');

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/tables/summary')
        ->assertOk()
        ->assertJsonPath('total_tables', 4)
        ->assertJsonPath('occupied_tables', 2)
        ->assertJsonPath('available_tables', 2)
        ->assertJsonPath('data_integrity_issues', 0);
});

/**
 * Tujuan: Menguji penanganan beberapa order aktif pada satu meja.
 * Skenario: Satu meja memiliki order served dan order pending secara bersamaan.
 * Hasil yang diharapkan: Status meja mengikuti prioritas processing, active_orders_count 2, dan has_data_integrity_issue true.
 */
test('multiple active orders are flagged and resolved by priority', function () {
    $user = statusViewer();
    $table = statusTable(1);
    statusOrder($table, $user, Order::STATUS_SERVED, 'ORD-1');
    statusOrder($table, $user, Order::STATUS_PENDING, 'ORD-2');

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/tables')
        ->assertOk()
        ->assertJsonPath('data.0.status', 'processing')
        ->assertJsonPath('data.0.active_orders_count', 2)
        ->assertJsonPath('data.0.has_data_integrity_issue', true);
});

/**
 * Tujuan: Menguji pembatasan izin view_tables pada endpoint daftar meja.
 * Skenario: Pengguna terautentikasi dengan role gudang yang tidak memiliki izin view_tables mengakses endpoint.
 * Hasil yang diharapkan: Endpoint menolak request dengan status HTTP 403.
 */
test('tables endpoint requires view_tables permission', function () {
    $role = Role::firstOrCreate(['name' => 'gudang']);
    $user = User::create([
        'name' => 'Gudang',
        'email' => 'gudang-status@pos.com',
        'password' => 'password',
        'role_id' => $role->id,
        'is_active' => true,
    ]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/tables')
        ->assertForbidden();
});
