<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin user
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('adminadmin'),
                'role' => 'admin',
            ]
        );

        // Regular user
        User::updateOrCreate(
            ['email' => 'user@gmail.com'],
            [
                'name' => 'User',
                'password' => Hash::make('useruser'),
                'role' => 'user',
            ]
        );
    }
}

