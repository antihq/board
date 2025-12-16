<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->withPersonalTeam(['name' => 'Oliver\'s Team'])->create([
            'name' => 'Oliver Servín',
            'email' => 'oliver@example.com',
        ]);
    }
}
