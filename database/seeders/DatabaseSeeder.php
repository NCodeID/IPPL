<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductRecipe;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\Table;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->clearSeededTables();

        User::create(['name' => 'Admin', 'email' => 'admin@pos.com', 'role' => 'admin', 'password' => Hash::make('password')]);
        $kasir = User::create(['name' => 'Kasir', 'email' => 'kasir@pos.com', 'role' => 'kasir', 'password' => Hash::make('password')]);
        $runner = User::create(['name' => 'Runner', 'email' => 'runner@pos.com', 'role' => 'runner', 'password' => Hash::make('password')]);
        $dapur = User::create(['name' => 'Dapur', 'email' => 'dapur@pos.com', 'role' => 'dapur', 'password' => Hash::make('password')]);
        $gudang = User::create(['name' => 'Gudang', 'email' => 'gudang@pos.com', 'role' => 'gudang', 'password' => Hash::make('password')]);
        $akuntan = User::create(['name' => 'Akuntan', 'email' => 'akuntan@pos.com', 'role' => 'akuntan', 'password' => Hash::make('password')]);

        $pendingTable = Table::create(['number' => '1', 'status' => Table::STATUS_OCCUPIED]);
        $cookingTable = Table::create(['number' => '2', 'status' => Table::STATUS_OCCUPIED]);
        $servedTable = Table::create(['number' => '3', 'status' => Table::STATUS_OCCUPIED]);
        $cashTable = Table::create(['number' => '4', 'status' => Table::STATUS_AVAILABLE]);
        $qrisTable = Table::create(['number' => '5', 'status' => Table::STATUS_AVAILABLE]);
        $cancelledTable = Table::create(['number' => '6', 'status' => Table::STATUS_OCCUPIED]);

        $cashPaymentMethod = PaymentMethod::create(['name' => 'Cash - Rupiah', 'type' => 'cash']);
        $qrisPaymentMethod = PaymentMethod::create(['name' => 'QRIS - BCA', 'type' => 'qris']);

        $mainCourse = Category::create(['name' => 'Main Course']);
        $beverage = Category::create(['name' => 'Beverage']);
        $ingredient = Category::create(['name' => 'Ingredient']);

        $beras = Product::create([
            'category_id' => $ingredient->id,
            'name' => 'Beras (Kg)',
            'description' => '',
            'price' => 12000,
            'stock' => 50.0,
            'is_sellable' => false,
        ]);

        $telur = Product::create([
            'category_id' => $ingredient->id,
            'name' => 'Telur (Butir)',
            'description' => '',
            'price' => 2000,
            'stock' => 100.0,
            'is_sellable' => false,
        ]);

        $teh = Product::create([
            'category_id' => $ingredient->id,
            'name' => 'Teh Bubuk (Gram)',
            'description' => '',
            'price' => 100,
            'stock' => 1000.0,
            'is_sellable' => false,
        ]);

        $gula = Product::create([
            'category_id' => $ingredient->id,
            'name' => 'Gula Pasir (Gram)',
            'description' => '',
            'price' => 20,
            'stock' => 2000.0,
            'is_sellable' => false,
        ]);

        $es = Product::create([
            'category_id' => $ingredient->id,
            'name' => 'Es Batu (Kg)',
            'description' => '',
            'price' => 1500,
            'stock' => 20.0,
            'is_sellable' => false,
        ]);

        $airMineral = Product::create([
            'category_id' => $beverage->id,
            'name' => 'Air Mineral Botol',
            'description' => '',
            'price' => 5000,
            'stock' => 100.0,
            'is_sellable' => true,
        ]);

        $kerupuk = Product::create([
            'category_id' => $mainCourse->id,
            'name' => 'Kerupuk Kaleng',
            'description' => '',
            'price' => 2000,
            'stock' => 50.0,
            'is_sellable' => true,
        ]);

        $nasiGoreng = Product::create([
            'category_id' => $mainCourse->id,
            'name' => 'Nasi Goreng Spesial',
            'description' => '',
            'price' => 25000,
            'stock' => 0,
            'is_sellable' => true,
        ]);

        $esTeh = Product::create([
            'category_id' => $beverage->id,
            'name' => 'Es Teh Manis',
            'description' => '',
            'price' => 8000,
            'stock' => 0,
            'is_sellable' => true,
        ]);

        ProductRecipe::create([
            'product_id' => $nasiGoreng->id,
            'ingredient_id' => $beras->id,
            'quantity_required' => 0.2,
        ]);

        ProductRecipe::create([
            'product_id' => $nasiGoreng->id,
            'ingredient_id' => $telur->id,
            'quantity_required' => 1.0,
        ]);

        ProductRecipe::create([
            'product_id' => $esTeh->id,
            'ingredient_id' => $teh->id,
            'quantity_required' => 10.0,
        ]);

        ProductRecipe::create([
            'product_id' => $esTeh->id,
            'ingredient_id' => $gula->id,
            'quantity_required' => 15.0,
        ]);

        ProductRecipe::create([
            'product_id' => $esTeh->id,
            'ingredient_id' => $es->id,
            'quantity_required' => 0.2,
        ]);

        $orders = [
            [
                'order_number' => 'ORD-20260929000001-101',
                'table' => $pendingTable,
                'customer_name' => 'Budi Santoso',
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'chef_id' => null,
                'completed_at' => null,
                'cancellation_reason' => null,
                'payment' => null,
                'items' => [
                    ['product' => $nasiGoreng, 'quantity' => 1, 'note' => null],
                ],
            ],
            [
                'order_number' => 'ORD-20260929000002-102',
                'table' => $cookingTable,
                'customer_name' => 'Siti Aminah',
                'status' => 'cooking',
                'payment_status' => 'unpaid',
                'chef_id' => $dapur->id,
                'completed_at' => null,
                'cancellation_reason' => null,
                'payment' => null,
                'items' => [
                    ['product' => $nasiGoreng, 'quantity' => 1, 'note' => null],
                    ['product' => $esTeh, 'quantity' => 2, 'note' => 'Es batu sedikit'],
                ],
            ],
            [
                'order_number' => 'ORD-20260929000003-103',
                'table' => null,
                'customer_name' => 'Andi Wijaya',
                'status' => 'ready',
                'payment_status' => 'unpaid',
                'chef_id' => $dapur->id,
                'completed_at' => null,
                'cancellation_reason' => null,
                'payment' => null,
                'items' => [
                    ['product' => $nasiGoreng, 'quantity' => 2, 'note' => null],
                ],
            ],
            [
                'order_number' => 'ORD-20260929000004-104',
                'table' => $servedTable,
                'customer_name' => 'Rina Marlina',
                'status' => 'served',
                'payment_status' => 'unpaid',
                'chef_id' => $dapur->id,
                'completed_at' => null,
                'cancellation_reason' => null,
                'payment' => null,
                'items' => [
                    ['product' => $nasiGoreng, 'quantity' => 1, 'note' => null],
                    ['product' => $esTeh, 'quantity' => 1, 'note' => null],
                ],
            ],
            [
                'order_number' => 'ORD-20260929000005-105',
                'table' => $cashTable,
                'customer_name' => 'Dewi Lestari',
                'status' => 'completed',
                'payment_status' => 'paid',
                'chef_id' => $dapur->id,
                'completed_at' => now(),
                'cancellation_reason' => null,
                'payment' => 'cash',
                'items' => [
                    ['product' => $nasiGoreng, 'quantity' => 2, 'note' => null],
                    ['product' => $esTeh, 'quantity' => 3, 'note' => null],
                    ['product' => $airMineral, 'quantity' => 1, 'note' => null],
                ],
            ],
            [
                'order_number' => 'ORD-20260929000006-106',
                'table' => $qrisTable,
                'customer_name' => 'Eko Prasetyo',
                'status' => 'completed',
                'payment_status' => 'paid',
                'chef_id' => $dapur->id,
                'completed_at' => now(),
                'cancellation_reason' => null,
                'payment' => 'qris',
                'items' => [
                    ['product' => $nasiGoreng, 'quantity' => 1, 'note' => null],
                    ['product' => $esTeh, 'quantity' => 2, 'note' => null],
                ],
            ],
            [
                'order_number' => 'ORD-20260929000007-107',
                'table' => $cancelledTable,
                'customer_name' => 'Fajar Nugroho',
                'status' => 'cancelled',
                'payment_status' => 'unpaid',
                'chef_id' => null,
                'completed_at' => null,
                'cancellation_reason' => 'Runner salah input menu, seharusnya tanpa pedas.',
                'payment' => null,
                'items' => [
                    ['product' => $nasiGoreng, 'quantity' => 1, 'note' => null],
                ],
            ],
        ];

        foreach ($orders as $attributes) {
            $subtotal = 0;

            foreach ($attributes['items'] as $item) {
                $subtotal += (int) $item['product']->price * $item['quantity'];
            }

            $tax = (int) round($subtotal * 0.11);

            $order = Order::create([
                'order_number' => $attributes['order_number'],
                'runner_id' => $runner->id,
                'chef_id' => $attributes['chef_id'],
                'table_id' => $attributes['table']?->id,
                'customer_name' => $attributes['customer_name'],
                'order_type' => $attributes['table'] ? 'dine_in' : 'takeaway',
                'status' => $attributes['status'],
                'payment_status' => $attributes['payment_status'],
                'subtotal' => $subtotal,
                'discount' => 0,
                'tax' => $tax,
                'total' => $subtotal + $tax,
                'completed_at' => $attributes['completed_at'],
                'cancellation_reason' => $attributes['cancellation_reason'],
            ]);

            foreach ($attributes['items'] as $item) {
                $unitPrice = (int) $item['product']->price;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product']->id,
                    'product_name' => $item['product']->name,
                    'unit_price' => $unitPrice,
                    'quantity' => $item['quantity'],
                    'subtotal' => $unitPrice * $item['quantity'],
                    'note' => $item['note'],
                ]);
            }

            if ($attributes['payment'] === 'cash') {
                $amount = (int) $order->total;
                $tenderedAmount = $amount + 20000;

                Payment::create([
                    'order_id' => $order->id,
                    'payment_method_id' => $cashPaymentMethod->id,
                    'cashier_id' => $kasir->id,
                    'amount' => $amount,
                    'tendered_amount' => $tenderedAmount,
                    'change_amount' => $tenderedAmount - $amount,
                    'status' => 'success',
                ]);
            }

            if ($attributes['payment'] === 'qris') {
                Payment::create([
                    'order_id' => $order->id,
                    'payment_method_id' => $qrisPaymentMethod->id,
                    'cashier_id' => $kasir->id,
                    'amount' => (int) $order->total,
                    'tendered_amount' => (int) $order->total,
                    'change_amount' => 0,
                    'status' => 'success',
                    'snap_token' => 'seed-snap-token-'.$order->order_number,
                    'payment_reference' => (string) Str::uuid(),
                    'paid_at' => now(),
                ]);
            }
        }

        $purchaseRequests = [
            [
                'request_number' => 'PR-0001',
                'status' => 'pending_approval',
                'accountant_id' => null,
                'note' => null,
                'approved_at' => null,
                'items' => [
                    ['product' => $beras, 'quantity' => 50],
                    ['product' => $telur, 'quantity' => 100],
                    ['product' => $teh, 'quantity' => 500],
                    ['product' => $gula, 'quantity' => 1000],
                    ['product' => $es, 'quantity' => 10],
                ],
            ],
            [
                'request_number' => 'PR-0002',
                'status' => 'funds_released',
                'accountant_id' => $akuntan->id,
                'note' => 'Harga disetujui, silakan beli di pasar induk.',
                'approved_at' => now(),
                'items' => [
                    ['product' => $beras, 'quantity' => 25],
                    ['product' => $telur, 'quantity' => 200],
                    ['product' => $teh, 'quantity' => 200],
                    ['product' => $gula, 'quantity' => 500],
                    ['product' => $es, 'quantity' => 5],
                ],
            ],
        ];

        foreach ($purchaseRequests as $attributes) {
            $totalEstimatedCost = 0;

            foreach ($attributes['items'] as $item) {
                $totalEstimatedCost += $item['quantity'] * (int) $item['product']->price;
            }

            $purchaseRequest = PurchaseRequest::create([
                'request_number' => $attributes['request_number'],
                'warehouse_user_id' => $gudang->id,
                'accountant_id' => $attributes['accountant_id'],
                'status' => $attributes['status'],
                'note' => $attributes['note'],
                'approved_at' => $attributes['approved_at'],
                'total_estimated_cost' => $totalEstimatedCost,
            ]);

            foreach ($attributes['items'] as $item) {
                $estimatedPrice = (int) $item['product']->price;

                PurchaseRequestItem::create([
                    'purchase_request_id' => $purchaseRequest->id,
                    'product_id' => $item['product']->id,
                    'quantity' => $item['quantity'],
                    'estimated_price' => $estimatedPrice,
                    'subtotal' => $item['quantity'] * $estimatedPrice,
                ]);
            }
        }
    }

    private function clearSeededTables(): void
    {
        $tables = [
            'payments',
            'order_items',
            'orders',
            'purchase_request_items',
            'purchase_requests',
            'product_recipes',
            'products',
            'payment_methods',
            'categories',
            'tables',
            'personal_access_tokens',
            'users',
        ];

        foreach ($tables as $table) {
            DB::table($table)->delete();
        }
    }
}
