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
        // Create Admin User
        \App\Models\User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@akt.com',
            'password' => bcrypt('password'),
        ]);

        // $this->call([
        //     XlsxImportSeeder::class,
        //     ServerImportSeeder::class,
        //     IsolationStateSeeder::class,
        //     DummyAreaSeeder::class,
        // ]);
    }
}
