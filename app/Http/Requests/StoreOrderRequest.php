<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('orders.create');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // A blank hidden input submits "" (not an absent key), so the
        // address fields' requiredness is checked against the real
        // submitted value via a closure rather than required_if's
        // string-literal comparison (which would never match "").
        $noAddressSelected = fn () => blank($this->input('customer_address_id'));

        return [
            'channel_id' => ['required', 'exists:channels,id'],

            'customer_id' => ['nullable', 'exists:customers,id'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'customer_email' => ['nullable', 'email', 'max:255'],

            'customer_address_id' => ['nullable', 'exists:customer_addresses,id'],
            'address1' => [Rule::requiredIf($noAddressSelected), 'nullable', 'string', 'max:255'],
            'address2' => ['nullable', 'string', 'max:255'],
            'city' => [Rule::requiredIf($noAddressSelected), 'nullable', Rule::in(['Lahore'])],
            'country' => [Rule::requiredIf($noAddressSelected), 'nullable', 'string', 'max:255'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_variant_id' => ['required', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'discount_total' => ['nullable', 'numeric', 'min:0'],
            'shipping_total' => ['nullable', 'numeric', 'min:0'],
            'tax_total' => ['nullable', 'numeric', 'min:0'],
            'payment_status' => ['required', 'in:pending,paid,partially_paid'],
            'payment_type' => ['required', 'in:cash,credit'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
