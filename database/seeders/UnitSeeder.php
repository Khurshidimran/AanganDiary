<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    private const UNITS = [
        ['name' => 'Gram', 'short_code' => 'g', 'type' => 'mass', 'conversion_factor' => 1],
        ['name' => 'Kilogram', 'short_code' => 'kg', 'type' => 'mass', 'conversion_factor' => 1000],
        ['name' => 'Milliliter', 'short_code' => 'ml', 'type' => 'volume', 'conversion_factor' => 1],
        ['name' => 'Liter', 'short_code' => 'L', 'type' => 'volume', 'conversion_factor' => 1000],
        ['name' => 'Piece', 'short_code' => 'pcs', 'type' => 'count', 'conversion_factor' => 1],
    ];

    public function run(): void
    {
        foreach (self::UNITS as $unit) {
            Unit::updateOrCreate(['name' => $unit['name']], [...$unit, 'status' => 'active']);
        }
    }
}
