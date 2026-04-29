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
        // Create sample users
        User::factory()->create([
            'name'  => 'John Doe',
            'email' => 'john@example.com',
        ]);

        User::factory()->create([
            'name'  => 'Jane Smith',
            'email' => 'jane@example.com',
        ]);

        User::factory()->create([
            'name'  => 'Admin User',
            'email' => 'admin@example.com',
        ]);

        // Seed products
        $this->call([
            ProductSeeder::class,
        ]);
    }
}
