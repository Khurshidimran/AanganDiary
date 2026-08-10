<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'code', 'address', 'contact_person', 'phone', 'latitude', 'longitude', 'shopify_location_id', 'status'])]
class Warehouse extends Model
{
    use HasUuid, SoftDeletes;

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function stockBalances(): HasMany
    {
        return $this->hasMany(StockBalance::class);
    }
}
