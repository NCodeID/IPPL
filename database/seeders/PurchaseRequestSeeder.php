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

        // Scenario 1: PENDING (Ready for Akuntan to Review)
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

        // Scenario 2: APPROVED (Ready for Gudang to Receive Goods)
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

        // Scenario 3: PENDING SETTLEMENT (Reimburse Case - Actual > Estimated)
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
    }
}
