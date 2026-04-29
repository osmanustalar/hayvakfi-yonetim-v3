<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

abstract class ApiClient
{
    protected function sendRest(string $name, string $method, string $url, array $options = []): Response
    {
        $headers = $options['headers'] ?? [];
        $payload = $options['json'] ?? [];

        $client = Http::withHeaders($headers)->timeout(30);

        return match (strtoupper($method)) {
            'GET' => $client->get($url, $payload),
            'PUT' => $client->put($url, $payload),
            'PATCH' => $client->patch($url, $payload),
            'DELETE' => $client->delete($url, $payload),
            default => $client->post($url, $payload),
        };
    }
}
