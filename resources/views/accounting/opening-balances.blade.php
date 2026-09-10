@extends('layouts.app')

@section('title', 'Opening Balances')

@php
    $grouped = $accounts->groupBy('type');
    $sections = ['asset' => 'Assets', 'liability' => 'Liabilities', 'equity' => 'Equity'];
@endphp

@section('content')
    <h1 class="h4 mb-3">Opening Balances</h1>
    <p class="text-muted small">
        Starting this accounting system partway through the financial year — enter what each account already stood at
        as of a specific date, and it'll be recorded as a single balanced opening entry. Any difference between what
        you enter is automatically posted to <strong>{{ $plugAccount->code ?? '' }} — Opening Balance Equity</strong>,
        so you never need to work out the balancing figure yourself.
    </p>

    @if ($existing)
        <div class="alert alert-info">
            An opening balance was already recorded on <strong>{{ $existing->entry_date->format('Y-m-d') }}</strong>
            (<a href="{{ route('journal-entries.show', $existing) }}">{{ $existing->entry_number }}</a>).
            To correct it, void that entry from Journal Entries first, then come back here.
        </div>
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Account</th>
                            <th class="text-end">Debit</th>
                            <th class="text-end">Credit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($existing->lines as $line)
                            <tr>
                                <td>{{ $line->account->code }} — {{ $line->account->name }}</td>
                                <td class="text-end">{{ $line->debit > 0 ? number_format($line->debit, 2) : '—' }}</td>
                                <td class="text-end">{{ $line->credit > 0 ? number_format($line->credit, 2) : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <form method="POST" action="{{ route('accounting.opening-balances.update') }}">
            @csrf

            <div class="card shadow-sm mb-3">
                <div class="card-body py-2">
                    <label class="form-label small mb-0">As Of Date</label>
                    <input type="date" name="as_of_date" value="{{ old('as_of_date', now()->toDateString()) }}"
                           class="form-control form-control-sm @error('as_of_date') is-invalid @enderror" style="max-width: 220px;" required>
                    @error('as_of_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            @foreach ($sections as $type => $label)
                @php $typeAccounts = $grouped->get($type, collect()); @endphp
                @if ($typeAccounts->isNotEmpty())
                    <div class="card shadow-sm mb-3">
                        <div class="card-header bg-white fw-semibold">{{ $label }}</div>
                        <div class="table-responsive">
                            <table class="table mb-0 align-middle">
                                <tbody>
                                    @foreach ($typeAccounts as $account)
                                        <tr>
                                            <td>{{ $account->code }} — {{ $account->name }}</td>
                                            <td style="width: 220px;">
                                                <input type="number" step="0.01" name="amounts[{{ $account->id }}]"
                                                       value="{{ old('amounts.'.$account->id) }}"
                                                       class="form-control form-control-sm text-end @error('amounts.'.$account->id) is-invalid @enderror"
                                                       placeholder="0.00">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            @endforeach

            <button type="submit" class="btn btn-primary">Record Opening Balances</button>
        </form>
    @endif
@endsection
