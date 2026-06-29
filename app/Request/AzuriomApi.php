<?php

namespace App\Request;

use App\Models\OptionsGeneral;
use Illuminate\Support\Facades\Http;

class AzuriomApi
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $options = OptionsGeneral::first();
        if (!$options) {
            throw new \RuntimeException('Les options générales ne sont pas configurées. Veuillez configurer l\'URL Azuriom et la clé API dans les paramètres généraux.');
        }

        if (!$options->azuriom_url || !$options->azuriom_api_key) {
            throw new \RuntimeException('L\'URL Azuriom et la clé API doivent être configurées dans les paramètres généraux.');
        }

        $this->baseUrl = rtrim($options->azuriom_url, '/');
        $this->apiKey = $options->azuriom_api_key;
    }

    private function makeRequest(string $endpoint)
    {
        return Http::timeout(10)
            ->withOptions(['verify' => config('services.http_verify_ssl', true)])
            ->withHeaders(['API-Key' => $this->apiKey])
            ->get($this->baseUrl . $endpoint);
    }

    public function getServers()
    {
        return $this->makeRequest('/api/apiextender/servers');
    }

    public function getRoles(): array
    {
        $response = $this->makeRequest('/api/apiextender/roles');

        return $response->successful() ? $response->json() : [];
    }

    public function getUsers(): array
    {
        $response = $this->makeRequest('/api/apiextender/users');

        return $response->successful() ? $response->json() : [];
    }

    public function getMoney()
    {
        return $this->makeRequest('/api/apiextender/money');
    }

    public function getSocial()
    {
        return $this->makeRequest('/api/apiextender/social');
    }
}
