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
        $bankTransferPaymentMethod = PaymentMethod::create(['name' => 'Bank Transfer - BCA', 'type' => 'bank_transfer']);
        $ewalletPaymentMethod = PaymentMethod::create(['name' => 'GoPay', 'type' => 'ewallet']);
        $creditCardPaymentMethod = PaymentMethod::create(['name' => 'Credit Card - Visa', 'type' => 'credit_card']);

        $mainCourse = Category::create(['name' => 'Main Course']);
        $beverage = Category::create(['name' => 'Beverage']);
        $ingredient = Category::create(['name' => 'Ingredient']);
        $dessert = Category::create(['name' => 'Dessert']);
        $snack = Category::create(['name' => 'Snack']);

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

        $kopi = Product::create([
            'category_id' => $beverage->id,
            'name' => 'Kopi Hitam',
            'description' => '',
            'price' => 7000,
            'stock' => 0,
            'is_sellable' => true,
        ]);

        $kacangTanah = Product::create([
            'category_id' => $snack->id,
            'name' => 'Kacang Tanah Goreng',
            'description' => '',
            'price' => 5000,
            'stock' => 0,
            'is_sellable' => true,
        ]);

        $pisangGoreng = Product::create([
            'category_id' => $dessert->id,
            'name' => 'Pisang Goreng',
            'description' => '',
            'price' => 12000,
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

        ProductRecipe::create([
            'product_id' => $kopi->id,
            'ingredient_id' => $gula->id,
            'quantity_required' => 5.0,
        ]);

        ProductRecipe::create([
            'product_id' => $kopi->id,
            'ingredient_id' => $teh->id,
            'quantity_required' => 2.0,
        ]);

        ProductRecipe::create([
            'product_id' => $kacangTanah->id,
            'ingredient_id' => $gula->id,
            'quantity_required' => 10.0,
        ]);

        ProductRecipe::create([
            'product_id' => $pisangGoreng->id,
            'ingredient_id' => $gula->id,
            'quantity_required' => 15.0,
        ]);

        ProductRecipe::create([
            'product_id' => $pisangGoreng->id,
            'ingredient_id' => $teh->id,
            'quantity_required' => 5.0,
        ]);

        $orders = [
            // Pending Table Orders (5 orders)
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
                    ['product' => $esTeh, 'quantity' => 2, 'note' => null],
                    ['product' => $airMineral, 'quantity' => 1, 'note' => null],
                    ['product' => $kopi, 'quantity' => 1, 'note' => null],
                    ['product' => $kacangTanah, 'quantity' => 1, 'note' => null],
                ],
            ],
            [
                'order_number' => 'ORD-20260929000008-108',
                'table' => $pendingTable,
                'customer_name' => 'Lia Mariana',
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'chef_id' => null,
                'completed_at' => null,
                'cancellation_reason' => null,
                'payment' => null,
                'items' => [
                    ['product' => $nasiGoreng, 'quantity' => 2, 'note' => 'Extra pedas'],
                    ['product' => $esTeh, 'quantity' => 1, 'note' => 'Manis sedikit'],
                    ['product' => $airMineral, 'quantity' => 2, 'note' => null],
                ],
            ],
            [
                'order_number' => 'ORD-20260929000009-109',
                'table' => $pendingTable,
                'customer_name' => 'Rahmat Hidayat',
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'chef_id' => null,
                'completed_at' => null,
                'cancellation_reason' => null,
                'payment' => null,
                'items' => [
                    ['product' => $nasiGoreng, 'quantity' => 1, 'note' => null],
                    ['product' => $kopi, 'quantity' => 2, 'note' => 'Tanpa gula'],
                    ['product' => $kacangTanah, 'quantity' => 2, 'note' => null],
                ],
            ],
            [
                'order_number' => 'ORD-2026092900010-110',
                'table' => $pendingTable,
                'customer_name' => 'Sariyah',
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'chef_id' => null,
                'completed_at' => null,
                'cancellation_reason' => null,
                'payment' => null,
                'items' => [
                    ['product' => $nasiGoreng, 'quantity' => 3, 'note' => 'Special'],
                    ['product' => $esTeh, 'quantity' => 3, 'note' => 'Es batu banyak'],
                    ['product' => $pisangGoreng, 'quantity' => 2, 'note' => 'Untuk dibawa pulang'],
                ],
            ],
            [
                'order_number' => 'ORD-2026092900011-111',
                'table' => $pendingTable,
                'customer_name' => 'Taufik Hidayat',
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'chef_id' => null,
                'completed_at' => null,
                'cancellation_reason' => null,
                'payment' => null,
                'items' => [
                    ['product' => $nasiGoreng, 'quantity' => 1, 'note' => null],
                    ['product' => $airMineral, 'quantity' => 1, 'note' => null],
                ],
            ],
            
            // Cooking Table Orders (5 orders)
            [
                'order_number' => 'ORD-20260929000002-102',
                'table' => $cookingTable,
                'customer_name' => 'Siti Aminah',
                'status' => 'completed',
                'payment_status' => 'paid',
                'chef_id' => $dapur->id,
                'completed_at' => now(),
                'cancellation_reason' => null,
                'payment' => 'cash',
                'items' => [
                    ['product' => $nasiGoreng, 'quantity' => 1, 'note' => null],
                    ['product' => $esTeh, 'quantity' => 2, 'note' => 'Es batu sedikit'],
                    ['product' => $airMineral, 'quantity' => 1, 'note' => null],
                    ['product' => $kopi, 'quantity' => 1, 'note' => null],
                    ['product' => $kacangTanah, 'quantity' => 1, 'note' => null],
                ],
            ],
            [
                'order_number' => 'ORD-2026092900012-112',
                'table' => $cookingTable,
                'customer_name' => 'Umar Farooq',
                'status' => 'completed',
                'payment_status' => 'paid',
                'chef_id' => $dapur->id,
                'completed_at' => now(),
                'cancellation_reason' => null,
                'payment' => 'qris',
                'items' => [
                    ['product' => $nasiGoreng, 'quantity' => 2, 'note' => 'Tanpa telur'],
                    ['product' => $esTeh, 'quantity' => 1, 'note' => 'Manis'],
                    ['product' => $kopi, 'quantity' => 1, 'note' => 'Panasss'],
                ],
            ],
            [
                'order_number' => 'ORD-2026092900013-113',
                'table' => $cookingTable,
                'customer_name' => 'Vivi Yuliana',
                'status' => 'completed',
                'payment_status' => 'paid',
                'chef_id' => $dapur->id,
                'completed_at' => now(),
                'cancellation_reason' => null,
                'payment' => 'cash',
                'items' => [
                    ['product' => $nasiGoreng, 'quantity' => 1, 'note' => null],
                    ['product' => $esTeh, 'quantity' => 2, 'note' => null],
                    ['product' => $airMineral, 'quantity' => 1, 'note' => null],
                    ['product' => $pisangGoreng, 'quantity' => 1, 'note' => 'Selamat pagi'],
                ],
            ],
            [
                'order_number' => 'ORD-2026092900014-114',
                'table' => $cookingTable,
                'customer_name' => 'Wahyu Saputra',
                'status' => 'completed',
                'payment_status' => 'paid',
                'chef_id' => $dapur->id,
                'completed_at' => now(),
                'cancellation_reason' => null,
                'payment' => 'bank_transfer',
                'items' => [
                    ['product' => $nasiGoreng, 'quantity' => 3, 'note' => 'Extra nasi'],
                    ['product' => $esTeh, 'quantity' => 2, 'note' => 'Less sweet'],
                    ['product' => $kopi, 'quantity' => 2, 'note' => 'Hangat'],
                    ['product' => $kacangTanah, 'quantity' => 1, 'note' => 'Untuk cemilan'],
                ],
            ],
            [
                'order_number' => 'ORD-2026092900015-115',
                'table' => $cookingTable,
                'customer_name' => 'Xavier Nathanael',
                'status' => 'completed',
                'payment_status' => 'paid',
                'chef_id' => $dapur->id,
                'completed_at' => now(),
                'cancellation_reason' => null,
                'payment' => 'ewallet',
                'items' => [
                    ['product' => $nasiGoreng, 'quantity' => 1, 'note' => null],
                    ['product' => $airMineral, 'quantity' => 2, 'note' => 'Dingin'],
                ],
            ],
            
            // Served Table Orders (5 orders)
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
                'order_number' => 'ORD-2026092900016-116',
                'table' => $servedTable,
                'customer_name' => 'Yulianti',
                'status' => 'served',
                'payment_status' => 'unpaid',
                'chef_id' => $dapur->id,
                'completed_at' => null,
                'cancellation_reason' => null,
                'payment' => null,
                'items' => [
                    ['product' => $nasiGoreng, 'quantity' => 2, 'note' => 'Special'],
                    ['product' => $esTeh, 'quantity' => 3, 'note' => 'Manis banget'],
                    ['product' => $airMineral, 'quantity' => 1, 'note' => null],
                    ['product' => $kopi, 'quantity' => 1, 'note' => 'Panasss'],
                ],
            ],
            [
                'order_number' => 'ORD-2026092900017-117',
                'table' => $servedTable,
                'customer_name' => 'Zainal Abidin',
                'status' => 'served',
                'payment_status' => 'unpaid',
                'chef_id' => $dapur->id,
                'completed_at' => null,
                'cancellation_reason' => null,
                'payment' => null,
                'items' => [
                    ['product' => $nasiGoreng, 'quantity' => 1, 'note' => null],
                    ['product' => $kopi, 'quantity' => 2, 'note' => 'Tanpa gula'],
                    ['product' => $kacangTanah, 'quantity' => 1, 'note' => 'Untuk tambahan'],
                ],
            ],
            [
                'order_number' => 'ORD-2026092900018-118',
                'table' => $servedTable,
                'customer_name' => 'Adelia Putri',
                'status' => 'served',
                'payment_status' => 'unpaid',
                'chef_id' => $dapur->id,
                'completed_at' => null,
                'cancellation_reason' => null,
                'payment' => null,
                'items' => [
                    ['product' => $nasiGoreng, 'quantity' => 1, 'note' => null],
                    ['product' => $esTeh, 'quantity' => 1, 'note' => null],
                    ['product' => $pisangGoreng, 'quantity' => 2, 'note' => 'Untuk anak-anak'],
                ],
            ],
            [
                'order_number' => 'ORD-2026092900019-119',
                'table' => $servedTable,
                'customer_name' => 'Bambang Pamungkas',
                'status' => 'served',
                'payment_status' => 'unpaid',
                'chef_id' => $dapur->id,
                'completed_at' => null,
                'cancellation_reason' => null,
                'payment' => null,
                'items' => [
                    ['product' => $nasiGoreng, 'quantity' => 2, 'note' => 'Nasi sedikit'],
                    ['product' => $airMineral, 'quantity' => 2, 'note' => null],
                    ['product' => $kopi, 'quantity' => 1, 'note' => 'Untuk semangat pagi'],
                ],
            ],
            
            // Cash Table Orders (5 orders)
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
                    ['product' => $kopi, 'quantity' => 2, 'note' => null],
                    ['product' => $kacangTanah, 'quantity' => 1, 'note' => null],
                ],
            ],
            [
                'order_number' => 'ORD-2026092900020-120',
                'table' => $cashTable,
                'customer_name' => 'Citra Lestari',
                'status' => 'completed',
                'payment_status' => 'paid',
                'chef_id' => $dapur->id,
                'completed_at' => now(),
                'cancellation_reason' => null,
                'payment' => 'cash',
                'items' => [
                    ['product' => $nasiGoreng, 'quantity' => 1, 'note' => null],
                    ['product' => $esTeh, 'quantity' => 2, 'note' => 'Less ice'],
                    ['product' => $airMineral, 'quantity' => 1, 'note' => null],
                    ['product' => $kopi, 'quantity' => 1, 'note' => 'Panasss'],
                ],
            ],
            [
                'order_number' => 'ORD-2026092900021-121',
                'table' => $cashTable,
                'customer_name' => 'Dedi Kusuma',
                'status' => 'completed',
                'payment_status' => 'paid',
                'chef_id' => $dapur->id,
                'completed_at' => now(),
                'cancellation_reason' => null,
                'payment' => 'cash',
                'items' => [
                    ['product' => $nasiGoreng, 'quantity' => 3, 'note' => 'Special'],
                    ['product' => $esTeh, 'quantity' => 1, 'note' => 'Manis'],
                    ['product' => $kopi, 'quantity' => 2, 'note' => 'Tanpa gula'],
                    ['product' => $kacangTanah, 'quantity' => 1, 'note' => 'Untuk cemilan'],
                ],
            ],
            [
                'order_number' => 'ORD-2026092900022-122',
                'table' => $cashTable,
                'customer_name' => 'Ela Novita',
                'status' => 'completed',
                'payment_status' => 'paid',
                'chef_id' => $dapur->id,
                'completed_at' => now(),
                'cancellation_reason' => null,
                'payment' => 'cash',
                'items' => [
                    ['product' => $nasiGoreng, 'quantity' => 2, 'note' => 'Extra telur'],
                    ['product' => $airMineral, 'quantity' => 3, 'note' => 'Dingin'],
                    ['product' => $pisangGoreng, 'quantity' => 1, 'note' => 'Untuk dessert'],
                ],
            ],
            [
                'order_number' => 'ORD-2026092900023-123',
                'table' => $cashTable,
                'customer_name' => 'Farhan Akbar',
                'status' => 'completed',
                'payment_status' => 'paid',
                'chef_id' => $dapur->id,
                'completed_at' => now(),
                'cancellation_reason' => null,
                'payment' => 'cash',
                'items' => [
                    ['product' => $nasiGoreng, 'quantity' => 1, 'note' => null],
                    ['product' => $esTeh, 'quantity' => 2, 'note' => null],
                    ['product' => $kopi, 'quantity' => 1, 'note' => 'Hangat'],
                ],
            ],
            
            // QRIS Table Orders (5 orders)
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
                    ['product' => $airMineral, 'quantity' => 1, 'note' => null],
                    ['product' => $kopi, 'quantity' => 1, 'note' => null],
                    ['product' => $kacangTanah, 'quantity' => 1, 'note' => null],
                ],
            ],
            [
                'order_number' => 'ORD-2026092900024-124',
                'table' => $qrisTable,
                'customer_name' => 'Gita Permata',
                'status' => 'completed',
                'payment_status' => 'paid',
                'chef_id' => $dapur->id,
                'completed_at' => now(),
                'cancellation_reason' => null,
                'payment' => 'qris',
                'items' => [
                    ['product' => $nasiGoreng, 'quantity' => 2, 'note' => 'Tanpa cabe'],
                    ['product' => $esTeh, 'quantity' => 1, 'note' => 'Manis'],
                    ['product' => $airMineral, 'quantity' => 1, 'note' => null],
                    ['product' => $kopi, 'quantity' => 1, 'note' => 'Panasss'],
                ],
            ],
            [
                'order_number' => 'ORD-2026092900025-125',
                'table' => $qrisTable,
                'customer_name' => 'Heri Susanto',
                'status' => 'completed',
                'payment_status' => 'paid',
                'chef_id' => $dapur->id,
                'completed_at' => now(),
                'cancellation_reason' => null,
                'payment' => 'qris',
                'items' => [
                    ['product' => $nasiGoreng, 'quantity' => 1, 'note' => null],
                    ['product' => $kopi, 'quantity' => 2, 'note' => 'Tanpa gula'],
                    ['product' => $kacangTanah, 'quantity' => 1, 'note' => 'Untuk tambahan'],
                ],
            ],
            [
                'order_number' => 'ORD-2026092900026-126',
                'table' => $qrisTable,
                'customer_name' => 'Indah Lestari',
                'status' => 'completed',
                'payment_status' => 'paid',
                'chef_id' => $dapur->id,
                'completed_at' => now(),
                'cancellation_reason' => null,
                'payment' => 'qris',
                'items' => [
                    ['product' => $nasiGoreng, 'quantity' => 1, 'note' => null],
                    ['product' => $esTeh, 'quantity' => 1, 'note' => null],
                    ['product' => $pisangGoreng, 'quantity' => 2, 'note' => 'Untuk anak-anak'],
                ],
            ],
            [
                'order_number' => 'ORD-2026092900027-127',
                'table' => $qrisTable,
                'customer_name' => 'Joko Widodo',
                'status' => 'completed',
                'payment_status' => 'paid',
                'chef_id' => $dapur->id,
                'completed_at' => now(),
                'cancellation_reason' => null,
                'payment' => 'qris',
                'items' => [
                    ['product' => $nasiGoreng, 'quantity' => 2, 'note' => 'Nasi sedikit'],
                    ['product' => $airMineral, 'quantity' => 2, 'note' => null],
                    ['product' => $kopi, 'quantity' => 1, 'note' => 'Untuk semangat pagi'],
                ],
            ],
            
            // Cancelled Table Orders (5 orders)
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
                    ['product' => $esTeh, 'quantity' => 2, 'note' => null],
                    ['product' => $airMineral, 'quantity' => 1, 'note' => null],
                    ['product' => $kopi, 'quantity' => 1, 'note' => null],
                    ['product' => $kacangTanah, 'quantity' => 1, 'note' => null],
                ],
            ],
            [
                'order_number' => 'ORD-2026092900028-128',
                'table' => $cancelledTable,
                'customer_name' => 'Kartini',
                'status' => 'cancelled',
                'payment_status' => 'unpaid',
                'chef_id' => null,
                'completed_at' => null,
                'cancellation_reason' => 'Pelanggan berubah pikiran, ingin pesan yang lain.',
                'payment' => null,
                'items' => [
                    ['product' => $nasiGoreng, 'quantity' => 1, 'note' => null],
                    ['product' => $esTeh, 'quantity' => 2, 'note' => 'Less sweet'],
                    ['product' => $kopi, 'quantity' => 1, 'note' => 'Panasss'],
                ],
            ],
            [
                'order_number' => 'ORD-2026092900029-129',
                'table' => $cancelledTable,
                'customer_name' => 'Lukman Hakim',
                'status' => 'cancelled',
                'payment_status' => 'unpaid',
                'chef_id' => null,
                'completed_at' => null,
                'cancellation_reason' => 'Stok habis untuk pesanan yang diminta.',
                'payment' => null,
                'items' => [
                    ['product' => $nasiGoreng, 'quantity' => 2, 'note' => 'Special'],
                    ['product' => $airMineral, 'quantity' => 1, 'note' => null],
                    ['product' => $pisangGoreng, 'quantity' => 1, 'note' => 'Untuk dibawa pulang'],
                ],
            ],
            [
                'order_number' => 'ORD-2026092900030-130',
                'table' => $cancelledTable,
                'customer_name' => 'Mega Setiawan',
                'status' => 'cancelled',
                'payment_status' => 'unpaid',
                'chef_id' => null,
                'completed_at' => null,
                'cancellation_reason' => 'Pelanggan terlambat datang, makanan sudah dingin.',
                'payment' => null,
                'items' => [
                    ['product' => $nasiGoreng, 'quantity' => 1, 'note' => null],
                    ['product' => $esTeh, 'quantity' => 2, 'note' => null],
                    ['product' => $kopi, 'quantity' => 1, 'note' => 'Hangat'],
                ],
            ],
            [
                'order_number' => 'ORD-2026092900031-131',
                'table' => $cancelledTable,
                'customer_name' => 'Nina Anggraini',
                'status' => 'cancelled',
                'payment_status' => 'unpaid',
                'chef_id' => null,
                'completed_at' => null,
                'cancellation_reason' => 'Pesanan dibatalkan karena alasan keluarga.',
                'payment' => null,
                'items' => [
                    ['product' => $nasiGoreng, 'quantity' => 3, 'note' => 'Extra nasi'],
                    ['product' => $airMineral, 'quantity' => 2, 'note' => 'Dingin'],
                    ['product' => $kacangTanah, 'quantity' => 1, 'note' => 'Untuk cemilan'],
                ],
            ],
            
            // Takeaway Orders (5 orders)
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
                'order_number' => 'ORD-2026092900032-132',
                'table' => null,
                'customer_name' => 'Oscar Fernando',
                'status' => 'ready',
                'payment_status' => 'unpaid',
                'chef_id' => $dapur->id,
                'completed_at' => null,
                'cancellation_reason' => null,
                'payment' => null,
                'items' => [
                    ['product' => $nasiGoreng, 'quantity' => 1, 'note' => null],
                    ['product' => $esTeh, 'quantity' => 2, 'note' => 'Less ice'],
                    ['product' => $airMineral, 'quantity' => 1, 'note' => null],
                    ['product' => $kopi, 'quantity' => 1, 'note' => 'Panasss'],
                ],
            ],
            [
                'order_number' => 'ORD-2026092900033-133',
                'table' => null,
                'customer_name' => 'Putri Indah Sari',
                'status' => 'ready',
                'payment_status' => 'unpaid',
                'chef_id' => $dapur->id,
                'completed_at' => null,
                'cancellation_reason' => null,
                'payment' => null,
                'items' => [
                    ['product' => $nasiGoreng, 'quantity' => 2, 'note' => 'Special'],
                    ['product' => $esTeh, 'quantity' => 1, 'note' => 'Manis'],
                    ['product' => $kopi, 'quantity' => 2, 'note' => 'Tanpa gula'],
                    ['product' => $kacangTanah, 'quantity' => 1, 'note' => 'Untuk cemilan'],
                ],
            ],
            [
                'order_number' => 'ORD-2026092900034-134',
                'table' => null,
                'customer_name' => 'Qoriah Hasanah',
                'status' => 'ready',
                'payment_status' => 'unpaid',
                'chef_id' => $dapur->id,
                'completed_at' => null,
                'cancellation_reason' => null,
                'payment' => null,
                'items' => [
                    ['product' => $nasiGoreng, 'quantity' => 2, 'note' => 'Extra telur'],
                    ['product' => $airMineral, 'quantity' => 3, 'note' => 'Dingin'],
                    ['product' => $pisangGoreng, 'quantity' => 1, 'note' => 'Untuk dessert'],
                ],
            ],
            [
                'order_number' => 'ORD-2026092900035-135',
                'table' => null,
                'customer_name' => 'Rani Kartika',
                'status' => 'ready',
                'payment_status' => 'unpaid',
                'chef_id' => $dapur->id,
                'completed_at' => null,
                'cancellation_reason' => null,
                'payment' => null,
                'items' => [
                    ['product' => $nasiGoreng, 'quantity' => 1, 'note' => null],
                    ['product' => $esTeh, 'quantity' => 2, 'note' => null],
                    ['product' => $kopi, 'quantity' => 1, 'note' => 'Hangat'],
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
                'approved_by' => null,
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
                'approved_by' => $akuntan->id,
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
            [
                'request_number' => 'PR-0003',
                'status' => 'rejected',
                'approved_by' => $akuntan->id,
                'note' => 'Harga terlalu mahal, cari supplier lain.',
                'approved_at' => now(),
                'items' => [
                    ['product' => $beras, 'quantity' => 30],
                    ['product' => $telur, 'quantity' => 150],
                    ['product' => $teh, 'quantity' => 300],
                    ['product' => $gula, 'quantity' => 750],
                    ['product' => $es, 'quantity' => 8],
                ],
            ],
            [
                'request_number' => 'PR-0004',
                'status' => 'pending_approval',
                'approved_by' => null,
                'note' => 'Menunggu persetujuan anggaran.',
                'approved_at' => null,
                'items' => [
                    ['product' => $kopi, 'quantity' => 10],
                    ['product' => $kacangTanah, 'quantity' => 20],
                    ['product' => $pisangGoreng, 'quantity' => 15],
                    ['product' => $gula, 'quantity' => 100],
                    ['product' => $teh, 'quantity' => 50],
                ],
            ],
            [
                'request_number' => 'PR-0005',
                'status' => 'funds_released',
                'approved_by' => $akuntan->id,
                'note' => 'Persetujuan untuk pembelian bahan bakar.',
                'approved_at' => now(),
                'items' => [
                    ['product' => $beras, 'quantity' => 40],
                    ['product' => $telur, 'quantity' => 180],
                    ['product' => $teh, 'quantity' => 400],
                    ['product' => $gula, 'quantity' => 600],
                    ['product' => $es, 'quantity' => 12],
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
                'user_id' => $gudang->id,
                'approved_by' => $attributes['approved_by'],
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
                    'estimated_quantity' => $item['quantity'],
                    'estimated_price' => $estimatedPrice,
                    'subtotal' => $item['quantity'] * $estimatedPrice,
                ]);
            }
        }
        $this->call(PurchaseRequestSeeder::class);
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
