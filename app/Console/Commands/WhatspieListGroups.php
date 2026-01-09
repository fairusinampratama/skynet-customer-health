<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class WhatspieListGroups extends Command
{
    protected $signature = 'app:whatspie-list-groups';
    protected $description = 'List all WhatsApp groups and their IDs from Whatspie';

    public function handle()
    {
        $baseUrl = config('services.whatsapp.base_url', env('WHATSAPP_BASE_URL', 'https://api.whatspie.com'));
        $token = config('services.whatsapp.token', env('WHATSAPP_TOKEN'));
        $device = config('services.whatsapp.device_id', env('WHATSAPP_DEVICE_ID'));

        if (!$token || !$device) {
            $this->error("Please configure WHATSAPP_TOKEN and WHATSAPP_DEVICE_ID in your .env file first.");
            return;
        }

        $this->info("Fetching groups for device: {$device}...");

        // Note: The endpoint might vary based on Whatspie version. 
        // Based on research, it is likely GET /groups or similar.
        // Let's try the common endpoint.
        $response = Http::withToken($token)->get("{$baseUrl}/groups", [
            'device' => $device,
            'limit' => 50,
        ]);

        if ($response->successful()) {
            $groups = $response->json('data'); // Assuming standard JSON response wrapper
            
            if (empty($groups)) {
                 $groups = $response->json();
            }
            
            // Debug: Print the first item to see structure
            if (!empty($groups)) {
                $this->info("First Group Data: " . json_encode($groups[0], JSON_PRETTY_PRINT));
            }


            $headers = ['Name', 'Internal ID', 'WhatsApp JID (Try this first if numeric fails)', 'Participants'];
            $this->table($headers, collect($groups)->map(function ($group) {
                return [
                    $group['title'] ?? 'Unknown',
                    $group['id'] ?? 'N/A',
                    $group['jid'] ?? 'N/A',
                    $group['participants_count'] ?? 'N/A',
                ];
            }));
        } else {
            $this->error("Failed to fetch groups: " . $response->status());
            $this->error($response->body());
        }
    }
}
