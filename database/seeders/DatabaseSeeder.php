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
        // User::factory(10)->create();

        \App\Models\User::factory()->create([
            'full_name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'role' => 'client',
        ]);

        \App\Models\Template::create([
            'name' => 'Royal Wedding',
            'category' => 'Mariage',
            'price_per_pack' => 9.99,
            'config_schema' => ['colors' => ['gold', 'white'], 'photo_slots' => 1],
            'is_active' => true,
        ]);
    }
}
