<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['customer_id', 'label', 'address1', 'address2', 'city', 'country', 'phone', 'is_default'])]
class CustomerAddress extends Model
{
    use HasUuid, SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * "Home, 12 Main Blvd, Lahore" — used as the option text in the saved-
     * addresses picker on the manual order-creation screen.
     */
    public function label(): string
    {
        return implode(', ', array_filter([$this->label, $this->address1, $this->city]));
    }
}
