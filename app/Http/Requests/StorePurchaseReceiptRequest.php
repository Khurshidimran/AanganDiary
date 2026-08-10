<?php

namespace App\Http\Requests;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePurchaseReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('purchase_receipts.create');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'purchase_order_id' => ['required', 'exists:purchase_orders,id'],
            'receipt_date' => ['required', 'date'],
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.purchase_order_item_id' => ['required', 'exists:purchase_order_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.batch_number' => ['nullable', 'string', 'max:100'],
            'items.*.manufacturing_date' => ['nullable', 'date'],
            'items.*.expiry_date' => ['nullable', 'date', 'after_or_equal:items.*.manufacturing_date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $purchaseOrder = PurchaseOrder::find($this->input('purchase_order_id'));

            if (! $purchaseOrder) {
                return;
            }

            if (! $purchaseOrder->canReceiveStock()) {
                $validator->errors()->add('purchase_order_id', 'This purchase order is not open to receive stock.');

                return;
            }

            foreach ($this->input('items', []) as $index => $item) {
                $poItem = PurchaseOrderItem::find($item['purchase_order_item_id'] ?? null);

                if (! $poItem || $poItem->purchase_order_id !== $purchaseOrder->id) {
                    $validator->errors()->add("items.{$index}.purchase_order_item_id", 'Invalid item for this purchase order.');

                    continue;
                }

                $remaining = $poItem->quantityRemaining();

                if ((float) ($item['quantity'] ?? 0) > $remaining) {
                    $validator->errors()->add(
                        "items.{$index}.quantity",
                        "Quantity cannot exceed the remaining {$remaining} still owed on this order line.",
                    );
                }
            }
        });
    }
}
