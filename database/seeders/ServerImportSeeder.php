<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ServerImportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $file = database_path('migrations/server/ip_address_RO_CORE.csv');
        
        if (! file_exists($file)) {
            $this->command->error("CSV file not found at: {$file}");
            return;
        }

        $handle = fopen($file, 'r');
        $headers = fgetcsv($handle); // Skip headers
        
        $count = 0;
        
        while (($data = fgetcsv($handle)) !== false) {
            // CSV Format: address,interface
            $rawIp = $data[0] ?? null;
            $name = $data[1] ?? 'Unknown Server';
            
            if (! $rawIp) continue;
            
            // Strip CIDR (e.g. 103.../32 -> 103...)
            $ip = explode('/', $rawIp)[0];
            
            \App\Models\Server::updateOrCreate(
                ['ip_address' => $ip],
                [
                    'name' => $name,
                    'status' => 'up', // Default assume up until checked
                ]
            );
            
            $count++;
        }
        
        fclose($handle);
        $this->command->info("Successfully imported {$count} servers.");
    }
}
