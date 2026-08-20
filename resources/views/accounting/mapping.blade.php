@extends('layouts.app')

@section('title', 'Account Mapping')

@php
    $grouped = $accounts->groupBy('type');
    $selectField = function (string $key, string $label, ?string $help = null) use ($grouped, $mapping) {
        $current = old($key, $mapping->get($key));
        return view('accounting._mapping-select', compact('key', 'label', 'help', 'grouped', 'current'))->render();
    };
@endphp

@section('content')
    <h1 class="h4 mb-3">Account Mapping</h1>
    <p class="text-muted small">
        These accounts are used automatically when Sales, Purchases, Expenses, and Payroll post to the general ledger.
        Leaving a field blank skips that automatic posting (the underlying action still succeeds — a warning is just shown).
    </p>

    <form method="POST" action="{{ route('accounting.mapping.update') }}">
        @csrf
        @method('PUT')

        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">General</div>
            <div class="card-body row">
                {!! $selectField('cash_account_id', 'Cash Account') !!}
                {!! $selectField('bank_account_id', 'Bank Account') !!}
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Sales</div>
            <div class="card-body row">
                {!! $selectField('sales_revenue_account_id', 'Sales Revenue') !!}
                {!! $selectField('receivable_account_id', 'Accounts Receivable') !!}
                {!! $selectField('tax_payable_account_id', 'Tax Payable', 'Only used if an order has tax_total > 0.') !!}
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Purchases</div>
            <div class="card-body row">
                {!! $selectField('inventory_asset_account_id', 'Inventory Asset') !!}
                {!! $selectField('cogs_account_id', 'Cost of Goods Sold', 'Posted automatically when an order is delivered, using standard cost.') !!}
                {!! $selectField('payable_account_id', 'Accounts Payable') !!}
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Payroll</div>
            <div class="card-body row">
                {!! $selectField('salaries_expense_account_id', 'Salaries Expense') !!}
                {!! $selectField('salaries_payable_account_id', 'Salaries Payable') !!}
                {!! $selectField('payroll_cash_account_id', 'Payroll Cash/Bank Account') !!}
                {!! $selectField('payroll_deductions_account_id', 'Payroll Deductions Clearing', 'Advance repayments and other deductions withheld from net pay.') !!}
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Expenses</div>
            <div class="card-body row">
                {!! $selectField('default_expense_account_id', 'Default Expense Account', 'Used when an expense category has no specific GL account set (see Expense Categories).') !!}
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Save Mapping</button>
    </form>
@endsection
