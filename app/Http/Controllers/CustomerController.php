<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Account;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Services\AuditLogService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLog)
    {
    }

    /**
     * Feeds the "Find Existing Customer" searchable picker on the manual
     * order-creation screen — gated by orders.create (not customers.view)
     * since that's the only place this is used, and a user who can create
     * orders should be able to look up a customer for one regardless of
     * whether they separately have Customer module access.
     */
    public function search(Request $request): JsonResponse
    {
        $this->authorize('create', Order::class);

        $term = trim((string) $request->query('q', ''));

        if ($term === '') {
            return response()->json([]);
        }

        $customers = Customer::with('addresses')
            ->where(fn ($q) => $q->where('phone', 'like', "%{$term}%")->orWhere('name', 'like', "%{$term}%"))
            ->orderBy('name')
            ->limit(20)
            ->get();

        return response()->json($customers->map(fn (Customer $customer) => [
            'value' => $customer->id,
            'text' => "{$customer->name} — {$customer->phone}",
            'name' => $customer->name,
            'phone' => $customer->phone,
            'email' => $customer->email,
            'addresses' => $customer->addresses->map(fn (CustomerAddress $address) => [
                'id' => $address->id,
                'label' => $address->label(),
                'address1' => $address->address1,
                'address2' => $address->address2,
                'city' => $address->city,
                'country' => $address->country,
                'phone' => $address->phone,
                'is_default' => $address->is_default,
            ])->values(),
        ]));
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

    /**
     * A single chronological statement of everything that affects what this
     * customer owes — every order (a charge, regardless of how it's paid)
     * and every settlement against it, running to a balance. An order paid
     * instantly (cash/online at delivery or pickup) never creates a real
     * OrderPayment row — that settlement is synthesized here from
     * total - total_outstanding so it still nets out correctly rather than
     * inflating the balance as if the order were unpaid; a genuine credit
     * order's real OrderPayment rows (from Record Payment) are used as-is.
     */
    public function ledger(Request $request, Customer $customer): View
    {
        $this->authorize('view', $customer);

        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->query('date_from'))->startOfDay() : null;
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->query('date_to'))->endOfDay() : null;

        $orders = $customer->orders()
            ->where('order_status', '!=', Order::ORDER_STATUS_CANCELLED)
            ->with('payments')
            ->get();

        $rows = collect();

        foreach ($orders as $order) {
            $rows->push([
                'date' => $order->shopify_created_at,
                'description' => "Order #{$order->shopify_order_number}",
                'subtitle' => str($order->order_type)->headline().' · '.str($order->payment_type)->headline(),
                'order' => $order,
                'debit' => (float) $order->total,
                'credit' => 0.0,
            ]);

            if ($order->payments->isNotEmpty()) {
                foreach ($order->payments as $payment) {
                    $rows->push([
                        'date' => $payment->payment_date,
                        'description' => "Payment — Order #{$order->shopify_order_number}",
                        'subtitle' => str($payment->method ?? 'payment')->headline().($payment->reference_number ? " · {$payment->reference_number}" : ''),
                        'order' => $order,
                        'debit' => 0.0,
                        'credit' => (float) $payment->amount,
                    ]);
                }
            } else {
                $settled = (float) $order->total - (float) $order->total_outstanding;

                if ($settled > 0.009) {
                    $rows->push([
                        'date' => $order->delivered_at ?? $order->shopify_created_at,
                        'description' => "Paid — Order #{$order->shopify_order_number}",
                        'subtitle' => 'Settled via '.str($order->payment_type)->headline(),
                        'order' => $order,
                        'debit' => 0.0,
                        'credit' => $settled,
                    ]);
                }
            }
        }

        $rows = $rows->sortBy('date')->values();

        $opening = $dateFrom
            ? $rows->filter(fn ($r) => $r['date'] < $dateFrom)->sum(fn ($r) => $r['debit'] - $r['credit'])
            : 0.0;

        $filteredRows = $rows->filter(fn ($r) => (! $dateFrom || $r['date'] >= $dateFrom) && (! $dateTo || $r['date'] <= $dateTo))->values();

        $running = $opening;
        $filteredRows = $filteredRows->map(function ($row) use (&$running) {
            $running += $row['debit'] - $row['credit'];
            $row['balance'] = $running;

            return $row;
        });

        $closing = $running;

        return view('customers.ledger', compact('customer', 'filteredRows', 'opening', 'closing', 'dateFrom', 'dateTo'));
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
