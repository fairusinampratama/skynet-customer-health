<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $baseUrl;
    protected string $token;
    protected string $device;

    public function __construct()
    {
        $this->baseUrl = config('services.whatsapp.base_url');
        $this->token = config('services.whatsapp.token');
        $this->device = config('services.whatsapp.device_id');
    }

    /**
     * Send a text message to a generic recipient (generic implementation left for reference).
     */
    /**
     * Send a text message to a Whatspie Group.
     * 
     * @param string $groupId
     * @param string $message
     * @return bool
     */
    public function sendMessageToGroup(string $groupId, string $message): bool
    {
        if (!$this->token || !$this->device) {
            Log::warning('WhatsApp Service: Token or Device ID not configured.');
            return false;
        }

        $endpoint = "{$this->baseUrl}/groups/{$groupId}/send";

        try {
            $response = Http::withToken($this->token)
                ->post($endpoint, [
                    'device' => $this->device,
                    'type' => 'chat', 
                    'params' => [
                        'text' => $message,
                    ]
                ]);

            if ($response->successful()) {
                Log::info('WhatsApp Service: Message sent successfully.', [
                    'recipient' => $groupId
                ]);
                return true;
            }

            Log::error('WhatsApp Service: Failed to send message to group.', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('WhatsApp Service: Exception sending message.', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Send a document (PDF) to a Whatspie Group.
     *
     * @param string $groupId
     * @param string $fileUrl Publicly accessible URL of the file
     * @param string $caption
     * @return bool
     */
    public function sendDocumentToGroup(string $groupId, string $fileUrl, string $caption = '', ?string $filename = null): bool
    {
        if (!$this->token || !$this->device) {
            Log::warning('WhatsApp Service: Token or Device ID not configured.');
            return false;
        }

        // Whatspie Endpoint: POST /groups/{group_id}/send
        // Use the numeric internal group ID (not JID) — this is what Whatspie expects in the URL
        $endpoint = "{$this->baseUrl}/groups/{$groupId}/send";

        try {
            if (!empty($fileUrl)) {
                // Step 1: Upload PDF to tmpfiles.org so Whatspie's servers can download it
                // (Whatspie cannot reach our production domain directly)
                $deliveryUrl = $fileUrl; // fallback to original URL
                if ($filename) {
                    $filePath = \Illuminate\Support\Facades\Storage::disk('public')->path("reports/{$filename}");
                    $uploadResponse = Http::attach('file', file_get_contents($filePath), $filename)
                        ->post('https://tmpfiles.org/api/v1/upload');
                    
                    if ($uploadResponse->successful()) {
                        $viewerUrl = $uploadResponse->json('data.url');
                        // Convert viewer URL to direct download URL: /31745583/file.pdf → /dl/31745583/file.pdf
                        $deliveryUrl = str_replace('tmpfiles.org/', 'tmpfiles.org/dl/', $viewerUrl);
                        Log::info("WhatsApp Service: PDF uploaded to tmpfiles.org: {$deliveryUrl}");
                    } else {
                        Log::warning("WhatsApp Service: tmpfiles.org upload failed, falling back to original URL.");
                    }
                }

                // Step 2: Send to Whatspie with the public CDN URL
                $response = Http::withToken($this->token)
                    ->post($endpoint, [
                        'device' => $this->device,
                        'type' => 'file',
                        'params' => [
                            'document' => [
                                'url' => $deliveryUrl,
                            ],
                            'caption' => $caption,
                        ]
                    ]);

                if ($response->successful()) {
                    Log::info("WhatsApp Service: Document sent successfully. " . $response->body());
                    return true;
                }

                Log::error("WhatsApp Service: Failed to send document. Status: " . $response->status() . " Body: " . $response->body());
            }

            Log::error('WhatsApp Service: Failed to send document to group.', [
                'endpoint' => $endpoint,
                'status' => $response->status() ?? null,
                'body' => $response->body() ?? null,
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('WhatsApp Service: Exception sending document.', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
