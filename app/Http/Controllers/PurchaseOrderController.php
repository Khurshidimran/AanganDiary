<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseOrderRequest;
use App\Http\Requests\UpdatePurchaseOrderRequest;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PurchaseOrderController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLog)
    {
    }

    public function index(): View
    {
        $this->authorize('viewAny', PurchaseOrder::class);

        $purchaseOrders = PurchaseOrder::with(['vendor', 'warehouse'])->latest('order_date')->paginate(20);

        return view('purchase-orders.index', compact('purchaseOrders'));
    }

    public function create(): View
    {
        $this->authorize('create', PurchaseOrder::class);

        return view('purchase-orders.create', $this->formData());
    }

    public function store(StorePurchaseOrderRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $purchaseOrder = DB::transaction(function () use ($validated, $request) {
            $purchaseOrder = PurchaseOrder::create([
                'po_number' => $this->nextPoNumber(),
                'vendor_id' => $validated['vendor_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'status' => PurchaseOrder::STATUS_DRAFT,
                'order_date' => $validated['order_date'],
                'expected_date' => $validated['expected_date'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            foreach ($validated['items'] as $item) {
                $purchaseOrder->items()->create($item);
            }

            return $purchaseOrder;
        });

        $this->auditLog->log('created', 'purchase_orders', $purchaseOrder, null, ['po_number' => $purchaseOrder->po_number]);

        return redirect()->route('purchase-orders.show', $purchaseOrder)->with('status', 'Purchase order created successfully.');
    }

    public function show(PurchaseOrder $purchaseOrder): View
    {
        $this->authorize('view', $purchaseOrder);

        $purchaseOrder->load(['vendor', 'warehouse', 'createdBy', 'items.productVariant.product', 'items.productVariant.unit', 'receipts']);

        return view('purchase-orders.show', compact('purchaseOrder'));
    }

    public function edit(PurchaseOrder $purchaseOrder): View
    {
        $this->authorize('update', $purchaseOrder);

        $purchaseOrder->load('items');

        return view('purchase-orders.edit', [...$this->formData(), 'purchaseOrder' => $purchaseOrder]);
    }

    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $purchaseOrder) {
            $purchaseOrder->update([
                'vendor_id' => $validated['vendor_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'order_date' => $validated['order_date'],
                'expected_date' => $validated['expected_date'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            $purchaseOrder->items()->delete();

            foreach ($validated['items'] as $item) {
                $purchaseOrder->items()->create($item);
            }
        });

        $this->auditLog->log('updated', 'purchase_orders', $purchaseOrder, null, ['po_number' => $purchaseOrder->po_number]);

        return redirect()->route('purchase-orders.show', $purchaseOrder)->with('status', 'Purchase order updated successfully.');
    }

    public function destroy(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorize('delete', $purchaseOrder);

        $purchaseOrder->delete();

        $this->auditLog->log('deleted', 'purchase_orders', null, ['po_number' => $purchaseOrder->po_number], null);

        return redirect()->route('purchase-orders.index')->with('status', 'Purchase order deleted successfully.');
    }

    public function submit(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorize('submit', $purchaseOrder);

        $purchaseOrder->update(['status' => PurchaseOrder::STATUS_SUBMITTED]);

        $this->auditLog->log('submitted', 'purchase_orders', $purchaseOrder, null, ['status' => $purchaseOrder->status]);

        return back()->with('status', 'Purchase order submitted for approval.');
    }

    public function approve(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorize('approve', $purchaseOrder);

        $purchaseOrder->update(['status' => PurchaseOrder::STATUS_APPROVED]);

        $this->auditLog->log('approved', 'purchase_orders', $purchaseOrder, null, ['status' => $purchaseOrder->status]);

        return back()->with('status', 'Purchase order approved.');
    }

    public function cancel(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorize('cancel', $purchaseOrder);

        $purchaseOrder->update(['status' => PurchaseOrder::STATUS_CANCELLED]);

        $this->auditLog->log('cancelled', 'purchase_orders', $purchaseOrder, null, ['status' => $purchaseOrder->status]);

        return back()->with('status', 'Purchase order cancelled.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'vendors' => Vendor::where('status', 'active')->orderBy('name')->pluck('name', 'id'),
            'warehouses' => Warehouse::where('status', 'active')->orderBy('name')->pluck('name', 'id'),
            'variants' => ProductVariant::with('product')->where('is_active', true)->get(),
        ];
    }

    private function nextPoNumber(): string
    {
        $next = PurchaseOrder::withTrashed()->count() + 1;

        return 'PO-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
