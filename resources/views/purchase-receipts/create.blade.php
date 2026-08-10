@extends('layouts.app')

@section('title', 'Receive Stock')

@section('content')
    <h1 class="h4 mb-1">Receive Stock</h1>
    <p class="text-muted small mb-3">
        Against {{ $purchaseOrder->po_number }} — {{ $purchaseOrder->vendor->name }} — {{ $purchaseOrder->warehouse->name }}
    </p>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('purchase-receipts.store') }}">
                @csrf
                <input type="hidden" name="purchase_order_id" value="{{ $purchaseOrder->id }}">

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="receipt_date" class="form-label">Receipt Date</label>
                        <input id="receipt_date" type="date" name="receipt_date" value="{{ old('receipt_date', now()->toDateString()) }}"
                               class="form-control @error('receipt_date') is-invalid @enderror" required>
                        @error('receipt_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="invoice_number" class="form-label">Invoice Number</label>
                        <input id="invoice_number" type="text" name="invoice_number" value="{{ old('invoice_number') }}"
                               class="form-control @error('invoice_number') is-invalid @enderror">
                        @error('invoice_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="notes" class="form-label">Notes</label>
                    <textarea id="notes" name="notes" rows="2" class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
                    @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <hr>
                <h2 class="h6 mb-2">Items to Receive</h2>

                @php $itemIndex = 0; @endphp
                @foreach ($purchaseOrder->items as $poItem)
                    @continue($poItem->quantityRemaining() <= 0)
                    @php
                        $variant = $poItem->productVariant;
                        $old = old("items.{$itemIndex}");
                    @endphp
                    <div class="border rounded p-3 mb-2">
                        <input type="hidden" name="items[{{ $itemIndex }}][purchase_order_item_id]" value="{{ $poItem->id }}">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label small">Product</label>
                                <div class="fw-semibold">{{ $variant->product->name }}</div>
                                <div class="text-muted small">{{ $variant->name }} ({{ $variant->sku }})</div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">Remaining</label>
                                <div>{{ rtrim(rtrim($poItem->quantityRemaining(), '0'), '.') }} {{ $variant->unit->short_code }}</div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">Quantity Received</label>
                                <input type="number" step="0.001" min="0" max="{{ $poItem->quantityRemaining() }}"
                                       name="items[{{ $itemIndex }}][quantity]"
                                       value="{{ $old['quantity'] ?? $poItem->quantityRemaining() }}"
                                       class="form-control form-control-sm @error("items.{$itemIndex}.quantity") is-invalid @enderror" required>
                                @error("items.{$itemIndex}.quantity") <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">Unit Cost</label>
                                <input type="number" step="0.01" min="0" name="items[{{ $itemIndex }}][unit_cost]"
                                       value="{{ $old['unit_cost'] ?? $poItem->unit_cost }}"
                                       class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Batch Number</label>
                                <input type="text" name="items[{{ $itemIndex }}][batch_number]" value="{{ $old['batch_number'] ?? '' }}"
                                       class="form-control form-control-sm">
                            </div>
                        </div>
                        <div class="row g-2 mt-1">
                            <div class="col-md-3 offset-md-3">
                                <label class="form-label small">Manufacturing Date</label>
                                <input type="date" name="items[{{ $itemIndex }}][manufacturing_date]" value="{{ $old['manufacturing_date'] ?? '' }}"
                                       class="form-control form-control-sm">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Expiry Date</label>
                                <input type="date" name="items[{{ $itemIndex }}][expiry_date]" value="{{ $old['expiry_date'] ?? '' }}"
                                       class="form-control form-control-sm @error("items.{$itemIndex}.expiry_date") is-invalid @enderror">
                                @error("items.{$itemIndex}.expiry_date") <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                    @php $itemIndex++; @endphp
                @endforeach

                <button type="submit" class="btn btn-success">
                    <i class="bi bi-box-arrow-in-down"></i> Post Stock Receipt
                </button>
                <a href="{{ route('purchase-orders.show', $purchaseOrder) }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
