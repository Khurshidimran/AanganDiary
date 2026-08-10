<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name', 'company_name', 'contact_person', 'phone', 'email', 'address',
    'tax_number', 'payment_terms', 'opening_balance', 'status', 'notes',
])]
class Vendor extends Model
{
    use HasUuid, SoftDeletes;

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
        ];
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function purchaseReceipts(): HasMany
    {
        return $this->hasMany(PurchaseReceipt::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(VendorPayment::class);
    }

    public function totalReceived(): float
    {
        return (float) $this->purchaseReceipts()->sum('total_cost');
    }

    public function totalPaid(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    public function payableBalance(): float
    {
        return (float) $this->opening_balance + $this->totalReceived() - $this->totalPaid();
    }
}
