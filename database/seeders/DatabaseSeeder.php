<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Model events must stay enabled here (no WithoutModelEvents) — UUID
     * primary keys are generated via the HasUuids "creating" event listener.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            AdminUserSeeder::class,
            UnitSeeder::class,
            DummyDataSeeder::class,
        ]);
    }
}
