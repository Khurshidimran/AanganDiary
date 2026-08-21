@extends('layouts.app')

@section('title', 'Edit Customer')

@section('content')
    <h1 class="h4 mb-3">Edit Customer</h1>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <form method="POST" action="{{ route('customers.update', $customer) }}">
                        @csrf
                        @method('PUT')
                        @include('customers._form', ['customer' => $customer])
                        <button type="submit" class="btn btn-primary">Update Customer</button>
                        <a href="{{ route('customers.index') }}" class="btn btn-link">Cancel</a>
                    </form>
                </div>
            </div>

            @if ($outstandingCredit > 0)
                <div class="card shadow-sm border-warning mb-3">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <span class="small">Outstanding Credit Balance</span>
                        <span class="fs-5 fw-bold text-warning-emphasis">Rs. {{ number_format($outstandingCredit, 2) }}</span>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Saved Addresses</span>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modal-add-address">
                        <i class="bi bi-plus-lg"></i> Add Address
                    </button>
                </div>
                <ul class="list-group list-group-flush">
                    @forelse ($customer->addresses as $address)
                        <li class="list-group-item d-flex justify-content-between align-items-start gap-2">
                            <div class="small">
                                @if ($address->label)
                                    <span class="fw-semibold">{{ $address->label }}</span> —
                                @endif
                                {{ $address->address1 }}@if ($address->address2), {{ $address->address2 }}@endif,
                                {{ $address->city }}, {{ $address->country }}
                                @if ($address->is_default)
                                    <span class="badge bg-success-subtle text-success-emphasis ms-1">Default</span>
                                @endif
                            </div>
                            <div class="d-flex gap-1 flex-shrink-0">
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modal-edit-address-{{ $address->id }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST" action="{{ route('customers.addresses.destroy', [$customer, $address]) }}" onsubmit="return confirm('Remove this address?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </li>
                    @empty
                        <li class="list-group-item text-muted small">No saved addresses yet.</li>
                    @endforelse
                </ul>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Recent Orders</div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Payment</th>
                                <th class="text-end">Outstanding</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($orders as $order)
                                <tr>
                                    <td><a href="{{ route('orders.show', $order) }}">{{ $order->shopify_order_number ?? $order->shopify_order_id }}</a></td>
                                    <td class="small">{{ str($order->payment_type)->headline() }} · {{ str($order->payment_status)->headline() }}</td>
                                    <td class="text-end">{{ $order->currency }} {{ number_format($order->total_outstanding, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">No orders yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-add-address" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('customers.addresses.store', $customer) }}" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Address</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @include('customers._address-form')
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Address</button>
                </div>
            </form>
        </div>
    </div>

    @foreach ($customer->addresses as $address)
        <div class="modal fade" id="modal-edit-address-{{ $address->id }}" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('customers.addresses.update', [$customer, $address]) }}" class="modal-content">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Address</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        @include('customers._address-form', ['address' => $address])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@endsection
