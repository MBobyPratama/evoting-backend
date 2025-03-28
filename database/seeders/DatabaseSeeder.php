<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'nim' => '2310512056',
            'email' => 'user@example.com',
            'password' => bcrypt('string'),
            'role' => 'admin',
            'image_url' => '/storage/avatars/defaultAvatar.jpg',
        ]);
    }
}
