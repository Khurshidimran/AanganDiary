@extends('layouts.app')

@section('title', $config['label'])

@section('content')
    <h1 class="h4 mb-3">{{ $config['label'] }}</h1>

    @if (! $fixedAccount)
        <div class="alert alert-warning">
            No {{ $config['fixed_leg'] === 'cash' ? 'Cash' : 'Bank' }} account is mapped yet.
            <a href="{{ route('accounting.mapping.edit') }}">Set it up in Account Mapping</a> before recording this voucher.
        </div>
    @endif

    <div class="card shadow-sm" style="max-width: 640px;">
        <div class="card-body">
            <form method="POST" action="{{ route('vouchers.store', $type) }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label">{{ $config['fixed_leg'] === 'cash' ? 'Cash' : 'Bank' }} Account</label>
                    <input type="text" class="form-control" value="{{ $fixedAccount ? "{$fixedAccount->code} — {$fixedAccount->name}" : 'Not mapped' }}" disabled>
                </div>

                <div class="mb-3">
                    <label for="account_id" class="form-label">{{ $config['other_label'] }}</label>
                    <select id="account_id" name="account_id" class="form-select @error('account_id') is-invalid @enderror" required>
                        <option value="">Select an account</option>
                        @foreach ($accounts as $option)
                            <option value="{{ $option->id }}" @selected((string) old('account_id') === (string) $option->id)>{{ $option->code }} — {{ $option->name }}</option>
                        @endforeach
                    </select>
                    @error('account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="entry_date" class="form-label">Date</label>
                        <input id="entry_date" type="date" name="entry_date" value="{{ old('entry_date', now()->toDateString()) }}"
                               class="form-control @error('entry_date') is-invalid @enderror" required>
                        @error('entry_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="amount" class="form-label">Amount</label>
                        <input id="amount" type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}"
                               class="form-control @error('amount') is-invalid @enderror" required>
                        @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="narration" class="form-label">Narration</label>
                    <input id="narration" type="text" name="narration" value="{{ old('narration') }}"
                           class="form-control @error('narration') is-invalid @enderror" required>
                    @error('narration') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <button type="submit" class="btn btn-primary" {{ $fixedAccount ? '' : 'disabled' }}>Record Voucher</button>
                <a href="{{ route('journal-entries.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
