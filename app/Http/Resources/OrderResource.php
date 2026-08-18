<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->shopify_order_number,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'shipping_address' => $this->shipping_address,
            'delivery_status' => $this->delivery_status,
            'route_sequence' => $this->route_sequence,
            'payment_status' => $this->payment_status,
            'currency' => $this->currency,
            'total' => (float) $this->total,
            'cod_amount' => (float) $this->cod_amount,
            'cod_collected' => (bool) $this->cod_collected,
            'assigned_at' => $this->assigned_at?->toIso8601String(),
            'scheduled_dispatch_at' => $this->scheduled_dispatch_at?->toIso8601String(),
            'rider_instructions' => $this->rider_instructions,
            'picked_up_at' => $this->picked_up_at?->toIso8601String(),
            'delivered_at' => $this->delivered_at?->toIso8601String(),
            'delivery_failure_reason' => $this->delivery_failure_reason,
            'pod_photo_url' => $this->pod_photo_path ? Storage::disk('public')->url($this->pod_photo_path) : null,
            'pod_signature_url' => $this->pod_signature_path ? Storage::disk('public')->url($this->pod_signature_path) : null,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
