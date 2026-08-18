<?php

namespace App\Exports;

use App\Models\Order;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class OrdersExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, Order>  $orders  Expected to have 'items' and 'rider.user' eager-loaded.
     */
    public function __construct(private readonly Collection $orders)
    {
    }

    public function collection(): Collection
    {
        return $this->orders;
    }

    public function headings(): array
    {
        return [
            'Order No', 'Customer Name', 'Customer Contact', 'Amount', 'Address',
            'Order Detail', 'Order Date', 'Order Status', 'Delivery Status', 'Payment Status', 'Rider Name',
            'Scheduled Dispatch', 'Instructions for Rider',
        ];
    }

    /**
     * @param  Order  $order
     */
    public function map($order): array
    {
        return [
            $order->shopify_order_number ?? $order->shopify_order_id,
            $order->customer_name,
            $order->customer_phone,
            number_format((float) $order->total, 2),
            $order->formattedAddress(),
            $order->itemsSummary(),
            $order->shopify_created_at?->format('Y-m-d H:i'),
            ucfirst($order->order_status),
            str($order->delivery_status)->headline(),
            str($order->payment_status)->headline(),
            $order->rider?->user?->name ?? '',
            $order->scheduled_dispatch_at?->format('Y-m-d H:i') ?? '',
            $order->rider_instructions ?? '',
        ];
    }
}
