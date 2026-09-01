<?php

namespace App\Exports;

use App\Models\Order;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class OrdersExport implements FromCollection, WithHeadings, WithMapping
{
    private int $rowNumber = 0;

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
            'Sr#', 'Order No', 'Customer Name', 'Customer Contact',
            'Order Amount', 'Discount', 'Delivery Charges', 'Total Amount',
            'Address', 'Order Detail', 'Order Date', 'Order Status', 'Order Type', 'Delivery Status', 'Delivered Date/Time',
            'Payment Status', 'Rider Name', 'Scheduled Dispatch', 'Instructions for Rider',
        ];
    }

    /**
     * @param  Order  $order
     */
    public function map($order): array
    {
        return [
            ++$this->rowNumber,
            $order->shopify_order_number ?? $order->shopify_order_id,
            $order->customer_name,
            $order->customer_phone,
            number_format((float) $order->subtotal, 2),
            number_format((float) $order->discount_total, 2),
            number_format((float) $order->shipping_total, 2),
            number_format((float) $order->total, 2),
            $order->formattedAddress(),
            $order->itemsSummary(),
            $order->shopify_created_at?->format('Y-m-d H:i'),
            ucfirst($order->order_status),
            $order->isSelfPickup() ? 'Self Pickup' : 'Delivery',
            str($order->delivery_status)->headline(),
            $order->delivered_at?->format('Y-m-d H:i') ?? '',
            str($order->payment_status)->headline(),
            $order->rider?->user?->name ?? '',
            $order->scheduled_dispatch_at?->format('Y-m-d H:i') ?? '',
            $order->rider_instructions ?? '',
        ];
    }
}
