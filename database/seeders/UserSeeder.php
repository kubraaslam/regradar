<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * One demonstration account per role (SRS FR2). These are local development
 * fixtures for demonstrating and marking the prototype, not real credentials.
 *
 * Idempotent: firstOrCreate keys on email, so re-seeding never duplicates an
 * account or resets a password that has since been changed.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['name' => 'QA Engineer', 'email' => 'qa@regradar.com', 'role' => User::ROLE_QA_ENGINEER],
            ['name' => 'Developer', 'email' => 'dev@regradar.com', 'role' => User::ROLE_DEVELOPER],
            ['name' => 'Admin', 'email' => 'admin@regradar.com', 'role' => User::ROLE_ADMIN],
        ];

        foreach ($accounts as $account) {
            $user = User::firstOrNew(['email' => $account['email']]);

            if (!$user->exists) {
                $user->forceFill([
                    'name' => $account['name'],
                    'role' => $account['role'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ])->save();
            }
        }
    }
}
