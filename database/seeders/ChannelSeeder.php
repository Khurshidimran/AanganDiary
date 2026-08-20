<?php

namespace Database\Seeders;

use App\Models\Channel;
use App\Models\Order;
use Illuminate\Database\Seeder;

class ChannelSeeder extends Seeder
{
    public function run(): void
    {
        $shopify = Channel::updateOrCreate(
            ['code' => 'shopify'],
            ['name' => 'Shopify', 'is_system' => true, 'status' => Channel::STATUS_ACTIVE],
        );

        Channel::updateOrCreate(['code' => 'whatsapp'], ['name' => 'WhatsApp', 'is_system' => false, 'status' => Channel::STATUS_ACTIVE]);
        Channel::updateOrCreate(['code' => 'phone'], ['name' => 'Phone', 'is_system' => false, 'status' => Channel::STATUS_ACTIVE]);

        // Every order synced before this feature existed came from Shopify —
        // backfill so "distinguish orders by channel" is accurate for
        // historical data too, not just orders created from here on.
        Order::whereNull('channel_id')->update(['channel_id' => $shopify->id]);
    }
}
