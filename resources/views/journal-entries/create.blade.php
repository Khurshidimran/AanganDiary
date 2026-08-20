@extends('layouts.app')

@section('title', 'New Journal Voucher')

@section('content')
    <h1 class="h4 mb-3">New Journal Voucher</h1>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('journal-entries.store') }}">
                @csrf

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="entry_date" class="form-label">Date</label>
                        <input id="entry_date" type="date" name="entry_date" value="{{ old('entry_date', now()->toDateString()) }}"
                               class="form-control @error('entry_date') is-invalid @enderror" required>
                        @error('entry_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-8 mb-3">
                        <label for="narration" class="form-label">Narration</label>
                        <input id="narration" type="text" name="narration" value="{{ old('narration') }}"
                               class="form-control @error('narration') is-invalid @enderror" required>
                        @error('narration') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <table class="table" id="lines-table">
                    <thead>
                        <tr>
                            <th style="width: 35%;">Account</th>
                            <th>Debit</th>
                            <th>Credit</th>
                            <th>Description</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="lines-body">
                        @for ($i = 0; $i < 2; $i++)
                            <tr class="line-row">
                                <td>
                                    <select name="lines[{{ $i }}][account_id]" class="form-select form-select-sm" required>
                                        <option value="">Select an account</option>
                                        @foreach ($accounts as $option)
                                            <option value="{{ $option->id }}">{{ $option->code }} — {{ $option->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="number" step="0.01" min="0" name="lines[{{ $i }}][debit]" class="form-control form-control-sm"></td>
                                <td><input type="number" step="0.01" min="0" name="lines[{{ $i }}][credit]" class="form-control form-control-sm"></td>
                                <td><input type="text" name="lines[{{ $i }}][description]" class="form-control form-control-sm"></td>
                                <td><button type="button" class="btn btn-sm btn-link text-danger remove-line">&times;</button></td>
                            </tr>
                        @endfor
                    </tbody>
                    <tfoot>
                        <tr>
                            <td class="text-end fw-semibold">Totals</td>
                            <td id="debit-total" class="fw-semibold">0.00</td>
                            <td id="credit-total" class="fw-semibold">0.00</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
                @error('lines') <div class="text-danger small mb-2">{{ $message }}</div> @enderror

                <button type="button" id="add-line" class="btn btn-sm btn-outline-secondary mb-3">
                    <i class="bi bi-plus-lg"></i> Add Line
                </button>
                <br>

                <button type="submit" class="btn btn-primary">Post Journal Entry</button>
                <a href="{{ route('journal-entries.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const accountOptions = document.querySelector('#lines-body select').innerHTML;
            let lineIndex = {{ 2 }};

            function recalcTotals() {
                let debit = 0, credit = 0;
                document.querySelectorAll('input[name*="[debit]"]').forEach(el => debit += parseFloat(el.value) || 0);
                document.querySelectorAll('input[name*="[credit]"]').forEach(el => credit += parseFloat(el.value) || 0);
                document.getElementById('debit-total').textContent = debit.toFixed(2);
                document.getElementById('credit-total').textContent = credit.toFixed(2);
            }

            document.getElementById('add-line').addEventListener('click', function () {
                const row = document.createElement('tr');
                row.className = 'line-row';
                row.innerHTML = `
                    <td><select name="lines[${lineIndex}][account_id]" class="form-select form-select-sm" required><option value="">Select an account</option>${accountOptions.replace('<option value="">Select an account</option>', '')}</select></td>
                    <td><input type="number" step="0.01" min="0" name="lines[${lineIndex}][debit]" class="form-control form-control-sm"></td>
                    <td><input type="number" step="0.01" min="0" name="lines[${lineIndex}][credit]" class="form-control form-control-sm"></td>
                    <td><input type="text" name="lines[${lineIndex}][description]" class="form-control form-control-sm"></td>
                    <td><button type="button" class="btn btn-sm btn-link text-danger remove-line">&times;</button></td>
                `;
                document.getElementById('lines-body').appendChild(row);
                lineIndex++;
            });

            document.getElementById('lines-body').addEventListener('click', function (e) {
                if (e.target.classList.contains('remove-line')) {
                    if (document.querySelectorAll('.line-row').length > 2) {
                        e.target.closest('tr').remove();
                        recalcTotals();
                    }
                }
            });

            document.getElementById('lines-body').addEventListener('input', recalcTotals);
        })();
    </script>
@endsection
