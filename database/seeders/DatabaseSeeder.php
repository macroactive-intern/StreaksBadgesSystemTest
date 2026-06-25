<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\BadgeDefinitionSeeder;
use Database\Seeders\UserDataSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->createMany([
            ['name' => 'Alice Nguyen',   'email' => 'alice@example.com'],
            ['name' => 'Ben Carter',     'email' => 'ben@example.com'],
            ['name' => 'Carla Reyes',    'email' => 'carla@example.com'],
            ['name' => 'David Kim',      'email' => 'david@example.com'],
        ]);

        $this->call([
            BadgeDefinitionSeeder::class,
            UserDataSeeder::class,
        ]);
    }
}
