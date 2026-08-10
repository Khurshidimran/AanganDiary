<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

trait HasUuid
{
    use HasUuids;

    /**
     * Eloquent auto-calls initialize{TraitName}() per used trait on construction.
     * Property redeclaration (public $incrementing = false;) isn't usable here —
     * PHP 8.3 treats that as an incompatible conflict with Model's own declaration.
     */
    public function initializeHasUuid(): void
    {
        $this->incrementing = false;
        $this->keyType = 'string';
    }
}
