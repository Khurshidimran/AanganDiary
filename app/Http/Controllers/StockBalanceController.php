<?php

namespace App\Http\Controllers;

use App\Models\StockBalance;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockBalanceController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('stock.view');

        $balances = StockBalance::with(['productVariant.product', 'productVariant.unit', 'warehouse'])
            ->where('quantity', '>', 0)
            ->when($request->filled('warehouse_id'), fn ($q) => $q->where('warehouse_id', $request->query('warehouse_id')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->query('search');
                $q->whereHas('productVariant', function ($vq) use ($search) {
                    $vq->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhereHas('product', fn ($pq) => $pq->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderBy('warehouse_id')
            ->paginate(25)
            ->withQueryString();

        return view('stock-balances.index', [
            'balances' => $balances,
            'warehouses' => Warehouse::orderBy('name')->pluck('name', 'id'),
        ]);
    }
}
