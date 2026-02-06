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
        // Create Admin User
        \App\Models\User::updateOrCreate(
            ['email' => 'admin@akt.com'],
            [
                'name' => 'Admin',
                'password' => 'password',
            ]
        );

        // $this->call([
        //     XlsxImportSeeder::class,
        //     ServerImportSeeder::class,
        //     IsolationStateSeeder::class,
        //     DummyAreaSeeder::class,
        // ]);
    }
}
