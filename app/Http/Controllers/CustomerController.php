<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Account;
use App\Models\Customer;
use App\Models\Order;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLog)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Customer::class);

        $customers = Customer::withCount('orders')
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = $request->query('q');
                $query->where(fn ($q) => $q->where('name', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%"));
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('customers.index', compact('customers'));
    }

    public function create(): View
    {
        $this->authorize('create', Customer::class);

        return view('customers.create', ['accounts' => $this->accountOptions()]);
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $customer = Customer::create($request->validated());

        $this->auditLog->log('created', 'customers', $customer, null, $customer->only(['name', 'phone']));

        return redirect()->route('customers.index')->with('status', 'Customer created successfully.');
    }

    public function edit(Customer $customer): View
    {
        $this->authorize('update', $customer);

        $customer->load('addresses');

        $orders = $customer->orders()->latest('shopify_created_at')->take(10)->get();
        $outstandingCredit = $customer->orders()
            ->where('payment_type', Order::PAYMENT_TYPE_CREDIT)
            ->sum('total_outstanding');

        return view('customers.edit', [
            'customer' => $customer,
            'accounts' => $this->accountOptions(),
            'orders' => $orders,
            'outstandingCredit' => $outstandingCredit,
        ]);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $before = $customer->only(['name', 'phone', 'email', 'account_id']);

        $customer->update($request->validated());

        $this->auditLog->log('updated', 'customers', $customer, $before, $customer->only(['name', 'phone', 'email', 'account_id']));

        return redirect()->route('customers.index')->with('status', 'Customer updated successfully.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $this->authorize('delete', $customer);

        if ($customer->orders()->exists()) {
            return redirect()->route('customers.index')
                ->with('error', "Cannot delete \"{$customer->name}\" — they have order history.");
        }

        $before = $customer->only(['name', 'phone']);
        $customer->delete();

        $this->auditLog->log('deleted', 'customers', null, $before, null);

        return redirect()->route('customers.index')->with('status', 'Customer deleted successfully.');
    }

    /**
     * @return \Illuminate\Support\Collection<int, Account>
     */
    private function accountOptions()
    {
        return Account::where('status', Account::STATUS_ACTIVE)->orderBy('code')->get();
    }
}
