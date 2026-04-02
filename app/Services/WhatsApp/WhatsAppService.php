<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class WhatsAppService
{
    protected string $baseUrl;
    protected string $token;
    protected string $device;

    public function __construct()
    {
        $this->baseUrl = config('services.whatsapp.base_url');
        $this->token   = config('services.whatsapp.token');
        $this->device  = config('services.whatsapp.device_id');
    }

    /**
     * Send a text message to a Whatspie Group.
     */
    public function sendMessageToGroup(string $groupId, string $message): bool
    {
        return $this->post("/groups/{$groupId}/send", [
            'type'   => 'chat',
            'params' => ['text' => $message],
        ]);
    }

    /**
     * Send a PDF document to a Whatspie Group.
     * Uploads the file to catbox.moe first so Whatspie can download it reliably.
     */
    public function sendDocumentToGroup(string $groupId, string $caption, string $filePath): bool
    {
        $url = $this->uploadToCatbox($filePath);

        return $this->post("/groups/{$groupId}/send", [
            'type'   => 'file',
            'params' => [
                'document' => ['url' => $url],
                'caption'  => $caption,
            ],
        ]);
    }

    /**
     * Upload a local file to catbox.moe and return the public URL.
     * Falls back to local Storage::url() if upload fails.
     */
    private function uploadToCatbox(string $filePath): string
    {
        $fallback = Storage::disk('public')->url(
            'reports/' . basename($filePath)
        );

        try {
            $response = Http::attach(
                'fileToUpload',
                file_get_contents($filePath),
                basename($filePath),
                ['Content-Type' => 'application/pdf']
            )->post('https://catbox.moe/user/api.php', ['reqtype' => 'fileupload']);

            if ($response->successful() && str_starts_with($response->body(), 'https://')) {
                $url = trim($response->body());
                Log::info("WhatsApp: Uploaded to catbox.moe: {$url}");
                return $url;
            }
        } catch (\Exception $e) {
            Log::warning("WhatsApp: catbox.moe upload exception: " . $e->getMessage());
        }

        Log::warning("WhatsApp: catbox.moe upload failed, using fallback URL: {$fallback}");
        return $fallback;
    }

    /**
     * Make a POST request to the Whatspie API.
     */
    private function post(string $path, array $payload): bool
    {
        if (!$this->token || !$this->device) {
            Log::warning('WhatsApp: Token or Device ID not configured.');
            return false;
        }

        $endpoint = $this->baseUrl . $path;
        $payload  = array_merge(['device' => $this->device], $payload);

        try {
            $response = Http::withToken($this->token)->post($endpoint, $payload);

            if ($response->successful()) {
                Log::info("WhatsApp: {$path} OK — " . $response->body());
                return true;
            }

            Log::error("WhatsApp: {$path} failed [{$response->status()}] — " . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error("WhatsApp: Exception on {$path} — " . $e->getMessage());
            return false;
        }
    }
}
