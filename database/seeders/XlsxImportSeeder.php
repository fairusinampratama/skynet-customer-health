<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Customer;
use Illuminate\Database\Seeder;
use OpenSpout\Reader\XLSX\Reader;
use Illuminate\Support\Facades\File;

class XlsxImportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $directory = database_path('migrations/ppp_secret');
        $files = File::glob("{$directory}/*.xlsx");

        if (empty($files)) {
            $this->command->warn("No XLSX files found in: {$directory}");
            return;
        }

        $this->command->info("Found " . count($files) . " XLSX files. Starting import...");

        foreach ($files as $filePath) {
            $fileName = basename($filePath);
            $this->command->info("Processing: {$fileName}");

            $reader = new Reader();
            $reader->open($filePath);

            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $cells = $row->getCells();
                    $data = [];
                    foreach ($cells as $cell) {
                        $data[] = $cell->getValue();
                    }

                    // Skip empty rows
                    if (empty($data) || empty($data[0])) continue;

                    // Skip Header Row (check if first col is 'name')
                    if (strtolower(trim((string)$data[0])) === 'name') continue;

                    // Mapping: name (0), remote_address (1), lokasi (2), router (3)
                    $name = trim((string)($data[0] ?? ''));
                    $ip = trim((string)($data[1] ?? ''));
                    // $location = trim((string)($data[2] ?? '')); // Unused
                    $areaName = trim((string)($data[3] ?? ''));

                    if (empty($name) || empty($ip)) continue;

                    // Create or Get Area
                    // If areaName is empty, maybe fallback to 'Unknown' or skip? 
                    // Based on CSV logic, we assume it's there.
                    if (empty($areaName)) {
                         // Fallback: Use filename as Area? "ppp_secrets_arjosari.xlsx" -> "arjosari"
                         // Only if column is empty.
                         $areaName = str_replace(['ppp_secrets_', '.xlsx'], '', $fileName);
                         $areaName = ucfirst($areaName);
                    }

                    $area = Area::firstOrCreate(['name' => $areaName]);

                    // Create Customer (update if exists by IP? Or name? Assuming new import)
                    Customer::firstOrCreate(
                        ['ip_address' => $ip],
                        [
                            'area_id' => $area->id,
                            'name' => $name,
                            'status' => 'up',
                        ]
                    );
                }
            }

            $reader->close();
        }

        $this->command->info('XLSX Import completed successfully.');
    }
}
