<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'sync_type', 'status', 'items_processed', 'items_created', 'items_updated',
    'items_failed', 'error_summary', 'triggered_by', 'started_at', 'finished_at',
])]
class ShopifySyncLog extends Model
{
    use HasUuid;

    public const TYPE_PRODUCTS_IMPORT = 'products_import';
    public const TYPE_INVENTORY_PUSH = 'inventory_push';

    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }
}
