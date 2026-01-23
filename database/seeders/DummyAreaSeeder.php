<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Area;
use App\Models\Customer;

class DummyAreaSeeder extends Seeder
{
    public function run(): void
    {
        $areas = [
            'KRIAN' => 381,
            'COMPUTER' => 11,
            'GONDANG' => 160,
            'KUNCI' => 94,
            'RANU' => 131,
            'RESTU' => 34,
            'SENTUL' => 16,
            'ARJOSARI' => 25,
            'LAWANG' => 50,
            'SRIGADING' => 71,
            'MARTOPURO' => 20,
            'KERTOREJO' => 9,
            'SLB' => 30,
            'PURWOSARI' => 15,
            'SONG2' => 20,
            'BANTUR' => 6,
            'BLITAR' => 17,
            'SITUBONDO' => 19,
            'TASIKMADU' => 4,
            'TUTUR' => 8,
            'BUMIAYU' => 20,
            'SUKO' => 64,
            'GAJAH' => 10,
            'BANTUL' => 6,
            'JATISARI' => 9,
            'TEMBELANG' => 5,
            'KAMPUNG' => 2,
            'NGADIREJO' => 6,
            'ALAM' => 18,
            'YONKAV' => 18,
        ];

        foreach ($areas as $name => $count) {
            $area = Area::firstOrCreate(['name' => $name]);
            
            // Generate customers if not enough
            $currentCount = $area->customers()->count();
            $needed = $count - $currentCount;

            if ($needed > 0) {
                Customer::factory()->count($needed)->create([
                    'area_id' => $area->id,
                    'status' => 'up', // Default to UP so board looks green/healthy
                ]);
            }
        }
    }
}
