<div class="modal fade" id="modal-pay-rider" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('riders.wallet.pay-earnings', $rider) }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Pay Rider</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between small text-muted mb-3 pb-2 border-bottom">
                    <span>Current Earnings Payable</span>
                    <span class="fw-semibold text-dark">Rs. {{ number_format($financials['earnings_payable'], 2) }}</span>
                </div>

                <div class="mb-2">
                    <label class="form-label small">Payment Amount *</label>
                    <input type="number" step="0.01" min="0.01" max="{{ $financials['earnings_payable'] }}"
                           name="amount" id="pay-amount" class="form-control @error('amount') is-invalid @enderror"
                           value="{{ old('amount') }}" required>
                    @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-2">
                    <label class="form-label small">Payment Date *</label>
                    <input type="date" name="payment_date" class="form-control @error('payment_date') is-invalid @enderror"
                           value="{{ old('payment_date', now()->format('Y-m-d')) }}" max="{{ now()->format('Y-m-d') }}" required>
                    @error('payment_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-2">
                    <label class="form-label small">Payment Method *</label>
                    <select name="payment_method" class="form-select @error('payment_method') is-invalid @enderror" required>
                        <option value="cash" @selected(old('payment_method', 'cash') === 'cash')>Cash</option>
                        <option value="bank_transfer" @selected(old('payment_method') === 'bank_transfer')>Bank Transfer</option>
                        <option value="other" @selected(old('payment_method') === 'other')>Other</option>
                    </select>
                    @error('payment_method') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-2">
                    <label class="form-label small">Reference #</label>
                    <input type="text" name="reference_number" class="form-control" value="{{ old('reference_number') }}">
                </div>
                <div class="mb-2">
                    <label class="form-label small">Notes</label>
                    <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                </div>

                <div class="d-flex justify-content-between small border-top pt-2 mt-3">
                    <span>Remaining Earnings Payable</span>
                    <span class="fw-semibold" id="pay-remaining">Rs. {{ number_format($financials['earnings_payable'], 2) }}</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary" id="pay-submit-btn">Confirm Payment</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
    <script>
        (function () {
            var payable = {{ (float) $financials['earnings_payable'] }};
            var amountInput = document.getElementById('pay-amount');
            var remainingEl = document.getElementById('pay-remaining');

            amountInput?.addEventListener('input', function () {
                var amt = parseFloat(this.value) || 0;
                remainingEl.textContent = 'Rs. ' + (payable - amt).toFixed(2);
            });

            document.querySelector('#modal-pay-rider form')?.addEventListener('submit', function () {
                var btn = document.getElementById('pay-submit-btn');
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Saving...';
            });
        })();
    </script>
@endpush
