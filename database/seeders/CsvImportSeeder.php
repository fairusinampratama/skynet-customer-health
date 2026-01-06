<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Customer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class CsvImportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csvPath = database_path('migrations/ppp_secrets_randuagung.csv');

        if (! File::exists($csvPath)) {
            $this->command->warn("CSV file not found at: {$csvPath}");
            return;
        }

        $lines = file($csvPath, FILE_IGNORE_NEW_LINES);
        $header = array_shift($lines); // Remove header

        $this->command->info('Importing customers from CSV...');

        foreach ($lines as $line) {
            if (empty(trim($line))) continue;

            // Format: name;remote_address;lokasi;router
            $data = explode(';', $line);
            
            if (count($data) < 4) continue;

            $name = trim($data[0]);
            $ip = trim($data[1]);
            $location = trim($data[2]); // lokasi from CSV
            $areaName = trim($data[3]); // router from CSV is actually Area

            // Create or Get Area
            $area = Area::firstOrCreate(['name' => $areaName]);

            // Create Customer
            Customer::create([
                'area_id' => $area->id,
                'name' => $name,
                'ip_address' => $ip,
                'location' => $location, // Use location column
                'status' => 'up', // Default status
            ]);
        }

        $this->command->info('CSV Import completed.');
    }
}
