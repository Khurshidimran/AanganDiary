<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'transfer_number', 'from_warehouse_id', 'to_warehouse_id', 'status', 'transfer_date',
    'notes', 'created_by', 'approved_by', 'dispatched_by', 'received_by', 'dispatched_at', 'received_at',
])]
class StockTransfer extends Model
{
    use HasUuid, SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_REQUESTED = 'requested';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_IN_TRANSIT = 'in_transit';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_CANCELLED = 'cancelled';

    protected function casts(): array
    {
        return [
            'transfer_date' => 'date',
            'dispatched_at' => 'datetime',
            'received_at' => 'datetime',
        ];
    }

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function dispatchedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatched_by');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class);
    }

    public function isEditable(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REQUESTED, self::STATUS_APPROVED], true);
    }
}
