<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Lookup-only for now — feeds the searchable customer picker on the manual
 * order-creation screen. No customers.index/create/edit management screen
 * exists yet (out of scope), so this stays a single lightweight endpoint
 * rather than a full resource controller.
 */
class CustomerController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $this->authorize('create', Order::class);

        $term = trim((string) $request->query('q', ''));

        if ($term === '') {
            return response()->json([]);
        }

        $customers = Customer::with('addresses')
            ->where(fn ($q) => $q->where('phone', 'like', "%{$term}%")->orWhere('name', 'like', "%{$term}%"))
            ->orderBy('name')
            ->limit(20)
            ->get();

        return response()->json($customers->map(fn (Customer $customer) => [
            'value' => $customer->id,
            'text' => "{$customer->name} — {$customer->phone}",
            'name' => $customer->name,
            'phone' => $customer->phone,
            'email' => $customer->email,
            'addresses' => $customer->addresses->map(fn (CustomerAddress $address) => [
                'id' => $address->id,
                'label' => $address->label(),
                'address1' => $address->address1,
                'address2' => $address->address2,
                'city' => $address->city,
                'country' => $address->country,
                'phone' => $address->phone,
                'is_default' => $address->is_default,
            ])->values(),
        ]));
    }
}
