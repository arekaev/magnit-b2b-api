<?php

namespace Magnit;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class MagnitClient
{
    private $accessToken;
    private $tokenExpiresAt;
    private $httpClient;
    private $clientId;
    private $clientSecret;
    private $tokenStoragePath = __DIR__ . '/data/token.json';

    public function __construct(string $baseUrl, string $clientId, string $clientSecret)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->loadTokenFromStorage();
        $this->httpClient = new Client(['base_uri' => $baseUrl]);
    }

    /**
     * Получить токен аутентификации (с проверкой срока действия).
     */
    private function getAccessToken(): string
    {
        if (!$this->accessToken || $this->tokenExpiresAt <= time()) {
            $this->refreshAccessToken();
        }

        return $this->accessToken;
    }

    private function loadTokenFromStorage(): void
    {
        if (file_exists($this->tokenStoragePath)) {
            $data = json_decode(file_get_contents($this->tokenStoragePath), true);
            $this->accessToken = $data['access_token'] ?? null;
            $this->tokenExpiresAt = $data['expires_at'] ?? 0;
        }
    }

    private function refreshAccessToken(): void
    {
        $response = $this->httpClient->request('POST', '/v2/oauth/token', [
            'json' => [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);
        $this->accessToken = $data['access_token'];
        $this->tokenExpiresAt = time() + ($data['expires_in'] ?? 59 * 60);

        // Сохранение токена в файл
        $this->saveTokenToStorage();
    }

    private function saveTokenToStorage(): void
    {
        file_put_contents($this->tokenStoragePath, json_encode([
            'access_token' => $this->accessToken,
            'expires_at' => $this->tokenExpiresAt,
        ]));
    }


    /**
     * Отправка запросов с Bearer токеном.
     */
    public function request(string $method, string $endpoint, array $data = []): array
    {
        try {
            $response = $this->httpClient->request($method, $endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->getAccessToken(),
                ],
                'json' => json_encode($data),
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            $response = $e->getResponse();
            $statusCode = $response ? $response->getStatusCode() : 0;
            $errorMessage = $response ? $response->getBody()->getContents() : $e->getMessage();

            throw new ApiException($errorMessage, $statusCode);
        }
    }


}
