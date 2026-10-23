<?php

use App\Models\Order;
use App\Models\Table;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * Tujuan: Menyediakan helper untuk membuat meja aktif pada pengujian relasi.
 * Skenario: Dipanggil dengan nomor meja dan kapasitas opsional.
 * Hasil yang diharapkan: Mengembalikan instance Table aktif yang tersimpan dengan nomor dan capacity sesuai argumen.
 */
function makeTable(int $number, int $capacity = 4): Table
{
    return Table::create(['number' => $number, 'is_active' => true, 'capacity' => $capacity]);
}

/**
 * Tujuan: Menyediakan helper untuk membuat pengguna runner pada pengujian relasi.
 * Skenario: Dipanggil tanpa argumen untuk menyiapkan data pengguna.
 * Hasil yang diharapkan: Mengembalikan instance User aktif yang tersimpan.
 */
function makeRunner(): User
{
    return User::create([
        'name' => 'Runner',
        'email' => 'runner@pos.com',
        'password' => 'password',
        'is_active' => true,
    ]);
}

/**
 * Tujuan: Menyediakan helper untuk membuat order pada sebuah meja.
 * Skenario: Dipanggil dengan meja, runner, status order, dan nomor order tertentu.
 * Hasil yang diharapkan: Mengembalikan instance Order yang tersimpan dan terhubung ke meja terkait.
 */
function makeOrder(Table $table, User $runner, string $status, string $number): Order
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
 * Tujuan: Menguji relasi satu meja memiliki banyak order.
 * Skenario: Satu meja diberi dua order dengan status completed dan cancelled.
 * Hasil yang diharapkan: Relasi orders mengembalikan jumlah 2.
 */
test('table has many orders', function () {
    $table = makeTable(1);
    $runner = makeRunner();

    makeOrder($table, $runner, Order::STATUS_COMPLETED, 'ORD-1');
    makeOrder($table, $runner, Order::STATUS_CANCELLED, 'ORD-2');

    expect($table->orders()->count())->toBe(2);
});

/**
 * Tujuan: Menguji relasi order dimiliki oleh sebuah meja.
 * Skenario: Sebuah order dibuat untuk satu meja tertentu.
 * Hasil yang diharapkan: Order terhubung ke instance Table yang benar.
 */
test('order belongs to a table', function () {
    $table = makeTable(1);
    $runner = makeRunner();
    $order = makeOrder($table, $runner, Order::STATUS_PENDING, 'ORD-1');

    expect($order->table)->toBeInstanceOf(Table::class)
        ->and($order->table->is($table))->toBeTrue();
});

/**
 * Tujuan: Menguji penyaringan order aktif pada relasi.
 * Skenario: Satu meja memiliki order pending, cooking, ready, served, completed, dan cancelled.
 * Hasil yang diharapkan: Relasi activeOrders berjumlah 4 sedangkan seluruh orders berjumlah 6.
 */
test('active orders exclude completed and cancelled', function () {
    $table = makeTable(1);
    $runner = makeRunner();

    makeOrder($table, $runner, Order::STATUS_PENDING, 'ORD-1');
    makeOrder($table, $runner, Order::STATUS_COOKING, 'ORD-2');
    makeOrder($table, $runner, Order::STATUS_READY, 'ORD-3');
    makeOrder($table, $runner, Order::STATUS_SERVED, 'ORD-4');
    makeOrder($table, $runner, Order::STATUS_COMPLETED, 'ORD-5');
    makeOrder($table, $runner, Order::STATUS_CANCELLED, 'ORD-6');

    expect($table->activeOrders()->count())->toBe(4)
        ->and($table->orders()->count())->toBe(6);
});

/**
 * Tujuan: Menguji penerapan eager loading untuk menghindari masalah N+1.
 * Skenario: Lima meja yang masing-masing memiliki satu order pending diambil dengan eager loading activeOrders.
 * Hasil yang diharapkan: Jumlah order aktif terbaca 5 dan hanya dieksekusi 2 query.
 */
test('eager loading active orders avoids n plus one queries', function () {
    $runner = makeRunner();

    foreach (range(1, 5) as $number) {
        $table = makeTable($number);
        makeOrder($table, $runner, Order::STATUS_PENDING, "ORD-{$number}");
    }

    DB::flushQueryLog();
    DB::enableQueryLog();

    $tables = Table::with('activeOrders')->get();
    $activeCount = $tables->map(fn (Table $table): int => $table->activeOrders->count())->sum();

    $queryCount = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($activeCount)->toBe(5)
        ->and($queryCount)->toBe(2);
});

/**
 * Tujuan: Menguji scope keterisian meja yang menjadi acuan filter dan ringkasan.
 * Skenario: Terdapat meja tanpa order, meja ber-order pending, meja ber-order served, meja ber-order completed, dan meja ber-order cancelled.
 * Hasil yang diharapkan: Scope occupied hanya mengembalikan meja pending dan served, sedangkan scope available mengembalikan meja tanpa order, completed, dan cancelled.
 */
test('occupied and available scopes share one filling rule', function () {
    $runner = makeRunner();
    $empty = makeTable(1);
    $pending = makeTable(2);
    $served = makeTable(3);
    $completed = makeTable(4);
    $cancelled = makeTable(5);

    makeOrder($pending, $runner, Order::STATUS_PENDING, 'ORD-1');
    makeOrder($served, $runner, Order::STATUS_SERVED, 'ORD-2');
    makeOrder($completed, $runner, Order::STATUS_COMPLETED, 'ORD-3');
    makeOrder($cancelled, $runner, Order::STATUS_CANCELLED, 'ORD-4');

    expect(Table::occupied()->orderBy('number')->pluck('id')->all())
        ->toBe([$pending->id, $served->id])
        ->and(Table::available()->orderBy('number')->pluck('id')->all())
        ->toBe([$empty->id, $completed->id, $cancelled->id]);
});
