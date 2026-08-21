<?php

namespace Database\Seeders;

use App\Models\Channel;
use App\Models\Order;
use Illuminate\Database\Seeder;

class ChannelSeeder extends Seeder
{
    public function run(): void
    {
        // Kept as the fallback for any Shopify order whose source_name
        // doesn't match one of the specific channels below (an
        // unrecognized/new sales channel) — never matched directly since
        // its own source_name is null.
        $shopify = Channel::updateOrCreate(
            ['code' => 'shopify'],
            ['name' => 'Shopify', 'is_system' => true, 'status' => Channel::STATUS_ACTIVE],
        );

        // The store's actual Shopify sales channels — source_name is what
        // ShopifyOrderSyncService matches against the payload to route each
        // order automatically. 'web' and 'shopify_draft_order' are Shopify's
        // own fixed identifiers; the COD form app's source_name isn't known
        // yet (third-party apps don't use a human-readable name for it), so
        // it's left unmapped until a real order confirms the value — orders
        // from it fall back to the generic Shopify channel until then.
        Channel::updateOrCreate(['code' => 'online_store'], ['name' => 'Online Store', 'source_name' => 'web', 'is_system' => true, 'status' => Channel::STATUS_ACTIVE]);
        Channel::updateOrCreate(['code' => 'draft_orders'], ['name' => 'Draft Orders', 'source_name' => 'shopify_draft_order', 'is_system' => true, 'status' => Channel::STATUS_ACTIVE]);
        Channel::updateOrCreate(['code' => 'easysell_cod_form'], ['name' => 'EasySellCOD Form', 'source_name' => null, 'is_system' => true, 'status' => Channel::STATUS_ACTIVE]);

        Channel::updateOrCreate(['code' => 'whatsapp'], ['name' => 'WhatsApp', 'is_system' => false, 'status' => Channel::STATUS_ACTIVE]);
        Channel::updateOrCreate(['code' => 'phone'], ['name' => 'Phone', 'is_system' => false, 'status' => Channel::STATUS_ACTIVE]);

        // Every order synced before this feature existed came from Shopify —
        // backfill so "distinguish orders by channel" is accurate for
        // historical data too, not just orders created from here on.
        Order::whereNull('channel_id')->update(['channel_id' => $shopify->id]);
    }
}
