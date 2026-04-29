<?php

namespace App\Services;

use Carbon\Carbon;

class N8nService extends ApiClient
{
    protected $apiUrl;
    protected $apiKey;
    protected $apiName = "n8nWhatsapp";

    public function __construct()
    {
        $credentials = [
            "apiUrl" => env('N8N_API_URL'),
            "apiKey" => env('N8N_APIKEY')
        ];

        $this->apiUrl = $credentials['apiUrl'];
        $this->apiKey = $credentials['apiKey'];
    }

    public function sendWhatsappTextMessage($phone, $message, $imageUrls = null)
    {
        $response = $this->sendRest($this->apiName, 'POST', $this->apiUrl . '/ad24c477-77be-44cb-a073-a1db18691985', [
            'headers' => [
                'Content-Type' => 'application/json',
                'X-API-KEY' => $this->apiKey,
                'accept-encoding' => 'gzip, deflate, br'
            ],
            'json' => [
                'phone' => $phone,
                'message' => $message,
                'event' => 'message',
                'imageUrls' => $imageUrls
            ]
        ]);
    }

    
} 