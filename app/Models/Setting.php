<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use App\Models\Concerns\HasLocalizedTimestamps;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['group', 'key', 'value'])]
class Setting extends Model
{
    use HasUuid, HasLocalizedTimestamps;

    protected function casts(): array
    {
        return [
            'value' => 'json',
        ];
    }
}
