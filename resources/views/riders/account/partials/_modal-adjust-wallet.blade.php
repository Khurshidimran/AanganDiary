<div class="modal fade" id="modal-adjust-wallet" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('riders.wallet.adjust', $rider) }}" class="modal-content">
            @csrf
            <input type="hidden" name="wallet_action" value="adjustment">
            <div class="modal-header">
                <h5 class="modal-title">Adjust Wallet</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between small text-muted mb-3 pb-2 border-bottom">
                    <span>Current Wallet Balance</span>
                    <span class="fw-semibold text-dark">Rs. {{ number_format($rider->wallet_balance, 2) }}</span>
                </div>

                <p class="small text-muted">
                    For correcting a mistake — a positive amount credits the wallet, a negative amount debits it.
                    This does not touch any specific order; use it for manual corrections only.
                </p>

                <div class="mb-2">
                    <label class="form-label small">Amount *</label>
                    <input type="number" step="0.01" name="amount" id="adjust-amount"
                           class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount') }}" required>
                    <div class="form-text">Use a negative number (e.g. -2989) to debit the wallet.</div>
                    @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-2">
                    <label class="form-label small">Notes *</label>
                    <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="2" required>{{ old('notes') }}</textarea>
                    @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="d-flex justify-content-between small border-top pt-2 mt-3">
                    <span>New Balance</span>
                    <span class="fw-semibold" id="adjust-new-balance">Rs. {{ number_format($rider->wallet_balance, 2) }}</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary" id="adjust-submit-btn">Confirm Adjustment</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
    <script>
        (function () {
            var balance = {{ (float) $rider->wallet_balance }};
            var amountInput = document.getElementById('adjust-amount');
            var newBalanceEl = document.getElementById('adjust-new-balance');

            amountInput?.addEventListener('input', function () {
                var amt = parseFloat(this.value) || 0;
                newBalanceEl.textContent = 'Rs. ' + (balance + amt).toFixed(2);
            });

            document.querySelector('#modal-adjust-wallet form')?.addEventListener('submit', function () {
                var btn = document.getElementById('adjust-submit-btn');
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Saving...';
            });
        })();
    </script>
@endpush
