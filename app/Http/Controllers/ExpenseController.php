<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Warehouse;
use App\Services\AccountingPostingService;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLog,
        private readonly AccountingPostingService $accounting,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Expense::class);

        $query = Expense::with(['category', 'warehouse', 'recordedBy'])
            ->when($request->filled('expense_category_id'), fn ($q) => $q->where('expense_category_id', $request->expense_category_id))
            ->when($request->filled('warehouse_id'), fn ($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('expense_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('expense_date', '<=', $request->date_to));

        $total = (clone $query)->sum('amount');

        $expenses = $query->latest('expense_date')->paginate(20)->withQueryString();

        $categories = ExpenseCategory::orderBy('name')->pluck('name', 'id');
        $warehouses = Warehouse::orderBy('name')->pluck('name', 'id');

        return view('expenses.index', compact('expenses', 'categories', 'warehouses', 'total'));
    }

    public function create(): View
    {
        $this->authorize('create', Expense::class);

        return view('expenses.create', [
            'categories' => ExpenseCategory::where('status', ExpenseCategory::STATUS_ACTIVE)->orderBy('name')->pluck('name', 'id'),
            'warehouses' => Warehouse::orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $expense = Expense::create([
            ...$request->validated(),
            'recorded_by' => Auth::id(),
        ]);

        $this->auditLog->log('created', 'expenses', $expense, null, $expense->only(['amount', 'expense_date']));

        $entry = $this->accounting->postExpenseEntry($expense->load('category'));
        $accountingNote = $entry ? '' : ' Accounting entry was not posted — finish Account Mapping setup.';

        return redirect()->route('expenses.index')->with('status', 'Expense recorded successfully.'.$accountingNote);
    }

    public function edit(Expense $expense): View
    {
        $this->authorize('update', $expense);

        return view('expenses.edit', [
            'expense' => $expense,
            'categories' => ExpenseCategory::orderBy('name')->pluck('name', 'id'),
            'warehouses' => Warehouse::orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $before = $expense->only(['amount', 'expense_date']);

        $expense->update($request->validated());

        $this->auditLog->log('updated', 'expenses', $expense, $before, $expense->only(['amount', 'expense_date']));

        return redirect()->route('expenses.index')->with('status', 'Expense updated successfully.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $this->authorize('delete', $expense);

        $before = $expense->only(['amount', 'expense_date']);
        $expense->delete();

        $this->auditLog->log('deleted', 'expenses', null, $before, null);

        return redirect()->route('expenses.index')->with('status', 'Expense deleted successfully.');
    }
}
