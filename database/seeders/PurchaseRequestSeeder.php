<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\User;
use Illuminate\Database\Seeder;

class PurchaseRequestSeeder extends Seeder
{
    public function run(): void
    {
        $gudang = User::where('role', 'gudang')->first();
        $akuntan = User::where('role', 'akuntan')->first();

        $beras = Product::where('is_sellable', false)->skip(0)->first();
        $telur = Product::where('is_sellable', false)->skip(1)->first();
        $teh = Product::where('is_sellable', false)->skip(2)->first();
        $gula = Product::where('is_sellable', false)->skip(3)->first();
        $es = Product::where('is_sellable', false)->skip(4)->first();
        $kopi = Product::where('is_sellable', true)->skip(0)->first(); // Assuming kopi is sellable
        $kacangTanah = Product::where('is_sellable', true)->skip(1)->first(); // Assuming kacang tanah is sellable
        $pisangGoreng = Product::where('is_sellable', true)->skip(2)->first(); // Assuming pisang goreng is sellable

        // Purchase Request 1: PENDING (Ready for Akuntan to Review)
        $pr1 = PurchaseRequest::create([
            'request_number' => 'PR-20260930000001-001',
            'user_id' => $gudang->id,
            'status' => 'pending',
            'total_estimated_cost' => 120000,
        ]);

        PurchaseRequestItem::create([
            'purchase_request_id' => $pr1->id,
            'product_id' => $beras->id,
            'estimated_quantity' => 10,
            'estimated_price' => 12000,
            'subtotal' => 120000,
        ]);

        // Purchase Request 2: APPROVED (Ready for Gudang to Receive Goods)
        $pr2 = PurchaseRequest::create([
            'request_number' => 'PR-20260930000002-002',
            'user_id' => $gudang->id,
            'approved_by' => $akuntan->id,
            'status' => 'approved',
            'approved_at' => now(),
            'total_estimated_cost' => 100000,
        ]);

        PurchaseRequestItem::create([
            'purchase_request_id' => $pr2->id,
            'product_id' => $telur->id,
            'estimated_quantity' => 50,
            'estimated_price' => 2000,
            'subtotal' => 100000,
        ]);

        // Purchase Request 3: PENDING SETTLEMENT (Reimburse Case - Actual > Estimated)
        $pr3 = PurchaseRequest::create([
            'request_number' => 'PR-20260930000003-003',
            'user_id' => $gudang->id,
            'approved_by' => $akuntan->id,
            'status' => 'pending_settlement',
            'approved_at' => now()->subDay(),
            'total_estimated_cost' => 50000,
            'total_actual_cost' => 60000,
        ]);

        PurchaseRequestItem::create([
            'purchase_request_id' => $pr3->id,
            'product_id' => $beras->id,
            'estimated_quantity' => 5,
            'estimated_price' => 10000,
            'actual_quantity' => 5,
            'actual_price' => 12000,
            'subtotal' => 50000,
        ]);

        // Purchase Request 4: REJECTED (Budget issues)
        $pr4 = PurchaseRequest::create([
            'request_number' => 'PR-20260930000004-004',
            'user_id' => $gudang->id,
            'approved_by' => $akuntan->id,
            'status' => 'rejected',
            'approved_at' => now(),
            'total_estimated_cost' => 75000,
        ]);

        PurchaseRequestItem::create([
            'purchase_request_id' => $pr4->id,
            'product_id' => $teh->id,
            'estimated_quantity' => 500,
            'estimated_price' => 100,
            'subtotal' => 50000,
        ]);

        PurchaseRequestItem::create([
            'purchase_request_id' => $pr4->id,
            'product_id' => $gula->id,
            'estimated_quantity' => 1000,
            'estimated_price' => 20,
            'subtotal' => 20000,
        ]);

        PurchaseRequestItem::create([
            'purchase_request_id' => $pr4->id,
            'product_id' => $es->id,
            'estimated_quantity' => 10,
            'estimated_price' => 1500,
            'subtotal' => 15000,
        ]);

        // Purchase Request 5: FUNDS RELEASED (For snack ingredients)
        $pr5 = PurchaseRequest::create([
            'request_number' => 'PR-20260930000005-005',
            'user_id' => $gudang->id,
            'approved_by' => $akuntan->id,
            'status' => 'funds_released',
            'approved_at' => now(),
            'total_estimated_cost' => 45000,
        ]);

        PurchaseRequestItem::create([
            'purchase_request_id' => $pr5->id,
            'product_id' => $kopi->id,
            'estimated_quantity' => 10,
            'estimated_price' => 2000,
            'subtotal' => 20000,
        ]);

        PurchaseRequestItem::create([
            'purchase_request_id' => $pr5->id,
            'product_id' => $kacangTanah->id,
            'estimated_quantity' => 20,
            'estimated_price' => 500,
            'subtotal' => 10000,
        ]);

        PurchaseRequestItem::create([
            'purchase_request_id' => $pr5->id,
            'product_id' => $pisangGoreng->id,
            'estimated_quantity' => 15,
            'estimated_price' => 1000,
            'subtotal' => 15000,
        ]);
    }
}
