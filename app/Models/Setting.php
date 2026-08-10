<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['group', 'key', 'value'])]
class Setting extends Model
{
    use HasUuid;

    protected function casts(): array
    {
        return [
            'value' => 'json',
        ];
    }
}
