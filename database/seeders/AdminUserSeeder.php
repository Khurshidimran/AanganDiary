<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('SUPER_ADMIN_EMAIL', 'dairyaangan@gmail.com');
        $password = env('SUPER_ADMIN_PASSWORD', 'ChangeMe!12345');

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Super Admin',
                'password' => $password,
                'status' => 'active',
                'email_verified_at' => now(),
            ],
        );

        $user->syncRoles(['Super Admin']);

        $this->command->warn("Super Admin seeded: {$email} / {$password} — change this password after first login.");
    }
}
