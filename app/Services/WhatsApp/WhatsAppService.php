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
                Log::info('WhatsApp Service: Document sent successfully.', [
                    'recipient' => $groupId, 
                    'response' => $response->json()
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
    public function sendDocument(string $phone, string $fileUrl, string $caption = ''): bool
    {
        if (!$this->token || !$this->device) {
            Log::warning('WhatsApp Service: Token or Device ID not configured.');
            return false;
        }

        // Whatspie Endpoint: POST /messages/send
        $endpoint = "{$this->baseUrl}/messages/send";

        try {
            $response = Http::withToken($this->token)
                ->post($endpoint, [
                    'device' => $this->device,
                    'receiver' => $phone,
                    'type' => 'file',
                    'params' => [
                        'document' => [
                            'url' => $fileUrl,
                        ],
                        'caption' => $caption,
                    ]
                ]);

            if ($response->successful()) {
                Log::info('WhatsApp Service: Document sent to individual successfully.', [
                    'recipient' => $phone,
                    'response' => $response->json()
                ]);
                return true;
            }

            Log::error('WhatsApp Service: Failed to send document to individual.', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('WhatsApp Service: Exception sending document to individual.', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
