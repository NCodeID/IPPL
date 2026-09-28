<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Table;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(UserSeeder::class);
        
        Table::factory(50)->create();
        Category::factory(50)->create();
        PaymentMethod::factory(50)->create();
        Product::factory(50)->create();
        Order::factory(50)->create();
        OrderItem::factory(50)->create();
        Payment::factory(50)->create();
    }
}
