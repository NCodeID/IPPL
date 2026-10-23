<?php

use App\Models\Order;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Table;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Tujuan: Menyediakan helper pembuatan meja pada pengujian detail meja.
 * Skenario: Dipanggil dengan nomor meja dan status keaktifan opsional.
 * Hasil yang diharapkan: Mengembalikan instance Table yang tersimpan sesuai argumen.
 */
function detailTable(int $number, bool $isActive = true): Table
{
    return Table::create(['number' => $number, 'is_active' => $isActive, 'capacity' => 4]);
}

/**
 * Tujuan: Menyediakan helper pengguna kasir yang memiliki izin view_tables pada pengujian detail meja.
 * Skenario: Dipanggil untuk menyiapkan role kasir beserta izin view_tables sebelum request detail dijalankan.
 * Hasil yang diharapkan: Mengembalikan instance User aktif yang terhubung ke role kasir dengan izin view_tables.
 */
function detailViewer(): User
{
    $permission = Permission::firstOrCreate(['name' => 'view_tables'], ['description' => 'View restaurant tables']);
    $role = Role::firstOrCreate(['name' => 'kasir']);
    $role->permissions()->syncWithoutDetaching([$permission->id]);

    return User::create([
        'name' => 'Kasir',
        'email' => 'kasir-detail@pos.com',
        'password' => 'password',
        'role_id' => $role->id,
        'is_active' => true,
    ]);
}

/**
 * Tujuan: Menyediakan helper pembuatan order pada pengujian detail meja.
 * Skenario: Dipanggil dengan meja, runner, status, nomor order, serta nama pelanggan dan catatan opsional.
 * Hasil yang diharapkan: Mengembalikan instance Order yang tersimpan dan terhubung ke meja terkait.
 */
function detailOrder(
    Table $table,
    User $runner,
    string $status,
    string $number,
    string $customer = 'Pelanggan',
    ?string $note = null,
): Order {
    return Order::create([
        'order_number' => $number,
        'runner_id' => $runner->id,
        'table_id' => $table->id,
        'customer_name' => $customer,
        'order_type' => 'dine_in',
        'status' => $status,
        'note' => $note,
    ]);
}

/**
 * Tujuan: Menguji proteksi autentikasi pada endpoint detail meja.
 * Skenario: Request GET /api/tables/{id} dikirim tanpa token autentikasi.
 * Hasil yang diharapkan: Endpoint menolak request dengan status HTTP 401.
 */
test('table detail endpoint requires authentication', function () {
    $table = detailTable(1);

    $this->getJson("/api/tables/{$table->id}")->assertUnauthorized();
});

/**
 * Tujuan: Menguji pembatasan izin pada endpoint detail meja.
 * Skenario: Pengguna terautentikasi dengan role gudang yang tidak memiliki izin view_tables mengakses endpoint.
 * Hasil yang diharapkan: Endpoint menolak request dengan status HTTP 403.
 */
test('table detail endpoint forbids users without view_tables permission', function () {
    $table = detailTable(1);
    $role = Role::firstOrCreate(['name' => 'gudang']);
    $user = User::create([
        'name' => 'Gudang',
        'email' => 'gudang-detail@pos.com',
        'password' => 'password',
        'role_id' => $role->id,
        'is_active' => true,
    ]);

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/tables/{$table->id}")
        ->assertForbidden();
});

/**
 * Tujuan: Menguji detail meja tanpa order aktif.
 * Skenario: Meja aktif tanpa order diminta melalui endpoint detail.
 * Hasil yang diharapkan: Respons memuat status available, display_status available, customer_name null, order null, dan hitungan order aktif nol.
 */
test('table detail returns an available table without an active order', function () {
    $user = detailViewer();
    $table = detailTable(1);

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/tables/{$table->id}")
        ->assertOk()
        ->assertJsonPath('id', $table->id)
        ->assertJsonPath('number', 1)
        ->assertJsonPath('capacity', 4)
        ->assertJsonPath('status', 'available')
        ->assertJsonPath('display_status', 'available')
        ->assertJsonPath('customer_name', null)
        ->assertJsonPath('order', null)
        ->assertJsonPath('active_orders_count', 0)
        ->assertJsonPath('has_data_integrity_issue', false);
});

/**
 * Tujuan: Menguji detail meja dengan order aktif beserta catatannya.
 * Skenario: Meja memiliki order pending pelanggan Budi dengan catatan "Tanpa pedas".
 * Hasil yang diharapkan: Respons memuat status processing, customer_name Budi, dan detail order termasuk note "Tanpa pedas" serta tidak lebih dari satu order aktif.
 */
test('table detail returns the active order including its note', function () {
    $user = detailViewer();
    $table = detailTable(1);
    detailOrder($table, $user, Order::STATUS_PENDING, 'ORD-1', 'Budi', 'Tanpa pedas');

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/tables/{$table->id}")
        ->assertOk()
        ->assertJsonPath('status', 'processing')
        ->assertJsonPath('display_status', 'occupied')
        ->assertJsonPath('customer_name', 'Budi')
        ->assertJsonPath('order.order_number', 'ORD-1')
        ->assertJsonPath('order.customer_name', 'Budi')
        ->assertJsonPath('order.order_type', 'dine_in')
        ->assertJsonPath('order.order_status', 'pending')
        ->assertJsonPath('order.note', 'Tanpa pedas')
        ->assertJsonPath('active_orders_count', 1)
        ->assertJsonPath('has_data_integrity_issue', false);
});

/**
 * Tujuan: Menguji pemilihan order aktif berprioritas pada detail meja.
 * Skenario: Satu meja memiliki order served dan order cooking, dengan order cooking dibuat lebih dulu.
 * Hasil yang diharapkan: Detail memilih order cooking sebagai order utama dan menandai adanya lebih dari satu order aktif.
 */
test('table detail resolves the active order by priority', function () {
    $user = detailViewer();
    $table = detailTable(1);
    detailOrder($table, $user, Order::STATUS_COOKING, 'ORD-1', 'Budi');
    detailOrder($table, $user, Order::STATUS_SERVED, 'ORD-2', 'Sinta');

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/tables/{$table->id}")
        ->assertOk()
        ->assertJsonPath('status', 'processing')
        ->assertJsonPath('customer_name', 'Budi')
        ->assertJsonPath('order.order_number', 'ORD-1')
        ->assertJsonPath('active_orders_count', 2)
        ->assertJsonPath('has_data_integrity_issue', true);
});

/**
 * Tujuan: Menguji penolakan detail untuk meja nonaktif.
 * Skenario: Meja nonaktif yang memiliki order pending diminta melalui endpoint detail.
 * Hasil yang diharapkan: Endpoint mengembalikan status HTTP 404.
 */
test('table detail hides inactive tables', function () {
    $user = detailViewer();
    $table = detailTable(1, false);
    detailOrder($table, $user, Order::STATUS_PENDING, 'ORD-1');

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/tables/{$table->id}")
        ->assertNotFound();
});

/**
 * Tujuan: Menguji respons untuk meja yang tidak ada.
 * Skenario: Request detail dikirim dengan ID meja yang tidak terdaftar.
 * Hasil yang diharapkan: Endpoint mengembalikan status HTTP 404.
 */
test('table detail returns not found for an unknown table', function () {
    $user = detailViewer();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/tables/999999')
        ->assertNotFound();
});

/**
 * Tujuan: Menguji konsistensi struktur detail dengan item pada daftar meja.
 * Skenario: Satu meja ber-order pending diambil melalui endpoint daftar dan endpoint detail.
 * Hasil yang diharapkan: Objek detail identik dengan item data pertama pada daftar.
 */
test('table detail matches the corresponding list item', function () {
    $user = detailViewer();
    $table = detailTable(1);
    detailOrder($table, $user, Order::STATUS_PENDING, 'ORD-1', 'Budi', 'Tanpa pedas');

    $list = $this->actingAs($user, 'sanctum')
        ->getJson('/api/tables')
        ->assertOk()
        ->json();

    $detail = $this->actingAs($user, 'sanctum')
        ->getJson("/api/tables/{$table->id}")
        ->assertOk()
        ->json();

    expect($detail)->toBe($list['data'][0]);
});
