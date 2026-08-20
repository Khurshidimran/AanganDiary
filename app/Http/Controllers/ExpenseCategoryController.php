<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseCategoryRequest;
use App\Http\Requests\UpdateExpenseCategoryRequest;
use App\Models\Account;
use App\Models\ExpenseCategory;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ExpenseCategoryController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLog)
    {
    }

    public function index(): View
    {
        $this->authorize('viewAny', ExpenseCategory::class);

        $expenseCategories = ExpenseCategory::withCount('expenses')->orderBy('name')->paginate(20);

        return view('expense-categories.index', compact('expenseCategories'));
    }

    public function create(): View
    {
        $this->authorize('create', ExpenseCategory::class);

        return view('expense-categories.create', ['accounts' => $this->expenseAccounts()]);
    }

    public function store(StoreExpenseCategoryRequest $request): RedirectResponse
    {
        $category = ExpenseCategory::create($request->validated());

        $this->auditLog->log('created', 'expense_categories', $category, null, $category->only(['name', 'status']));

        return redirect()->route('expense-categories.index')->with('status', 'Expense category created successfully.');
    }

    public function edit(ExpenseCategory $expenseCategory): View
    {
        $this->authorize('update', $expenseCategory);

        return view('expense-categories.edit', ['expenseCategory' => $expenseCategory, 'accounts' => $this->expenseAccounts()]);
    }

    public function update(UpdateExpenseCategoryRequest $request, ExpenseCategory $expenseCategory): RedirectResponse
    {
        $before = $expenseCategory->only(['name', 'status']);

        $expenseCategory->update($request->validated());

        $this->auditLog->log('updated', 'expense_categories', $expenseCategory, $before, $expenseCategory->only(['name', 'status']));

        return redirect()->route('expense-categories.index')->with('status', 'Expense category updated successfully.');
    }

    public function destroy(ExpenseCategory $expenseCategory): RedirectResponse
    {
        $this->authorize('delete', $expenseCategory);

        if ($expenseCategory->expenses()->exists()) {
            return redirect()->route('expense-categories.index')
                ->with('error', "Cannot delete \"{$expenseCategory->name}\" — it has expense history.");
        }

        $before = $expenseCategory->only(['name', 'status']);
        $expenseCategory->delete();

        $this->auditLog->log('deleted', 'expense_categories', null, $before, null);

        return redirect()->route('expense-categories.index')->with('status', 'Expense category deleted successfully.');
    }

    private function expenseAccounts(): \Illuminate\Support\Collection
    {
        return Account::where('type', Account::TYPE_EXPENSE)->where('status', Account::STATUS_ACTIVE)->orderBy('code')->get();
    }
}
