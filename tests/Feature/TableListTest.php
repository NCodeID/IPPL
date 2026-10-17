<?php

use App\Models\Order;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Table;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Tujuan: Menyediakan helper untuk membuat data meja pada pengujian daftar meja.
 * Skenario: Dipanggil dengan nomor meja dan status keaktifan opsional.
 * Hasil yang diharapkan: Mengembalikan instance Table yang tersimpan dengan nomor dan is_active sesuai argumen.
 */
function listTable(int $number, bool $isActive = true): Table
{
    return Table::create(['number' => $number, 'is_active' => $isActive]);
}

/**
 * Tujuan: Menyediakan helper pengguna kasir yang memiliki izin view_tables.
 * Skenario: Dipanggil untuk menyiapkan role kasir beserta izin view_tables sebelum request daftar meja dijalankan.
 * Hasil yang diharapkan: Mengembalikan instance User aktif yang terhubung ke role kasir dengan izin view_tables.
 */
function listViewer(): User
{
    $permission = Permission::firstOrCreate(['name' => 'view_tables'], ['description' => 'View restaurant tables']);
    $role = Role::firstOrCreate(['name' => 'kasir']);
    $role->permissions()->syncWithoutDetaching([$permission->id]);

    return User::create([
        'name' => 'Kasir',
        'email' => 'kasir-list@pos.com',
        'password' => 'password',
        'role_id' => $role->id,
        'is_active' => true,
    ]);
}

/**
 * Tujuan: Menyediakan helper untuk membuat order pada sebuah meja.
 * Skenario: Dipanggil dengan meja, runner, status, nomor order, serta nama pelanggan dan tipe order opsional.
 * Hasil yang diharapkan: Mengembalikan instance Order yang tersimpan dan terhubung ke meja terkait.
 */
function listOrder(
    Table $table,
    User $runner,
    string $status,
    string $number,
    string $customer = 'Pelanggan',
    string $type = 'dine_in',
): Order {
    return Order::create([
        'order_number' => $number,
        'runner_id' => $runner->id,
        'table_id' => $table->id,
        'customer_name' => $customer,
        'order_type' => $type,
        'status' => $status,
    ]);
}

/**
 * Tujuan: Menguji proteksi autentikasi pada endpoint daftar meja.
 * Skenario: Request GET /api/tables dikirim tanpa token autentikasi.
 * Hasil yang diharapkan: Endpoint menolak request dengan status HTTP 401.
 */
test('tables endpoint requires authentication', function () {
    $this->getJson('/api/tables')->assertUnauthorized();
});

/**
 * Tujuan: Menguji pembatasan izin pada endpoint daftar meja.
 * Skenario: Pengguna terautentikasi dengan role gudang yang tidak memiliki izin view_tables mengakses endpoint.
 * Hasil yang diharapkan: Endpoint menolak request dengan status HTTP 403.
 */
test('tables endpoint forbids users without view_tables permission', function () {
    $role = Role::firstOrCreate(['name' => 'gudang']);
    $user = User::create([
        'name' => 'Gudang',
        'email' => 'gudang-list@pos.com',
        'password' => 'password',
        'role_id' => $role->id,
        'is_active' => true,
    ]);

    $this->actingAs($user, 'sanctum')->getJson('/api/tables')->assertForbidden();
});

/**
 * Tujuan: Menguji paginasi beserta metadata pada endpoint daftar meja.
 * Skenario: Terdapat 12 meja aktif dan request meminta halaman kedua dengan per_page 5.
 * Hasil yang diharapkan: Respons memuat 5 data dengan meta current_page 2, per_page 5, total 12, dan last_page 3, serta data dimulai dari nomor meja 6 hingga 10.
 */
test('tables endpoint paginates with metadata', function () {
    $user = listViewer();
    foreach (range(1, 12) as $number) {
        listTable($number);
    }

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/tables?page=2&per_page=5')
        ->assertOk()
        ->assertJsonCount(5, 'data')
        ->assertJsonPath('meta.current_page', 2)
        ->assertJsonPath('meta.per_page', 5)
        ->assertJsonPath('meta.total', 12)
        ->assertJsonPath('meta.last_page', 3);

    $response->assertJsonPath('data.0.number', 6)
        ->assertJsonPath('data.4.number', 10);
});

/**
 * Tujuan: Menguji ukuran halaman default saat parameter per_page tidak dikirim.
 * Skenario: Terdapat 20 meja aktif dan request dilakukan tanpa parameter per_page.
 * Hasil yang diharapkan: Respons memuat 15 data dengan meta per_page 15, total 20, dan last_page 2.
 */
test('tables endpoint uses the default page size when per_page is omitted', function () {
    $user = listViewer();
    foreach (range(1, 20) as $number) {
        listTable($number);
    }

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/tables')
        ->assertOk()
        ->assertJsonCount(15, 'data')
        ->assertJsonPath('meta.per_page', 15)
        ->assertJsonPath('meta.total', 20)
        ->assertJsonPath('meta.last_page', 2);
});

/**
 * Tujuan: Menguji validasi batas maksimum parameter per_page.
 * Skenario: Request mengirim per_page bernilai 101 yang melebihi batas maksimum.
 * Hasil yang diharapkan: Endpoint menolak request dengan status HTTP 422 dan error validasi pada field per_page.
 */
test('tables endpoint rejects a per_page above the maximum', function () {
    $user = listViewer();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/tables?per_page=101')
        ->assertStatus(422)
        ->assertJsonValidationErrors('per_page');
});

/**
 * Tujuan: Menguji validasi nilai parameter status.
 * Skenario: Request mengirim status bernilai broken yang tidak termasuk nilai yang diizinkan.
 * Hasil yang diharapkan: Endpoint menolak request dengan status HTTP 422 dan error validasi pada field status.
 */
test('tables endpoint rejects an invalid status filter', function () {
    $user = listViewer();
    listTable(1);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/tables?status=broken')
        ->assertStatus(422)
        ->assertJsonValidationErrors('status');
});

/**
 * Tujuan: Menguji filter pencarian berdasarkan nomor meja.
 * Skenario: Terdapat meja bernomor 1, 2, dan 3 lalu request mengirim search bernilai 2.
 * Hasil yang diharapkan: Respons hanya memuat satu meja yaitu nomor 2 dengan meta total 1.
 */
test('tables endpoint filters by table number search', function () {
    $user = listViewer();
    listTable(1);
    listTable(2);
    listTable(3);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/tables?search=2')
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.number', 2);
});

/**
 * Tujuan: Menguji filter status available pada endpoint daftar meja.
 * Skenario: Terdapat meja tanpa order, meja ber-order completed, meja ber-order pending, dan meja ber-order served.
 * Hasil yang diharapkan: Hanya meja tanpa order dan meja ber-order completed yang ditampilkan dengan meta total 3 dan seluruh status bernilai available.
 */
test('tables endpoint filters available tables', function () {
    $user = listViewer();
    listTable(1);
    listTable(2);
    $processing = listTable(3);
    $occupied = listTable(4);
    listOrder(listTable(5), $user, Order::STATUS_COMPLETED, 'ORD-1');
    listOrder($processing, $user, Order::STATUS_PENDING, 'ORD-2');
    listOrder($occupied, $user, Order::STATUS_SERVED, 'ORD-3');

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/tables?status=available')
        ->assertOk()
        ->assertJsonPath('meta.total', 3)
        ->assertJsonPath('data.0.status', 'available')
        ->assertJsonPath('data.1.status', 'available')
        ->assertJsonPath('data.2.status', 'available');
});

/**
 * Tujuan: Menguji filter status processing pada endpoint daftar meja.
 * Skenario: Terdapat meja tanpa order, meja ber-order cooking, dan meja ber-order served.
 * Hasil yang diharapkan: Hanya meja ber-order cooking yang ditampilkan dengan status processing dan meta total 1.
 */
test('tables endpoint filters processing tables', function () {
    $user = listViewer();
    listTable(1);
    $processing = listTable(2);
    $occupied = listTable(3);
    listOrder($processing, $user, Order::STATUS_COOKING, 'ORD-1');
    listOrder($occupied, $user, Order::STATUS_SERVED, 'ORD-2');

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/tables?status=processing')
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.number', 2)
        ->assertJsonPath('data.0.status', 'processing');
});

/**
 * Tujuan: Menguji filter status occupied pada endpoint daftar meja.
 * Skenario: Terdapat meja tanpa order, meja ber-order ready, dan meja ber-order served.
 * Hasil yang diharapkan: Hanya meja ber-order served yang ditampilkan dengan status occupied dan meta total 1.
 */
test('tables endpoint filters occupied tables', function () {
    $user = listViewer();
    listTable(1);
    $processing = listTable(2);
    $occupied = listTable(3);
    listOrder($processing, $user, Order::STATUS_READY, 'ORD-1');
    listOrder($occupied, $user, Order::STATUS_SERVED, 'ORD-2');

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/tables?status=occupied')
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.number', 3)
        ->assertJsonPath('data.0.status', 'occupied');
});

/**
 * Tujuan: Menguji pengecualian meja nonaktif dari daftar meja.
 * Skenario: Terdapat satu meja aktif dan satu meja nonaktif.
 * Hasil yang diharapkan: Respons hanya memuat meja aktif dengan meta total 1 dan jumlah data 1.
 */
test('tables endpoint excludes inactive tables', function () {
    $user = listViewer();
    listTable(1);
    listTable(2, false);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/tables')
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.number', 1);
});

/**
 * Tujuan: Menguji penyertaan detail order pada daftar meja.
 * Skenario: Terdapat meja tanpa order dan meja dengan order pending pelanggan Budi bertipe takeaway.
 * Hasil yang diharapkan: Meja tanpa order memiliki order bernilai null, sedangkan meja ber-order memuat detail order_number, customer_name, order_type, dan order_status, serta setiap data memiliki field yang sesuai.
 */
test('tables endpoint returns order details only when an active order exists', function () {
    $user = listViewer();
    listTable(1);
    $busy = listTable(2);
    listOrder($busy, $user, Order::STATUS_PENDING, 'ORD-9', 'Budi', 'takeaway');

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/tables')
        ->assertOk()
        ->assertJsonPath('data.0.number', 1)
        ->assertJsonPath('data.0.order', null)
        ->assertJsonPath('data.1.number', 2)
        ->assertJsonPath('data.1.order.order_number', 'ORD-9')
        ->assertJsonPath('data.1.order.customer_name', 'Budi')
        ->assertJsonPath('data.1.order.order_type', 'takeaway')
        ->assertJsonPath('data.1.order.order_status', 'pending')
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'number', 'status', 'order', 'active_orders_count', 'has_data_integrity_issue'],
            ],
        ]);
});

/**
 * Tujuan: Memastikan endpoint daftar meja bersifat read-only.
 * Skenario: Terdapat meja dengan order pending lalu endpoint daftar meja diakses.
 * Hasil yang diharapkan: Status order tetap pending setelah request.
 */
test('tables endpoint does not modify order statuses', function () {
    $user = listViewer();
    $table = listTable(1);
    $order = listOrder($table, $user, Order::STATUS_PENDING, 'ORD-1');

    $this->actingAs($user, 'sanctum')->getJson('/api/tables')->assertOk();

    expect($order->fresh()->status)->toBe(Order::STATUS_PENDING);
});

/**
 * Tujuan: Menguji kombinasi filter pencarian, status, dan paginasi.
 * Skenario: Terdapat meja bernomor 11, 12, 31, dan 32 dengan sebagian meja ber-order, lalu request mengirim search 1 dan status processing dengan variasi per_page serta page.
 * Hasil yang diharapkan: Hanya meja nomor 12 yang memenuhi kombinasi filter dengan meta total dan last_page bernilai 1.
 */
test('tables endpoint combines search status and pagination', function () {
    $user = listViewer();
    listTable(11);
    $processing = listTable(12);
    listTable(31);
    $far = listTable(32);
    listOrder($processing, $user, Order::STATUS_PENDING, 'ORD-1');
    listOrder($far, $user, Order::STATUS_PENDING, 'ORD-2');

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/tables?search=1&status=processing')
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.number', 12);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/tables?search=1&status=processing&per_page=1&page=1')
        ->assertOk()
        ->assertJsonPath('meta.current_page', 1)
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('meta.last_page', 1)
        ->assertJsonPath('data.0.number', 12);
});
