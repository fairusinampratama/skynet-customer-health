<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class IsolationStateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $isolatedIps = [
            '192.0.2.20',
            '192.0.2.21',
        ];

        // 1. Mark All as Not Isolated (Default) - This handles the "others as not true" part
        // Since migration default is now false, this is implicit for new records, 
        // but for existing records, we should ensure it.
        Customer::query()->update(['is_isolated' => false]);

        // 2. Mark specific IPs as Isolated
        Customer::whereIn('ip_address', $isolatedIps)->update(['is_isolated' => true]);
        
        $this->command->info('Isolation State Seeder: Marked ' . count($isolatedIps) . ' customers as ISOLATED.');
    }
}
