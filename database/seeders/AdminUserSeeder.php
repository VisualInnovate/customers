<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use InvalidArgumentException;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (!is_string($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Set ADMIN_EMAIL to a valid email address before seeding.');
        }

        if (!is_string($password) || $password === '') {
            throw new RuntimeException('Set ADMIN_PASSWORD before seeding.');
        }

        $admin = User::firstOrCreate(
            ['email' => $email],
            [
                'first_name' => env('ADMIN_NAME', 'Administrator'),
                'role' => 'admin',
                'password' => $password,
                'email_verified_at' => now(),
            ]
        );

        if ($admin->role !== 'admin') {
            $admin->forceFill(['role' => 'admin'])->save();
        }
    }
}
