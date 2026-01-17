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
        $this->baseUrl = config('services.whatsapp.base_url', env('WHATSAPP_BASE_URL', 'https://api.whatspie.com'));
        $this->token = config('services.whatsapp.token', env('WHATSAPP_TOKEN', ''));
        $this->device = config('services.whatsapp.device_id', env('WHATSAPP_DEVICE_ID', ''));
    }

    /**
     * Send a text message to a generic recipient (generic implementation left for reference).
     */
    public function sendMessage(string $to, string $message): bool
    {
        // Implementation depends on if $to is a group or user. 
        // For now, focusing on the Report requirement.
        return false;
    }

    /**
     * Send a document (PDF) to a Whatspie Group.
     *
     * @param string $groupId
     * @param string $fileUrl Publicly accessible URL of the file
     * @param string $caption
     * @return bool
     */
    public function sendDocumentToGroup(string $groupId, string $fileUrl, string $caption = '', string $filename = null): bool
    {
        if (!$this->token || !$this->device) {
            Log::warning('WhatsApp Service: Token or Device ID not configured.');
            return false;
        }

        // Whatspie Endpoint: POST /groups/{group_id}/send
        // Payload: type=file, device=..., params={file_url: ..., caption: ...}
        $endpoint = "{$this->baseUrl}/groups/{$groupId}/send";

        try {
            $response = Http::withToken($this->token)
                ->post($endpoint, [
                    'device' => $this->device,
                    'type' => 'file',
                    'params' => [
                        'document' => [
                            'url' => $fileUrl,
                            // 'filename' => $filename, // Removed as it causes API error
                        ], 
                        'caption' => $caption,
                    ]
                ]);

            if ($response->successful()) {
                $responseData = $response->json();
                echo "\n--- Whatspie Response ---\n";
                print_r($responseData);
                echo "\n-------------------------\n";
                
                Log::info('WhatsApp Service: Document sent successfully.', [
                    'recipient' => $groupId, 
                    'response' => $responseData
                ]);
                return true;
            }

            Log::error('WhatsApp Service: Failed to send document to group.', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('WhatsApp Service: Exception sending document.', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
