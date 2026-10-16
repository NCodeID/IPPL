<?php

namespace Database\Seeders;

use App\Models\CashCategory;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductRecipe;
use App\Models\Role;
use App\Models\Table as TableModel;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedRolesAndUsers();
        $this->call(PermissionSeeder::class);
        $this->seedCategoriesAndProducts();
        $this->seedUnitsAndIngredients();
        $this->seedProductRecipes();
        $this->seedTablesAndPaymentMethods();
        $this->seedCashCategories();
    }

    private function seedRolesAndUsers(): void
    {
        $roles = ['admin', 'akuntan', 'kasir', 'dapur', 'runner', 'gudang'];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        $users = [
            ['name' => 'Admin', 'email' => 'admin@pos.com', 'role_id' => 1],
            ['name' => 'Kasir', 'email' => 'kasir@pos.com', 'role_id' => 3],
            ['name' => 'Runner', 'email' => 'runner@pos.com', 'role_id' => 5],
            ['name' => 'Dapur', 'email' => 'dapur@pos.com', 'role_id' => 4],
            ['name' => 'Gudang', 'email' => 'gudang@pos.com', 'role_id' => 6],
            ['name' => 'Akuntan', 'email' => 'akuntan@pos.com', 'role_id' => 2],
        ];

        foreach ($users as $user) {
            User::firstOrCreate(
                ['email' => $user['email']],
                ['name' => $user['name'], 'password' => Hash::make('password'), 'role_id' => $user['role_id'], 'is_active' => true]
            );
        }
    }

    private function seedCategoriesAndProducts(): void
    {
        $categories = [
            ['name' => 'Main Course', 'is_active' => true],
            ['name' => 'Beverage', 'is_active' => true],
        ];

        foreach ($categories as $cat) {
            Category::firstOrCreate(['name' => $cat['name']], $cat);
        }

        $mainCourse = Category::where('name', 'Main Course')->first();
        $beverage = Category::where('name', 'Beverage')->first();

        $products = [
            ['category_id' => $mainCourse->id, 'name' => 'Nasi Goreng', 'description' => 'Nasi goreng spesial', 'price' => 25000, 'is_available' => true, 'is_active' => true],
            ['category_id' => $mainCourse->id, 'name' => 'Mie Goreng', 'description' => 'Mie goreng spesial', 'price' => 22000, 'is_available' => true, 'is_active' => true],
            ['category_id' => $mainCourse->id, 'name' => 'Nasi Uduk', 'description' => 'Nasi uduk komplit', 'price' => 20000, 'is_available' => true, 'is_active' => true],
            ['category_id' => $beverage->id, 'name' => 'Es Teh Manis', 'description' => 'Es teh manis segar', 'price' => 5000, 'is_available' => true, 'is_active' => true],
            ['category_id' => $beverage->id, 'name' => 'Es Jeruk', 'description' => 'Es jeruk peras', 'price' => 7000, 'is_available' => true, 'is_active' => true],
            ['category_id' => $beverage->id, 'name' => 'Kopi Hitam', 'description' => 'Kopi hitam panas', 'price' => 8000, 'is_available' => true, 'is_active' => true],
        ];

        foreach ($products as $prod) {
            Product::firstOrCreate(['name' => $prod['name']], $prod);
        }
    }

    private function seedUnitsAndIngredients(): void
    {
        $units = [
            ['code' => 'kg', 'name' => 'Kilogram'],
            ['code' => 'gram', 'name' => 'Gram'],
            ['code' => 'butir', 'name' => 'Butir'],
            ['code' => 'liter', 'name' => 'Liter'],
            ['code' => 'pcs', 'name' => 'Piece'],
        ];

        foreach ($units as $unit) {
            Unit::firstOrCreate(['code' => $unit['code']], $unit);
        }

        $kg = Unit::where('code', 'kg')->first();
        $gram = Unit::where('code', 'gram')->first();
        $butir = Unit::where('code', 'butir')->first();
        $liter = Unit::where('code', 'liter')->first();

        $ingredients = [
            ['unit_id' => $kg->id, 'name' => 'Beras', 'unit_cost' => 12000, 'stock' => 50.00, 'is_active' => true],
            ['unit_id' => $kg->id, 'name' => 'Mie', 'unit_cost' => 18000, 'stock' => 20.00, 'is_active' => true],
            ['unit_id' => $butir->id, 'name' => 'Telur', 'unit_cost' => 2500, 'stock' => 200.00, 'is_active' => true],
            ['unit_id' => $kg->id, 'name' => 'Ayam', 'unit_cost' => 35000, 'stock' => 15.00, 'is_active' => true],
            ['unit_id' => $liter->id, 'name' => 'Minyak Goreng', 'unit_cost' => 15000, 'stock' => 10.00, 'is_active' => true],
            ['unit_id' => $gram->id, 'name' => 'Bawang Merah', 'unit_cost' => 30000, 'stock' => 5.00, 'is_active' => true],
            ['unit_id' => $gram->id, 'name' => 'Bawang Putih', 'unit_cost' => 25000, 'stock' => 5.00, 'is_active' => true],
            ['unit_id' => $gram->id, 'name' => 'Cabai', 'unit_cost' => 40000, 'stock' => 3.00, 'is_active' => true],
            ['unit_id' => $gram->id, 'name' => 'Kopi Bubuk', 'unit_cost' => 80000, 'stock' => 2.00, 'is_active' => true],
            ['unit_id' => $butir->id, 'name' => 'Jeruk', 'unit_cost' => 3000, 'stock' => 100.00, 'is_active' => true],
            ['unit_id' => $kg->id, 'name' => 'Gula', 'unit_cost' => 14000, 'stock' => 10.00, 'is_active' => true],
            ['unit_id' => $liter->id, 'name' => 'Teh Celup', 'unit_cost' => 20000, 'stock' => 5.00, 'is_active' => true],
        ];

        foreach ($ingredients as $ing) {
            Ingredient::firstOrCreate(['name' => $ing['name']], $ing);
        }
    }

    private function seedProductRecipes(): void
    {
        $nasiGoreng = Product::where('name', 'Nasi Goreng')->first();
        $mieGoreng = Product::where('name', 'Mie Goreng')->first();
        $nasiUduk = Product::where('name', 'Nasi Uduk')->first();
        $esTeh = Product::where('name', 'Es Teh Manis')->first();
        $esJeruk = Product::where('name', 'Es Jeruk')->first();
        $kopi = Product::where('name', 'Kopi Hitam')->first();

        $beras = Ingredient::where('name', 'Beras')->first();
        $mie = Ingredient::where('name', 'Mie')->first();
        $telur = Ingredient::where('name', 'Telur')->first();
        $ayam = Ingredient::where('name', 'Ayam')->first();
        $minyak = Ingredient::where('name', 'Minyak Goreng')->first();
        $bawangMerah = Ingredient::where('name', 'Bawang Merah')->first();
        $bawangPutih = Ingredient::where('name', 'Bawang Putih')->first();
        $cabai = Ingredient::where('name', 'Cabai')->first();
        $kopiBubuk = Ingredient::where('name', 'Kopi Bubuk')->first();
        $jeruk = Ingredient::where('name', 'Jeruk')->first();
        $gula = Ingredient::where('name', 'Gula')->first();
        $teh = Ingredient::where('name', 'Teh Celup')->first();

        $recipes = [
            ['product_id' => $nasiGoreng->id, 'ingredient_id' => $beras->id, 'quantity_required' => 0.20],
            ['product_id' => $nasiGoreng->id, 'ingredient_id' => $telur->id, 'quantity_required' => 2],
            ['product_id' => $nasiGoreng->id, 'ingredient_id' => $minyak->id, 'quantity_required' => 0.05],
            ['product_id' => $nasiGoreng->id, 'ingredient_id' => $bawangMerah->id, 'quantity_required' => 30],
            ['product_id' => $nasiGoreng->id, 'ingredient_id' => $bawangPutih->id, 'quantity_required' => 20],
            ['product_id' => $nasiGoreng->id, 'ingredient_id' => $cabai->id, 'quantity_required' => 10],

            ['product_id' => $mieGoreng->id, 'ingredient_id' => $mie->id, 'quantity_required' => 0.20],
            ['product_id' => $mieGoreng->id, 'ingredient_id' => $telur->id, 'quantity_required' => 1],
            ['product_id' => $mieGoreng->id, 'ingredient_id' => $minyak->id, 'quantity_required' => 0.05],
            ['product_id' => $mieGoreng->id, 'ingredient_id' => $bawangMerah->id, 'quantity_required' => 30],
            ['product_id' => $mieGoreng->id, 'ingredient_id' => $bawangPutih->id, 'quantity_required' => 20],
            ['product_id' => $mieGoreng->id, 'ingredient_id' => $cabai->id, 'quantity_required' => 10],

            ['product_id' => $nasiUduk->id, 'ingredient_id' => $beras->id, 'quantity_required' => 0.20],
            ['product_id' => $nasiUduk->id, 'ingredient_id' => $ayam->id, 'quantity_required' => 0.05],
            ['product_id' => $nasiUduk->id, 'ingredient_id' => $minyak->id, 'quantity_required' => 0.03],

            ['product_id' => $esTeh->id, 'ingredient_id' => $teh->id, 'quantity_required' => 0.02],
            ['product_id' => $esTeh->id, 'ingredient_id' => $gula->id, 'quantity_required' => 15],
            ['product_id' => $esTeh->id, 'ingredient_id' => $jeruk->id, 'quantity_required' => 0.25],

            ['product_id' => $esJeruk->id, 'ingredient_id' => $jeruk->id, 'quantity_required' => 0.50],
            ['product_id' => $esJeruk->id, 'ingredient_id' => $gula->id, 'quantity_required' => 20],

            ['product_id' => $kopi->id, 'ingredient_id' => $kopiBubuk->id, 'quantity_required' => 0.015],
            ['product_id' => $kopi->id, 'ingredient_id' => $gula->id, 'quantity_required' => 10],
        ];

        foreach ($recipes as $recipe) {
            ProductRecipe::firstOrCreate(
                ['product_id' => $recipe['product_id'], 'ingredient_id' => $recipe['ingredient_id']],
                $recipe
            );
        }
    }

    private function seedTablesAndPaymentMethods(): void
    {
        for ($i = 1; $i <= 6; $i++) {
            TableModel::firstOrCreate(['number' => $i], ['number' => $i, 'is_active' => true]);
        }

        $paymentMethods = [
            ['name' => 'Cash - Rupiah', 'type' => 'cash', 'is_active' => true],
            ['name' => 'QRIS - BCA', 'type' => 'qris', 'is_active' => true],
            ['name' => 'Transfer - BCA', 'type' => 'transfer', 'is_active' => true],
            ['name' => 'Debit Card', 'type' => 'card', 'is_active' => true],
        ];

        foreach ($paymentMethods as $pm) {
            PaymentMethod::firstOrCreate(['name' => $pm['name']], $pm);
        }
    }

    private function seedCashCategories(): void
    {
        $categories = [
            ['name' => 'Penjualan', 'type' => 'in', 'is_active' => true],
            ['name' => 'Pendapatan Lain', 'type' => 'in', 'is_active' => true],
            ['name' => 'Pembelian Bahan Baku', 'type' => 'out', 'is_active' => true],
            ['name' => 'Gaji', 'type' => 'out', 'is_active' => true],
            ['name' => 'Operasional', 'type' => 'out', 'is_active' => true],
        ];

        foreach ($categories as $cat) {
            CashCategory::firstOrCreate(['name' => $cat['name']], $cat);
        }
    }
}
