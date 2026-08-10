<?php

namespace App\Http\Controllers\Api\Rider;

use App\Http\Controllers\Controller;
use App\Http\Resources\RiderWalletTransactionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $rider = $request->user()->riderProfile;

        $transactions = $rider->walletTransactions()->latest('created_at')->paginate(20);

        return response()->json([
            'wallet_balance' => (float) $rider->wallet_balance,
            'transactions' => RiderWalletTransactionResource::collection($transactions->items()),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }
}
