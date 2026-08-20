@extends('layouts.app')

@section('title', 'Journal Entry ' . $journalEntry->entry_number)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0">{{ $journalEntry->entry_number }}</h1>
            <div class="text-muted small">
                {{ $journalEntry->entry_date->format('Y-m-d') }} &middot;
                {{ str($journalEntry->type)->headline() }} &middot;
                {{ str($journalEntry->source)->headline() }}
                @if ($journalEntry->createdBy)
                    &middot; by {{ $journalEntry->createdBy->name }}
                @endif
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge fs-6 {{ $journalEntry->status === 'voided' ? 'bg-danger' : 'bg-success' }}">
                {{ str($journalEntry->status)->headline() }}
            </span>
            @can('void', $journalEntry)
                @if ($journalEntry->status === 'posted')
                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#voidModal">
                        Void
                    </button>
                @endif
            @endcan
        </div>
    </div>

    @if ($journalEntry->narration)
        <div class="alert alert-light border small">{{ $journalEntry->narration }}</div>
    @endif

    @if ($journalEntry->voidedEntry)
        <div class="alert alert-warning small">This is a reversal of <a href="{{ route('journal-entries.show', $journalEntry->voidedEntry) }}">{{ $journalEntry->voidedEntry->entry_number }}</a>.</div>
    @endif
    @if ($journalEntry->reversal->isNotEmpty())
        <div class="alert alert-warning small">Reversed by <a href="{{ route('journal-entries.show', $journalEntry->reversal->first()) }}">{{ $journalEntry->reversal->first()->entry_number }}</a>.</div>
    @endif

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Account</th>
                        <th>Description</th>
                        <th class="text-end">Debit</th>
                        <th class="text-end">Credit</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($journalEntry->lines as $line)
                        <tr>
                            <td>{{ $line->account->code }} — {{ $line->account->name }}</td>
                            <td>{{ $line->description ?? '—' }}</td>
                            <td class="text-end">{{ $line->debit > 0 ? number_format($line->debit, 2) : '' }}</td>
                            <td class="text-end">{{ $line->credit > 0 ? number_format($line->credit, 2) : '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="fw-semibold">
                        <td colspan="2" class="text-end">Totals</td>
                        <td class="text-end">{{ number_format($journalEntry->totalDebit(), 2) }}</td>
                        <td class="text-end">{{ number_format($journalEntry->totalCredit(), 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="mt-3">
        <a href="{{ route('journal-entries.index') }}" class="btn btn-link">Back to Journal Entries</a>
    </div>

    @can('void', $journalEntry)
        <div class="modal fade" id="voidModal" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('journal-entries.void', $journalEntry) }}">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Void {{ $journalEntry->entry_number }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p class="small text-muted">This posts a reversing entry — the original stays in the ledger for audit purposes.</p>
                            <label for="reason" class="form-label">Reason</label>
                            <input id="reason" type="text" name="reason" class="form-control" required>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">Void Entry</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endcan
@endsection
