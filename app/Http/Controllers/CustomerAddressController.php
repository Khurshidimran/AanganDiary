<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerAddressRequest;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;

class CustomerAddressController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLog)
    {
    }

    public function store(StoreCustomerAddressRequest $request, Customer $customer): RedirectResponse
    {
        $address = $customer->addresses()->create($request->validated() + [
            'is_default' => $customer->addresses()->count() === 0,
        ]);

        $this->auditLog->log('created', 'customer_addresses', $address, null, $address->only(['address1', 'city']));

        return redirect()->route('customers.edit', $customer)->with('status', 'Address added.');
    }

    public function update(StoreCustomerAddressRequest $request, Customer $customer, CustomerAddress $address): RedirectResponse
    {
        $before = $address->only(['address1', 'city']);
        $address->update($request->validated());

        $this->auditLog->log('updated', 'customer_addresses', $address, $before, $address->only(['address1', 'city']));

        return redirect()->route('customers.edit', $customer)->with('status', 'Address updated.');
    }

    public function destroy(Customer $customer, CustomerAddress $address): RedirectResponse
    {
        $this->authorize('update', $customer);

        $before = $address->only(['address1', 'city']);
        $address->delete();

        $this->auditLog->log('deleted', 'customer_addresses', null, $before, null);

        return redirect()->route('customers.edit', $customer)->with('status', 'Address removed.');
    }
}
