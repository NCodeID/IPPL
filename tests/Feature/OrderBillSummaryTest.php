<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function billSummarySeed(): void
{
    foreach (['admin', 'akuntan', 'kasir', 'dapur', 'runner', 'gudang'] as $roleName) {
        Role::firstOrCreate(['name' => $roleName]);
    }

    (new PermissionSeeder)->run();
}

function billSummaryUser(string $roleName, string $email): User
{
    return User::create([
        'name' => ucfirst($roleName),
        'email' => $email,
        'password' => 'password',
        'role_id' => Role::where('name', $roleName)->value('id'),
        'is_active' => true,
    ]);
}

function billSummaryOrder(User $runner): array
{
    $category = Category::create(['name' => 'Makanan', 'is_active' => true]);
    $firstProduct = Product::create([
        'category_id' => $category->id,
        'name' => 'Nasi Goreng',
        'price' => 9999,
        'is_available' => true,
        'is_active' => false,
    ]);
    $secondProduct = Product::create([
        'category_id' => $category->id,
        'name' => 'Teh',
        'price' => 9000,
        'is_available' => false,
        'is_active' => false,
    ]);
    $order = Order::create([
        'order_number' => 'ORD-BILL-1',
        'runner_id' => $runner->id,
        'customer_name' => 'Budi',
        'order_type' => 'takeaway',
        'status' => Order::STATUS_SERVED,
        'note' => null,
        'subtotal' => 3500,
        'discount' => 0,
        'tax' => 0,
    ]);
    $order->items()->createMany([
        ['product_id' => $firstProduct->id, 'unit_price' => 1000, 'quantity' => 2],
        ['product_id' => $secondProduct->id, 'unit_price' => 500, 'quantity' => 3],
    ]);

    return [$order, $firstProduct];
}

test('cashier and runner can read historical bill summary without changing order state', function () {
    billSummarySeed();
    $runner = billSummaryUser('runner', 'bill-runner@pos.com');
    $cashier = billSummaryUser('kasir', 'bill-cashier@pos.com');
    [$order, $product] = billSummaryOrder($runner);

    $product->update(['price' => 20000]);

    foreach ([$cashier, $runner] as $user) {
        $this->actingAs($user, 'sanctum')
            ->getJson('/api/orders/'.$order->id)
            ->assertOk()
            ->assertJsonPath('data.customer_name', 'Budi')
            ->assertJsonPath('data.order_type', 'takeaway')
            ->assertJsonPath('data.total_menu', 2)
            ->assertJsonPath('data.total_item', 5)
            ->assertJsonPath('data.note', 'Tidak ada')
            ->assertJsonPath('data.items.0.unit_price', 1000)
            ->assertJsonPath('data.items.0.subtotal', 2000)
            ->assertJsonPath('data.bill.subtotal', 3500)
            ->assertJsonPath('data.bill.service_charge', 175)
            ->assertJsonPath('data.bill.tax', 350)
            ->assertJsonPath('data.bill.tax_basis', 'subtotal')
            ->assertJsonPath('data.bill.total', 4025);
    }

    expect($order->fresh()->status)->toBe(Order::STATUS_SERVED)
        ->and($order->fresh()->tax)->toBe(0);
});

test('bill summary requires an authenticated user and cashier or runner role', function () {
    billSummarySeed();
    $runner = billSummaryUser('runner', 'bill-guard-runner@pos.com');
    [$order] = billSummaryOrder($runner);
    $warehouse = billSummaryUser('gudang', 'bill-guard-warehouse@pos.com');

    $this->getJson('/api/orders/'.$order->id)->assertUnauthorized();

    $this->actingAs($warehouse, 'sanctum')
        ->getJson('/api/orders/'.$order->id)
        ->assertForbidden();
});

test('bill summary returns not found for a missing order', function () {
    billSummarySeed();
    $cashier = billSummaryUser('kasir', 'bill-missing@pos.com');

    $this->actingAs($cashier, 'sanctum')
        ->getJson('/api/orders/999999')
        ->assertNotFound();
});
