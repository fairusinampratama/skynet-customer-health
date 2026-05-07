<?php

namespace App\Console\Commands;

use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Console\Command;

class WhatspieSendTest extends Command
{
    protected $signature = 'app:whatspie-send-test
        {--to= : WhatsApp receiver number in international format}
        {--group= : Whatspie group ID, with or without leading #}
        {--message= : Test message text}';

    protected $description = 'Send a small Whatspie test message to a WhatsApp number or group';

    public function handle(WhatsAppService $whatsAppService): int
    {
        $receiver = $this->option('to');
        $group = $this->option('group');
        $message = $this->option('message') ?: 'Skynet Customer Health local Whatspie test - ' . now()->format('Y-m-d H:i:s');

        if (!$receiver && !$group) {
            $this->error('Pass --to=628... or --group=22750.');
            return self::FAILURE;
        }

        $ok = true;

        if ($receiver) {
            $this->info("Sending test message to {$receiver}...");
            $ok = $whatsAppService->sendMessageToNumber($receiver, $message) && $ok;
        }

        if ($group) {
            $group = ltrim($group, '#');
            $this->info("Sending test message to group {$group}...");
            $ok = $whatsAppService->sendMessageToGroup($group, $message) && $ok;
        }

        if (!$ok) {
            $this->error('One or more Whatspie sends failed. Check storage/logs/laravel.log for the API response.');
            return self::FAILURE;
        }

        $this->info('Whatspie test message sent.');
        return self::SUCCESS;
    }
}
