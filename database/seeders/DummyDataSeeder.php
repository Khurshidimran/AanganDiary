<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReceipt;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockTransfer;
use App\Models\Unit;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        $units = Unit::pluck('id', 'short_code');

        $warehouses = collect([
            ['name' => 'Main Warehouse', 'code' => 'WH-MAIN', 'address' => 'Plot 12, Industrial Area, Lahore', 'contact_person' => 'Imran Sheikh', 'phone' => '0300-1234567', 'latitude' => 31.5204, 'longitude' => 74.3587],
            ['name' => 'Branch Warehouse', 'code' => 'WH-BRANCH', 'address' => 'DHA Phase 5, Lahore', 'contact_person' => 'Bilal Ahmed', 'phone' => '0300-2345678', 'latitude' => 31.4697, 'longitude' => 74.4142],
            ['name' => 'Delivery Store', 'code' => 'WH-STORE', 'address' => 'Model Town, Lahore', 'contact_person' => 'Sana Malik', 'phone' => '0300-3456789', 'latitude' => 31.4855, 'longitude' => 74.3245],
        ])->map(fn ($attrs) => Warehouse::create([...$attrs, 'status' => 'active']));

        $categories = collect([
            'Milk' => null,
            'Yogurt' => null,
            'Cheese' => null,
            'Cream' => null,
            'Butter' => null,
        ])->map(fn ($_, $name) => Category::create([
            'name' => $name,
            'slug' => Str::slug($name),
            'status' => 'active',
        ]));

        Category::create([
            'name' => 'Flavored Milk',
            'slug' => Str::slug('Flavored Milk'),
            'parent_id' => $categories['Milk']->id,
            'status' => 'active',
        ]);

        $brand = Brand::create([
            'name' => 'Aangan Dairy',
            'slug' => 'aangan-dairy',
            'status' => 'active',
        ]);

        $products = [
            [
                'name' => 'Fresh Milk',
                'category' => 'Milk',
                'unit' => 'L',
                'tax_rate' => 0,
                'variants' => [
                    ['name' => '500ml', 'sku' => 'MILK-500ML', 'purchase_price' => 55, 'sale_price' => 70, 'unit' => 'ml', 'pack_size' => 500],
                    ['name' => '1 Liter', 'sku' => 'MILK-1L', 'purchase_price' => 100, 'sale_price' => 130, 'unit' => 'L', 'pack_size' => 1],
                    ['name' => '2 Liter', 'sku' => 'MILK-2L', 'purchase_price' => 190, 'sale_price' => 250, 'unit' => 'L', 'pack_size' => 2],
                ],
            ],
            [
                'name' => 'Plain Yogurt',
                'category' => 'Yogurt',
                'unit' => 'kg',
                'tax_rate' => 0,
                'variants' => [
                    ['name' => '500g', 'sku' => 'YOG-500G', 'purchase_price' => 60, 'sale_price' => 85, 'unit' => 'g', 'pack_size' => 500],
                    ['name' => '1kg', 'sku' => 'YOG-1KG', 'purchase_price' => 110, 'sale_price' => 150, 'unit' => 'kg', 'pack_size' => 1],
                ],
            ],
            [
                'name' => 'Paneer (Cottage Cheese)',
                'category' => 'Cheese',
                'unit' => 'g',
                'tax_rate' => 5,
                'variants' => [
                    ['name' => '250g', 'sku' => 'PANEER-250G', 'purchase_price' => 150, 'sale_price' => 200, 'unit' => 'g', 'pack_size' => 250],
                    ['name' => '500g', 'sku' => 'PANEER-500G', 'purchase_price' => 280, 'sale_price' => 380, 'unit' => 'g', 'pack_size' => 500],
                ],
            ],
            [
                'name' => 'Fresh Cream',
                'category' => 'Cream',
                'unit' => 'g',
                'tax_rate' => 0,
                'variants' => [
                    ['name' => '250g', 'sku' => 'CREAM-250G', 'purchase_price' => 120, 'sale_price' => 160, 'unit' => 'g', 'pack_size' => 250],
                ],
            ],
            [
                'name' => 'Desi Butter',
                'category' => 'Butter',
                'unit' => 'g',
                'tax_rate' => 5,
                'variants' => [
                    ['name' => '250g', 'sku' => 'BUTTER-250G', 'purchase_price' => 350, 'sale_price' => 450, 'unit' => 'g', 'pack_size' => 250],
                    ['name' => '500g', 'sku' => 'BUTTER-500G', 'purchase_price' => 680, 'sale_price' => 880, 'unit' => 'g', 'pack_size' => 500],
                ],
            ],
        ];

        $variantsBySku = collect();

        foreach ($products as $productData) {
            $product = Product::create([
                'category_id' => $categories[$productData['category']]->id,
                'brand_id' => $brand->id,
                'name' => $productData['name'],
                'slug' => Str::slug($productData['name']),
                'description' => "{$productData['name']} sourced fresh and delivered by Aangan Dairy.",
                'unit_id' => $units[$productData['unit']],
                'tax_rate' => $productData['tax_rate'],
                'supply_type' => 'purchased',
                'track_inventory' => true,
                'track_batch' => true,
                'track_expiry' => true,
                'status' => 'active',
            ]);

            foreach ($productData['variants'] as $variant) {
                $createdVariant = $product->variants()->create([
                    ...collect($variant)->except('unit')->all(),
                    'unit_id' => $units[$variant['unit']],
                    'is_active' => true,
                ]);

                $variantsBySku[$variant['sku']] = $createdVariant;
            }
        }

        $vendors = collect([
            ['name' => 'Punjab Dairy Supplies', 'company_name' => 'Punjab Dairy Supplies (Pvt) Ltd', 'contact_person' => 'Tariq Mehmood', 'phone' => '0321-1112233', 'email' => 'sales@punjabdairy.pk', 'payment_terms' => 'Net 15'],
            ['name' => 'Lahore Fresh Foods', 'company_name' => 'Lahore Fresh Foods', 'contact_person' => 'Ayesha Riaz', 'phone' => '0321-2223344', 'email' => 'contact@lahorefreshfoods.pk', 'payment_terms' => 'Net 30'],
            ['name' => 'Al-Barakah Traders', 'company_name' => 'Al-Barakah Traders', 'contact_person' => 'Usman Ali', 'phone' => '0321-3334455', 'email' => 'usman@albarakah.pk', 'payment_terms' => 'Cash on Delivery'],
        ])->map(fn ($attrs) => Vendor::create([...$attrs, 'opening_balance' => 0, 'status' => 'active']));

        $mainWarehouse = $warehouses->firstWhere('name', 'Main Warehouse');
        $branchWarehouse = $warehouses->firstWhere('name', 'Branch Warehouse');
        $deliveryStore = $warehouses->firstWhere('name', 'Delivery Store');
        $admin = User::where('email', 'dairyaangan@gmail.com')->first();
        $inventory = app(InventoryService::class);

        // PO 1: fully received, against Punjab Dairy Supplies.
        $po1 = PurchaseOrder::create([
            'po_number' => 'PO-000001',
            'vendor_id' => $vendors[0]->id,
            'warehouse_id' => $mainWarehouse->id,
            'status' => PurchaseOrder::STATUS_APPROVED,
            'order_date' => now()->subDays(5),
            'expected_date' => now()->subDays(2),
            'created_by' => $admin->id,
        ]);

        $po1Lines = [
            ['sku' => 'MILK-1L', 'qty' => 200, 'cost' => 95],
            ['sku' => 'BUTTER-250G', 'qty' => 100, 'cost' => 340],
        ];

        foreach ($po1Lines as $line) {
            $po1->items()->create([
                'product_variant_id' => $variantsBySku[$line['sku']]->id,
                'quantity_ordered' => $line['qty'],
                'unit_cost' => $line['cost'],
            ]);
        }

        $receipt1 = PurchaseReceipt::create([
            'receipt_number' => 'GRN-000001',
            'purchase_order_id' => $po1->id,
            'vendor_id' => $po1->vendor_id,
            'warehouse_id' => $po1->warehouse_id,
            'receipt_date' => now()->subDays(2),
            'invoice_number' => 'INV-PDS-1042',
            'received_by' => $admin->id,
        ]);

        $receipt1Total = 0;

        foreach ($po1->items as $poItem) {
            $lineCost = $poItem->quantity_ordered * $poItem->unit_cost;
            $receipt1Total += $lineCost;

            $receipt1->items()->create([
                'purchase_order_item_id' => $poItem->id,
                'product_variant_id' => $poItem->product_variant_id,
                'quantity' => $poItem->quantity_ordered,
                'unit_cost' => $poItem->unit_cost,
                'total_cost' => $lineCost,
                'batch_number' => 'BATCH-'.now()->format('Ymd').'-01',
                'manufacturing_date' => now()->subDays(3),
                'expiry_date' => now()->addDays(11),
            ]);

            $poItem->update(['quantity_received' => $poItem->quantity_ordered]);

            $inventory->postTransaction(
                variant: $poItem->productVariant,
                warehouse: $po1->warehouse,
                transactionType: InventoryTransaction::TYPE_PURCHASE_RECEIPT,
                quantity: (float) $poItem->quantity_ordered,
                batchNumber: 'BATCH-'.now()->format('Ymd').'-01',
                referenceType: 'purchase_receipt',
                referenceId: $receipt1->id,
                notes: "Received against {$po1->po_number}",
                expiryDate: now()->addDays(11)->toDateString(),
            );
        }

        $receipt1->update(['total_cost' => $receipt1Total]);
        $po1->update(['status' => PurchaseOrder::STATUS_FULLY_RECEIVED]);

        $vendors[0]->payments()->create([
            'amount' => 10000,
            'payment_date' => now()->subDay(),
            'method' => 'Bank Transfer',
            'reference_number' => 'TXN-88213',
            'created_by' => $admin->id,
        ]);

        // PO 2: partially received, against Lahore Fresh Foods.
        $po2 = PurchaseOrder::create([
            'po_number' => 'PO-000002',
            'vendor_id' => $vendors[1]->id,
            'warehouse_id' => $branchWarehouse->id,
            'status' => PurchaseOrder::STATUS_APPROVED,
            'order_date' => now()->subDays(3),
            'expected_date' => now()->addDay(),
            'created_by' => $admin->id,
        ]);

        $po2Item = $po2->items()->create([
            'product_variant_id' => $variantsBySku['YOG-500G']->id,
            'quantity_ordered' => 150,
            'unit_cost' => 58,
        ]);

        $receipt2 = PurchaseReceipt::create([
            'receipt_number' => 'GRN-000002',
            'purchase_order_id' => $po2->id,
            'vendor_id' => $po2->vendor_id,
            'warehouse_id' => $po2->warehouse_id,
            'receipt_date' => now()->subDay(),
            'invoice_number' => 'INV-LFF-2210',
            'received_by' => $admin->id,
        ]);

        $partialQty = 80;
        $receipt2->items()->create([
            'purchase_order_item_id' => $po2Item->id,
            'product_variant_id' => $po2Item->product_variant_id,
            'quantity' => $partialQty,
            'unit_cost' => $po2Item->unit_cost,
            'total_cost' => $partialQty * $po2Item->unit_cost,
            'batch_number' => 'BATCH-'.now()->format('Ymd').'-02',
            'manufacturing_date' => now()->subDays(2),
            'expiry_date' => now()->addDays(9),
        ]);

        $po2Item->update(['quantity_received' => $partialQty]);

        $inventory->postTransaction(
            variant: $po2Item->productVariant,
            warehouse: $po2->warehouse,
            transactionType: InventoryTransaction::TYPE_PURCHASE_RECEIPT,
            quantity: (float) $partialQty,
            batchNumber: 'BATCH-'.now()->format('Ymd').'-02',
            referenceType: 'purchase_receipt',
            referenceId: $receipt2->id,
            notes: "Received against {$po2->po_number}",
            expiryDate: now()->addDays(9)->toDateString(),
        );

        $receipt2->update(['total_cost' => $partialQty * $po2Item->unit_cost]);
        $po2->update(['status' => PurchaseOrder::STATUS_PARTIALLY_RECEIVED]);

        // PO 3: awaiting approval, against Al-Barakah Traders — nothing received yet.
        $po3 = PurchaseOrder::create([
            'po_number' => 'PO-000003',
            'vendor_id' => $vendors[2]->id,
            'warehouse_id' => $deliveryStore->id,
            'status' => PurchaseOrder::STATUS_SUBMITTED,
            'order_date' => now(),
            'expected_date' => now()->addDays(3),
            'created_by' => $admin->id,
        ]);

        $po3->items()->create([
            'product_variant_id' => $variantsBySku['PANEER-250G']->id,
            'quantity_ordered' => 80,
            'unit_cost' => 145,
        ]);

        // Transfer 1: completed, Main Warehouse -> Branch Warehouse.
        $transfer1 = StockTransfer::create([
            'transfer_number' => 'ST-000001',
            'from_warehouse_id' => $mainWarehouse->id,
            'to_warehouse_id' => $branchWarehouse->id,
            'status' => StockTransfer::STATUS_IN_TRANSIT,
            'transfer_date' => now()->subDay(),
            'created_by' => $admin->id,
            'approved_by' => $admin->id,
            'dispatched_by' => $admin->id,
            'dispatched_at' => now()->subDay(),
        ]);

        $transfer1Item = $transfer1->items()->create([
            'product_variant_id' => $variantsBySku['MILK-1L']->id,
            'batch_number' => 'BATCH-'.now()->format('Ymd').'-01',
            'quantity' => 40,
            'unit_cost' => 95,
        ]);

        $inventory->postTransaction(
            variant: $transfer1Item->productVariant,
            warehouse: $mainWarehouse,
            transactionType: InventoryTransaction::TYPE_STOCK_TRANSFER_OUT,
            quantity: -40,
            batchNumber: $transfer1Item->batch_number,
            referenceType: 'stock_transfer',
            referenceId: $transfer1->id,
            notes: "Dispatched on {$transfer1->transfer_number}",
        );

        $inventory->postTransaction(
            variant: $transfer1Item->productVariant,
            warehouse: $branchWarehouse,
            transactionType: InventoryTransaction::TYPE_STOCK_TRANSFER_IN,
            quantity: 40,
            batchNumber: $transfer1Item->batch_number,
            referenceType: 'stock_transfer',
            referenceId: $transfer1->id,
            notes: "Received from {$transfer1->transfer_number}",
            expiryDate: now()->addDays(11)->toDateString(),
        );

        $transfer1->update([
            'status' => StockTransfer::STATUS_RECEIVED,
            'received_by' => $admin->id,
            'received_at' => now(),
        ]);

        // Transfer 2: still in draft, awaiting request/approval.
        $transfer2 = StockTransfer::create([
            'transfer_number' => 'ST-000002',
            'from_warehouse_id' => $mainWarehouse->id,
            'to_warehouse_id' => $deliveryStore->id,
            'status' => StockTransfer::STATUS_DRAFT,
            'transfer_date' => now(),
            'created_by' => $admin->id,
        ]);

        $transfer2->items()->create([
            'product_variant_id' => $variantsBySku['BUTTER-250G']->id,
            'batch_number' => 'BATCH-'.now()->format('Ymd').'-01',
            'quantity' => 20,
            'unit_cost' => 340,
        ]);

        // A posted wastage adjustment, e.g. a few damaged butter tubs found during a stock check.
        $adjustment1 = StockAdjustment::create([
            'adjustment_number' => 'SA-000001',
            'warehouse_id' => $mainWarehouse->id,
            'status' => StockAdjustment::STATUS_POSTED,
            'adjustment_date' => now()->subHours(6),
            'created_by' => $admin->id,
            'posted_by' => $admin->id,
            'posted_at' => now()->subHours(6),
        ]);

        $adjustment1Item = $adjustment1->items()->create([
            'product_variant_id' => $variantsBySku['BUTTER-250G']->id,
            'batch_number' => 'BATCH-'.now()->format('Ymd').'-01',
            'direction' => StockAdjustmentItem::DIRECTION_DECREASE,
            'reason' => StockAdjustmentItem::REASON_DAMAGE,
            'quantity' => 5,
            'notes' => 'Damaged tubs found during shelf check.',
        ]);

        $inventory->postTransaction(
            variant: $adjustment1Item->productVariant,
            warehouse: $adjustment1->warehouse,
            transactionType: InventoryTransaction::TYPE_DAMAGE,
            quantity: -5,
            batchNumber: $adjustment1Item->batch_number,
            referenceType: 'stock_adjustment',
            referenceId: $adjustment1->id,
            notes: $adjustment1Item->notes,
        );

        // A still-draft adjustment awaiting review before posting.
        $adjustment2 = StockAdjustment::create([
            'adjustment_number' => 'SA-000002',
            'warehouse_id' => $branchWarehouse->id,
            'status' => StockAdjustment::STATUS_DRAFT,
            'adjustment_date' => now(),
            'notes' => 'Cycle count follow-up for yogurt batch.',
            'created_by' => $admin->id,
        ]);

        $adjustment2->items()->create([
            'product_variant_id' => $variantsBySku['YOG-500G']->id,
            'batch_number' => 'BATCH-'.now()->format('Ymd').'-02',
            'direction' => StockAdjustmentItem::DIRECTION_DECREASE,
            'reason' => StockAdjustmentItem::REASON_CORRECTION,
            'quantity' => 3,
            'notes' => 'Short count found during cycle count.',
        ]);

        // Order 1: fresh from Shopify, pending review and payment.
        $order1 = Order::create([
            'shopify_order_id' => '5001001',
            'shopify_order_number' => '#1001',
            'customer_name' => 'Ayesha Khan',
            'customer_email' => 'ayesha.khan@example.com',
            'customer_phone' => '0300-1112233',
            'billing_address' => ['address1' => 'House 12, Street 5', 'city' => 'Lahore', 'province' => 'Punjab', 'country' => 'Pakistan', 'zip' => '54000'],
            'shipping_address' => ['address1' => 'House 12, Street 5', 'city' => 'Lahore', 'province' => 'Punjab', 'country' => 'Pakistan', 'zip' => '54000'],
            'order_status' => Order::ORDER_STATUS_PENDING,
            'payment_status' => Order::PAYMENT_STATUS_PENDING,
            'delivery_status' => Order::DELIVERY_STATUS_PENDING,
            'currency' => 'PKR',
            'subtotal' => 260,
            'tax_total' => 0,
            'shipping_total' => 100,
            'total' => 360,
            'total_outstanding' => 360,
            'shopify_created_at' => now()->subHours(3),
        ]);

        $order1->items()->create([
            'product_variant_id' => $variantsBySku['MILK-1L']->id,
            'shopify_variant_id' => '888101',
            'sku' => 'MILK-1L',
            'product_name' => 'Fresh Milk - 1 Liter',
            'quantity' => 2,
            'unit_price' => 130,
            'total_price' => 260,
        ]);

        // Order 2: confirmed and paid, ready for dispatch once that module exists.
        $order2 = Order::create([
            'shopify_order_id' => '5001002',
            'shopify_order_number' => '#1002',
            'customer_name' => 'Bilal Ahmed',
            'customer_email' => 'bilal.ahmed@example.com',
            'customer_phone' => '0300-2223344',
            'billing_address' => ['address1' => 'Flat 3B, DHA Phase 5', 'city' => 'Lahore', 'province' => 'Punjab', 'country' => 'Pakistan', 'zip' => '54810'],
            'shipping_address' => ['address1' => 'Flat 3B, DHA Phase 5', 'city' => 'Lahore', 'province' => 'Punjab', 'country' => 'Pakistan', 'zip' => '54810'],
            'order_status' => Order::ORDER_STATUS_CONFIRMED,
            'payment_status' => Order::PAYMENT_STATUS_PAID,
            'delivery_status' => Order::DELIVERY_STATUS_PENDING,
            'currency' => 'PKR',
            'subtotal' => 535,
            'tax_total' => 0,
            'shipping_total' => 100,
            'total' => 635,
            'total_outstanding' => 0,
            'shopify_created_at' => now()->subDay(),
        ]);

        $order2->items()->create([
            'product_variant_id' => $variantsBySku['YOG-500G']->id,
            'shopify_variant_id' => '888102',
            'sku' => 'YOG-500G',
            'product_name' => 'Plain Yogurt - 500g',
            'quantity' => 3,
            'unit_price' => 85,
            'total_price' => 255,
        ]);

        $order2->items()->create([
            'product_variant_id' => $variantsBySku['BUTTER-250G']->id,
            'shopify_variant_id' => '888103',
            'sku' => 'BUTTER-250G',
            'product_name' => 'Desi Butter - 250g',
            'quantity' => 1,
            'unit_price' => 280,
            'total_price' => 280,
        ]);

        // Order 3: cancelled on Shopify before payment.
        $order3 = Order::create([
            'shopify_order_id' => '5001003',
            'shopify_order_number' => '#1003',
            'customer_name' => 'Sana Malik',
            'customer_email' => 'sana.malik@example.com',
            'order_status' => Order::ORDER_STATUS_CANCELLED,
            'payment_status' => Order::PAYMENT_STATUS_PENDING,
            'delivery_status' => Order::DELIVERY_STATUS_PENDING,
            'currency' => 'PKR',
            'subtotal' => 150,
            'tax_total' => 0,
            'shipping_total' => 100,
            'total' => 250,
            'total_outstanding' => 250,
            'shopify_created_at' => now()->subDays(2),
        ]);

        $order3->items()->create([
            'product_variant_id' => null,
            'shopify_variant_id' => '888104',
            'sku' => 'CREAM-250G-DISCONTINUED',
            'product_name' => 'Discontinued Cream Variant',
            'quantity' => 1,
            'unit_price' => 150,
            'total_price' => 150,
        ]);

        $reviewUsers = [
            ['name' => 'Warehouse Manager Demo', 'email' => 'warehouse.manager@aangandairy.com', 'role' => 'Warehouse Manager'],
            ['name' => 'Dispatch Manager Demo', 'email' => 'dispatch.manager@aangandairy.com', 'role' => 'Dispatch Manager'],
            ['name' => 'Accounts User Demo', 'email' => 'accounts@aangandairy.com', 'role' => 'Accounts User'],
            ['name' => 'Purchasing User Demo', 'email' => 'purchasing@aangandairy.com', 'role' => 'Purchasing User'],
            ['name' => 'Admin Demo', 'email' => 'admin@aangandairy.com', 'role' => 'Admin'],
        ];

        foreach ($reviewUsers as $reviewUser) {
            $user = User::create([
                'name' => $reviewUser['name'],
                'email' => $reviewUser['email'],
                'password' => 'password',
                'status' => 'active',
                'email_verified_at' => now(),
            ]);

            $user->assignRole($reviewUser['role']);
        }

        $this->command->warn('Demo users seeded with password: password');
    }
}
