<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'maya@example.com'],
            ['name' => 'Maya Chen', 'password' => bcrypt('password')],
        );

        User::updateOrCreate(
            ['email' => 'noah@example.com'],
            ['name' => 'Noah Williams', 'password' => bcrypt('password')],
        );

        User::updateOrCreate(
            ['email' => 'priya@example.com'],
            ['name' => 'Priya Shah', 'password' => bcrypt('password')],
        );
    }
}
