<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    private const CATEGORIES = [
        'Rent', 'Utilities', 'Fuel & Transport', 'Maintenance & Repairs',
        'Office Supplies', 'Marketing & Advertising', 'Packaging Material', 'Miscellaneous',
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $name) {
            ExpenseCategory::updateOrCreate(['name' => $name], ['status' => 'active']);
        }
    }
}
