<?php

namespace Database\Seeders;

use App\Models\Channel;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\RiderProfile;
use App\Models\RiderWalletTransaction;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\DispatchService;
use App\Services\OrderFulfillmentService;
use App\Services\RiderTripService;
use App\Services\RiderWalletService;
use App\Services\SettingsService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Demo data for the Rider Account / Rider Settlement page: three riders,
 * a spread of orders across every delivery status, a showcase multi-attempt
 * order (order #1050 — the exact example used in the feature spec: attempt 1
 * returned by Rider A, attempt 2 delivered by Rider B), partial cash
 * deposits/payouts so both balances are non-zero, and one completed + one
 * still-open trip. Every transition goes through DispatchService/
 * RiderWalletService/RiderTripService exactly like real traffic would, so
 * delivery_attempts/rider_wallet_transactions/rider_trips all come out
 * consistent with production behavior — nothing here is hand-inserted.
 */
class RiderAccountDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (User::where('email', 'ali.raza@aangandairy.demo')->exists()) {
            $this->command->warn('RiderAccountDemoSeeder already ran — skipping.');

            return;
        }

        DB::transaction(function () {
            $warehouse = Warehouse::where('code', 'WH-MAIN')->firstOrFail();

            // Never set by any other seeder — OrderFulfillmentService::allocateStock()
            // needs it to know which warehouse to allocate against.
            app(SettingsService::class)->set('default_warehouse_id', $warehouse->id, 'inventory');

            $milk = ProductVariant::where('sku', 'MILK-1L')->firstOrFail();
            $butter = ProductVariant::where('sku', 'BUTTER-250G')->firstOrFail();
            $phoneChannel = Channel::where('code', 'phone')->first();
            $admin = User::where('email', 'dairyaangan@gmail.com')->first();

            $riderA = $this->makeRider('Ali Raza', 'ali.raza@aangandairy.demo', $warehouse, 100, checkedIn: true);
            $riderB = $this->makeRider('Bilal Hussain', 'bilal.hussain@aangandairy.demo', $warehouse, 120, checkedIn: true);
            $riderC = $this->makeRider('Sana Tariq', 'sana.tariq@aangandairy.demo', $warehouse, 90, checkedIn: false);

            $fulfillment = app(OrderFulfillmentService::class);
            $dispatch = app(DispatchService::class);
            $wallet = app(RiderWalletService::class);
            $trips = app(RiderTripService::class);

            $makeOrder = function (string $number, string $customer, string $phone, ProductVariant $variant, int $qty) use ($phoneChannel) {
                $unitPrice = (float) $variant->sale_price;
                $subtotal = $qty * $unitPrice;

                $order = Order::create([
                    'shopify_order_id' => 'local-'.Str::uuid(),
                    'shopify_order_number' => "#{$number}",
                    'channel_id' => $phoneChannel?->id,
                    'customer_name' => $customer,
                    'customer_phone' => $phone,
                    'billing_address' => ['address1' => 'Demo Street 1', 'city' => 'Lahore', 'country' => 'Pakistan'],
                    'shipping_address' => ['address1' => 'Demo Street 1', 'city' => 'Lahore', 'country' => 'Pakistan'],
                    'order_status' => Order::ORDER_STATUS_PENDING,
                    'payment_status' => Order::PAYMENT_STATUS_PENDING,
                    'delivery_status' => Order::DELIVERY_STATUS_PENDING,
                    'currency' => 'PKR',
                    'subtotal' => $subtotal,
                    'tax_total' => 0,
                    'shipping_total' => 0,
                    'total' => $subtotal,
                    'total_outstanding' => $subtotal,
                    'shopify_created_at' => now()->subHours(random_int(2, 60)),
                ]);

                $order->items()->create([
                    'product_variant_id' => $variant->id,
                    'sku' => $variant->sku,
                    'product_name' => $variant->name,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'total_price' => $qty * $unitPrice,
                ]);

                return $order;
            };

            $confirm = function (Order $order) use ($fulfillment) {
                $fulfillment->allocateStock($order);
                $order->update(['order_status' => Order::ORDER_STATUS_CONFIRMED]);
            };

            // --- The showcase order: same order number throughout, two attempts,
            // two different riders — the exact worked example from the spec. ---
            $order1050 = $makeOrder('1050', 'Ahmed Khan', '0300-5551050', $milk, 2);
            $confirm($order1050);
            $dispatch->assign($order1050, $riderA);
            $dispatch->markPickedUp($order1050);
            $dispatch->markOutForDelivery($order1050);
            $dispatch->markFailed($order1050, 'Customer unavailable');
            $dispatch->markReturned($order1050, 'Customer unavailable — rider returned parcel to warehouse');
            $dispatch->assign($order1050, $riderB); // re-allocates stock, attempt #2
            $dispatch->markPickedUp($order1050);
            $dispatch->markOutForDelivery($order1050);
            $dispatch->markDelivered($order1050);

            // --- Returned and left unresolved (no reassignment yet). ---
            $order1048 = $makeOrder('1048', 'Bilal Ahmed', '0300-5551048', $butter, 1);
            $confirm($order1048);
            $dispatch->assign($order1048, $riderA);
            $dispatch->markPickedUp($order1048);
            $dispatch->markOutForDelivery($order1048);
            $dispatch->markFailed($order1048, 'Address not found');
            $dispatch->markReturned($order1048, 'Address not found — rider returned parcel to warehouse', now()->addDay());

            // --- Currently out for delivery. ---
            $order1042 = $makeOrder('1042', 'Usman Ali', '0300-5551042', $milk, 3);
            $confirm($order1042);
            $dispatch->assign($order1042, $riderC);
            $dispatch->markPickedUp($order1042);
            $dispatch->markOutForDelivery($order1042);

            // --- Straightforward deliveries, spread across riders, under an
            // open trip for Rider B (linked automatically at pickup time). ---
            $tripB = $trips->openTrip($riderB, at: now()->subHours(3));

            foreach ([
                ['1043', 'Fatima Noor', $milk, 1, $riderA],
                ['1044', 'Hassan Raza', $butter, 2, $riderB],
                ['1045', 'Ayesha Malik', $milk, 1, $riderC],
                ['1046', 'Zainab Iqbal', $butter, 1, $riderA],
                ['1049', 'Kamran Yousuf', $milk, 2, $riderC],
            ] as [$number, $customer, $variant, $qty, $rider]) {
                $order = $makeOrder($number, $customer, '0300-555'.$number, $variant, $qty);
                $confirm($order);
                $dispatch->assign($order, $rider);
                $dispatch->markPickedUp($order);
                $dispatch->markOutForDelivery($order);
                $dispatch->markDelivered($order);
            }

            // --- Failed, left unresolved (no return action taken yet). ---
            $order1047 = $makeOrder('1047', 'Imran Sheikh', '0300-5551047', $butter, 1);
            $confirm($order1047);
            $dispatch->assign($order1047, $riderB);
            $dispatch->markPickedUp($order1047);
            $dispatch->markOutForDelivery($order1047);
            $dispatch->markFailed($order1047, 'Customer refused delivery');

            $trips->closeTrip($riderB, at: now()->subMinutes(20));

            // --- Rider A: a separate, already-completed trip from "yesterday". ---
            $tripA = $trips->openTrip($riderA, at: now()->subDay()->setTime(8, 0));
            $order1051 = $makeOrder('1051', 'Nadia Chaudhry', '0300-5551051', $milk, 1);
            $confirm($order1051);
            $dispatch->assign($order1051, $riderA);
            $dispatch->markPickedUp($order1051);
            $trips->closeTrip($riderA, at: now()->subDay()->setTime(16, 0));

            // --- Just assigned, nothing else yet. ---
            $order1052 = $makeOrder('1052', 'Farah Siddiqui', '0300-5551052', $butter, 1);
            $confirm($order1052);
            $dispatch->assign($order1052, $riderB);

            // --- Partial cash deposits / rider payouts, so balances stay
            // non-zero on both sides — a fully-settled rider page looks empty
            // and doesn't exercise the "outstanding balance" UI at all. ---
            DB::transaction(function () use ($wallet, $riderA, $admin) {
                $wallet->postTransaction(
                    rider: $riderA, transactionType: RiderWalletTransaction::TYPE_COD_SETTLED, amount: -300,
                    notes: 'Partial cash handed in', paymentMethod: 'cash', transactionDate: now()->subHours(6),
                );
                $wallet->postTransaction(
                    rider: $riderA, transactionType: RiderWalletTransaction::TYPE_EARNING_PAID, amount: 100,
                    notes: 'Partial earnings payout', paymentMethod: 'cash', transactionDate: now()->subHours(6),
                );
            });

            DB::transaction(function () use ($wallet, $riderB) {
                $wallet->postTransaction(
                    rider: $riderB, transactionType: RiderWalletTransaction::TYPE_COD_SETTLED, amount: -500,
                    notes: 'Partial cash handed in', paymentMethod: 'bank_transfer', referenceNumber: 'TXN-DEMO-01',
                    transactionDate: now()->subDay(),
                );
            });

            // Rider C is left fully unsettled — every collected COD/earned charge
            // still outstanding, and no trips at all (exercises the Trips tab's
            // empty state).

            $this->command->info('Rider Account demo data seeded: '.$riderA->user->name.', '.$riderB->user->name.', '.$riderC->user->name);
        });
    }

    private function makeRider(string $name, string $email, Warehouse $warehouse, float $perDeliveryRate, bool $checkedIn): RiderProfile
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => 'password',
            'phone' => '0300-'.random_int(1000000, 9999999),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $user->syncRoles(['Rider']);

        return $user->riderProfile()->create([
            'warehouse_id' => $warehouse->id,
            'phone' => $user->phone,
            'vehicle_type' => 'bike',
            'per_delivery_rate' => $perDeliveryRate,
            'status' => RiderProfile::STATUS_ACTIVE,
            'is_online' => $checkedIn,
            'is_checked_in' => $checkedIn,
            'checked_in_at' => $checkedIn ? now() : null,
        ]);
    }
}
