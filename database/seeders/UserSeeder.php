<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'QA Engineer',
            'email' => 'qa@regradar.com',
            'password' => Hash::make('password'),
            'role' => 'qa_engineer',
        ]);
    
        User::create([
            'name' => 'Developer',
            'email' => 'dev@regradar.com',
            'password' => Hash::make('password'),
            'role' => 'developer',
        ]);

        User::create([
            'name' => 'Admin',
            'email' => 'admin@regradar.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);
    }
}
