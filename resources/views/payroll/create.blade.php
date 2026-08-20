@extends('layouts.app')

@section('title', 'Generate Payroll Run')

@section('content')
    <h1 class="h4 mb-3">Generate Payroll Run</h1>

    <div class="card shadow-sm" style="max-width: 640px;">
        <div class="card-body">
            <p class="text-muted small">
                A payslip is generated for every active employee. Basic salary is taken from their profile;
                riders also have delivery earnings credited during this period pulled in automatically from
                their wallet ledger. The run starts as a draft — you can add advances/other adjustments before approving it.
            </p>
            <form method="POST" action="{{ route('payroll.store') }}">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="period_start" class="form-label">Period Start</label>
                        <input id="period_start" type="date" name="period_start" value="{{ old('period_start', now()->startOfMonth()->toDateString()) }}"
                               class="form-control @error('period_start') is-invalid @enderror" required>
                        @error('period_start') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="period_end" class="form-label">Period End</label>
                        <input id="period_end" type="date" name="period_end" value="{{ old('period_end', now()->endOfMonth()->toDateString()) }}"
                               class="form-control @error('period_end') is-invalid @enderror" required>
                        @error('period_end') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="mb-3">
                    <label for="notes" class="form-label">Notes</label>
                    <textarea id="notes" name="notes" rows="2" class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
                    @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <button type="submit" class="btn btn-primary">Generate</button>
                <a href="{{ route('payroll.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
