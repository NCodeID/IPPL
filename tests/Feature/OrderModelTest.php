<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

/**
 * Tujuan: Menyediakan helper pengguna runner untuk pengujian model order.
 * Skenario: Dipanggil sebelum membuat order pada setiap kasus uji.
 * Hasil yang diharapkan: Mengembalikan instance User aktif yang tersimpan.
 */
function orderModelRunner(): User
{
    return User::create([
        'name' => 'Runner',
        'email' => 'runner-order-model@pos.com',
        'password' => 'password',
        'is_active' => true,
    ]);
}

/**
 * Tujuan: Menyediakan helper pembuatan order pada pengujian model.
 * Skenario: Dipanggil dengan runner dan atribut tambahan yang ingin ditimpa.
 * Hasil yang diharapkan: Mengembalikan instance Order yang tersimpan.
 */
function createOrderRecord(User $runner, array $attributes = []): Order
{
    return Order::create(array_merge([
        'order_number' => 'ORD-1',
        'runner_id' => $runner->id,
        'customer_name' => 'Budi',
    ], $attributes));
}

/**
 * Tujuan: Menguji kewajiban nama pelanggan pada order.
 * Skenario: Membuat order dengan customer_name null, string kosong, dan hanya spasi.
 * Hasil yang diharapkan: Ketiga percobaan menghasilkan ValidationException dan tidak ada order yang tersimpan.
 */
test('order requires a customer name', function (?string $customerName) {
    $runner = orderModelRunner();

    expect(fn () => createOrderRecord($runner, ['customer_name' => $customerName]))
        ->toThrow(ValidationException::class);

    expect(Order::count())->toBe(0);
})->with([
    'null' => [null],
    'string kosong' => [''],
    'hanya spasi' => ['   '],
]);

/**
 * Tujuan: Memastikan nama pelanggan dinormalisasi sebelum disimpan.
 * Skenario: Membuat order dengan customer_name yang memiliki spasi di awal dan akhir.
 * Hasil yang diharapkan: Nilai tersimpan berupa nama tanpa spasi berlebih.
 */
test('order trims the customer name before saving', function () {
    $order = createOrderRecord(orderModelRunner(), ['customer_name' => '  Budi  ']);

    expect($order->fresh()->customer_name)->toBe('Budi');
});

/**
 * Tujuan: Memastikan catatan pesanan disimpan pada record order.
 * Skenario: Membuat order dengan note "Tanpa pedas".
 * Hasil yang diharapkan: Nilai note terbaca dari record order.
 */
test('order stores its note at the order level', function () {
    $order = createOrderRecord(orderModelRunner(), ['note' => 'Tanpa pedas']);

    expect($order->fresh()->note)->toBe('Tanpa pedas');
});

/**
 * Tujuan: Memastikan order item tidak lagi menerima atribut catatan.
 * Skenario: Mengisi model OrderItem dengan atribut note melalui mass assignment.
 * Hasil yang diharapkan: Atribut note tidak tersimpan pada model OrderItem.
 */
test('order item does not accept a note attribute', function () {
    $item = (new OrderItem)->fill([
        'order_id' => 1,
        'product_id' => 1,
        'unit_price' => 10000,
        'quantity' => 1,
        'note' => 'Catatan item',
    ]);

    expect(array_key_exists('note', $item->getAttributes()))->toBeFalse();
});
