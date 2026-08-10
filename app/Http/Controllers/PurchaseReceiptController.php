<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseReceiptRequest;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReceipt;
use App\Services\AuditLogService;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PurchaseReceiptController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLog,
        private readonly InventoryService $inventory,
    ) {
    }

    public function index(): View
    {
        $this->authorize('viewAny', PurchaseReceipt::class);

        $purchaseReceipts = PurchaseReceipt::with(['vendor', 'warehouse', 'purchaseOrder'])
            ->latest('receipt_date')
            ->paginate(20);

        return view('purchase-receipts.index', compact('purchaseReceipts'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', PurchaseReceipt::class);

        $purchaseOrder = PurchaseOrder::with(['vendor', 'warehouse', 'items.productVariant.product', 'items.productVariant.unit'])
            ->where('id', $request->query('purchase_order'))
            ->firstOrFail();

        abort_unless($purchaseOrder->canReceiveStock(), 403, 'This purchase order is not open to receive stock.');

        return view('purchase-receipts.create', compact('purchaseOrder'));
    }

    public function store(StorePurchaseReceiptRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $purchaseOrder = PurchaseOrder::findOrFail($validated['purchase_order_id']);

        $receipt = DB::transaction(function () use ($validated, $purchaseOrder, $request) {
            $receipt = PurchaseReceipt::create([
                'receipt_number' => $this->nextReceiptNumber(),
                'purchase_order_id' => $purchaseOrder->id,
                'vendor_id' => $purchaseOrder->vendor_id,
                'warehouse_id' => $purchaseOrder->warehouse_id,
                'receipt_date' => $validated['receipt_date'],
                'invoice_number' => $validated['invoice_number'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'received_by' => $request->user()->id,
            ]);

            $totalCost = 0;

            foreach ($validated['items'] as $item) {
                $poItem = $purchaseOrder->items()->findOrFail($item['purchase_order_item_id']);
                $lineCost = $item['quantity'] * $item['unit_cost'];
                $totalCost += $lineCost;

                $receiptItem = $receipt->items()->create([
                    'purchase_order_item_id' => $poItem->id,
                    'product_variant_id' => $poItem->product_variant_id,
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'total_cost' => $lineCost,
                    'batch_number' => $item['batch_number'] ?? null,
                    'manufacturing_date' => $item['manufacturing_date'] ?? null,
                    'expiry_date' => $item['expiry_date'] ?? null,
                ]);

                $poItem->increment('quantity_received', $item['quantity']);

                $this->inventory->postTransaction(
                    variant: $poItem->productVariant,
                    warehouse: $purchaseOrder->warehouse,
                    transactionType: \App\Models\InventoryTransaction::TYPE_PURCHASE_RECEIPT,
                    quantity: (float) $item['quantity'],
                    batchNumber: $item['batch_number'] ?? null,
                    referenceType: 'purchase_receipt',
                    referenceId: $receipt->id,
                    notes: "Received against {$purchaseOrder->po_number}",
                    expiryDate: $item['expiry_date'] ?? null,
                );
            }

            $receipt->update(['total_cost' => $totalCost]);

            $purchaseOrder->refresh()->load('items');
            $allReceived = $purchaseOrder->items->every(fn ($i) => $i->quantityRemaining() <= 0);
            $someReceived = $purchaseOrder->items->sum('quantity_received') > 0;

            $purchaseOrder->update([
                'status' => $allReceived
                    ? PurchaseOrder::STATUS_FULLY_RECEIVED
                    : ($someReceived ? PurchaseOrder::STATUS_PARTIALLY_RECEIVED : $purchaseOrder->status),
            ]);

            return $receipt;
        });

        $this->auditLog->log('created', 'purchase_receipts', $receipt, null, ['receipt_number' => $receipt->receipt_number]);

        return redirect()->route('purchase-receipts.show', $receipt)->with('status', 'Stock received successfully.');
    }

    public function show(PurchaseReceipt $purchaseReceipt): View
    {
        $this->authorize('view', $purchaseReceipt);

        $purchaseReceipt->load(['vendor', 'warehouse', 'purchaseOrder', 'receivedBy', 'items.productVariant.product', 'items.productVariant.unit']);

        return view('purchase-receipts.show', compact('purchaseReceipt'));
    }

    private function nextReceiptNumber(): string
    {
        $next = PurchaseReceipt::withTrashed()->count() + 1;

        return 'GRN-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
