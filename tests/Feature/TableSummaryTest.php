<?php

use App\Models\Order;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Table;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Tujuan: Menyediakan helper untuk membuat data meja pada pengujian ringkasan.
 * Skenario: Dipanggil dengan nomor meja, status keaktifan, dan kapasitas opsional.
 * Hasil yang diharapkan: Mengembalikan instance Table yang tersimpan dengan nomor, is_active, dan capacity sesuai argumen.
 */
function summaryTable(int $number, bool $isActive = true, int $capacity = 4): Table
{
    return Table::create(['number' => $number, 'is_active' => $isActive, 'capacity' => $capacity]);
}

/**
 * Tujuan: Menyediakan helper pengguna kasir yang memiliki izin view_tables.
 * Skenario: Dipanggil untuk menyiapkan role kasir beserta izin view_tables sebelum request ringkasan dijalankan.
 * Hasil yang diharapkan: Mengembalikan instance User aktif yang terhubung ke role kasir dengan izin view_tables.
 */
function summaryViewer(): User
{
    $permission = Permission::firstOrCreate(['name' => 'view_tables'], ['description' => 'View restaurant tables']);
    $role = Role::firstOrCreate(['name' => 'kasir']);
    $role->permissions()->syncWithoutDetaching([$permission->id]);

    return User::create([
        'name' => 'Kasir',
        'email' => 'kasir-summary@pos.com',
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
function summaryOrder(Table $table, User $runner, string $status, string $number): Order
{
    return Order::create([
        'order_number' => $number,
        'runner_id' => $runner->id,
        'table_id' => $table->id,
        'customer_name' => 'Pelanggan',
        'status' => $status,
    ]);
}

/**
 * Tujuan: Memverifikasi invarian jumlah meja pada payload ringkasan.
 * Skenario: Dipanggil dengan payload respons ringkasan yang memuat total_tables, occupied_tables, dan available_tables.
 * Hasil yang diharapkan: total_tables selalu sama dengan occupied_tables ditambah available_tables.
 */
function assertSummaryInvariant(array $payload): void
{
    expect($payload['total_tables'])->toBe($payload['occupied_tables'] + $payload['available_tables']);
}

/**
 * Tujuan: Menguji proteksi autentikasi pada endpoint ringkasan meja.
 * Skenario: Request GET /api/tables/summary dikirim tanpa token autentikasi.
 * Hasil yang diharapkan: Endpoint menolak request dengan status HTTP 401.
 */
test('summary endpoint requires authentication', function () {
    $this->getJson('/api/tables/summary')->assertUnauthorized();
});

/**
 * Tujuan: Menguji pembatasan izin pada endpoint ringkasan meja.
 * Skenario: Pengguna terautentikasi dengan role gudang yang tidak memiliki izin view_tables mengakses endpoint.
 * Hasil yang diharapkan: Endpoint menolak request dengan status HTTP 403.
 */
test('summary endpoint forbids users without view_tables permission', function () {
    $role = Role::firstOrCreate(['name' => 'gudang']);
    $user = User::create([
        'name' => 'Gudang',
        'email' => 'gudang-summary@pos.com',
        'password' => 'password',
        'role_id' => $role->id,
        'is_active' => true,
    ]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/tables/summary')
        ->assertForbidden();
});

/**
 * Tujuan: Menguji nilai ringkasan saat belum ada data meja.
 * Skenario: Pengguna berizin mengakses endpoint ringkasan tanpa meja di database.
 * Hasil yang diharapkan: total_tables, occupied_tables, dan available_tables bernilai 0 serta invarian jumlah meja terpenuhi.
 */
test('summary returns zeros when there are no tables', function () {
    $user = summaryViewer();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/tables/summary')
        ->assertOk()
        ->assertJsonPath('total_tables', 0)
        ->assertJsonPath('occupied_tables', 0)
        ->assertJsonPath('available_tables', 0);

    assertSummaryInvariant($response->json());
});

/**
 * Tujuan: Menguji perhitungan meja tersedia saat tidak ada order aktif.
 * Skenario: Terdapat tiga meja aktif tanpa order sama sekali.
 * Hasil yang diharapkan: total_tables bernilai 3, occupied_tables bernilai 0, dan available_tables bernilai 3.
 */
test('summary counts every active table as available when none have running orders', function () {
    $user = summaryViewer();
    summaryTable(1);
    summaryTable(2);
    summaryTable(3);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/tables/summary')
        ->assertOk()
        ->assertJsonPath('total_tables', 3)
        ->assertJsonPath('occupied_tables', 0)
        ->assertJsonPath('available_tables', 3);

    assertSummaryInvariant($response->json());
});

/**
 * Tujuan: Menguji perlakuan order selesai dan dibatalkan terhadap status meja.
 * Skenario: Terdapat meja dengan order berstatus completed dan meja dengan order berstatus cancelled.
 * Hasil yang diharapkan: Kedua meja dihitung tersedia sehingga occupied_tables bernilai 0 dan available_tables bernilai 2.
 */
test('summary treats completed and cancelled orders as available', function () {
    $user = summaryViewer();
    $completed = summaryTable(1);
    $cancelled = summaryTable(2);
    summaryOrder($completed, $user, Order::STATUS_COMPLETED, 'ORD-1');
    summaryOrder($cancelled, $user, Order::STATUS_CANCELLED, 'ORD-2');

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/tables/summary')
        ->assertOk()
        ->assertJsonPath('total_tables', 2)
        ->assertJsonPath('occupied_tables', 0)
        ->assertJsonPath('available_tables', 2);

    assertSummaryInvariant($response->json());
});

/**
 * Tujuan: Menguji perhitungan ringkasan pada campuran status order berjalan.
 * Skenario: Terdapat meja dengan order pending, cooking, ready, dan served, satu meja tanpa order, serta satu meja ber-order completed.
 * Hasil yang diharapkan: total_tables bernilai 6, occupied_tables bernilai 4, dan available_tables bernilai 2.
 */
test('summary counts mixed occupied and available tables with each running status', function () {
    $user = summaryViewer();
    $pending = summaryTable(1);
    $cooking = summaryTable(2);
    $ready = summaryTable(3);
    $served = summaryTable(4);
    summaryTable(5);
    $completed = summaryTable(6);
    summaryOrder($pending, $user, Order::STATUS_PENDING, 'ORD-1');
    summaryOrder($cooking, $user, Order::STATUS_COOKING, 'ORD-2');
    summaryOrder($ready, $user, Order::STATUS_READY, 'ORD-3');
    summaryOrder($served, $user, Order::STATUS_SERVED, 'ORD-4');
    summaryOrder($completed, $user, Order::STATUS_COMPLETED, 'ORD-5');

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/tables/summary')
        ->assertOk()
        ->assertJsonPath('total_tables', 6)
        ->assertJsonPath('occupied_tables', 4)
        ->assertJsonPath('available_tables', 2);

    assertSummaryInvariant($response->json());
});

/**
 * Tujuan: Menguji pengecualian meja nonaktif dari seluruh metrik ringkasan.
 * Skenario: Terdapat meja aktif tanpa order, meja aktif ber-order pending, serta dua meja nonaktif yang salah satunya ber-order pending.
 * Hasil yang diharapkan: total_tables bernilai 2, occupied_tables bernilai 1, dan available_tables bernilai 1 sehingga meja nonaktif diabaikan.
 */
test('summary ignores inactive tables in every metric', function () {
    $user = summaryViewer();
    summaryTable(1);
    $activeOccupied = summaryTable(2);
    summaryTable(3, false);
    $inactiveOccupied = summaryTable(4, false);
    summaryOrder($activeOccupied, $user, Order::STATUS_PENDING, 'ORD-1');
    summaryOrder($inactiveOccupied, $user, Order::STATUS_PENDING, 'ORD-2');

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/tables/summary')
        ->assertOk()
        ->assertJsonPath('total_tables', 2)
        ->assertJsonPath('occupied_tables', 1)
        ->assertJsonPath('available_tables', 1);

    assertSummaryInvariant($response->json());
});
