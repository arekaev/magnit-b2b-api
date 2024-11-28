<?php

namespace Magnit;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
//use GuzzleHttp\HandlerStack;
//use GuzzleHttp\Middleware;
use Magnit\Exceptions\ApiException;

class MagnitClient
{
    private $accessToken = '';
    private $tokenExpiresIn = 0;
    private $httpClient;
    private $clientId = '';
    private $clientSecret = '';
    private $tokenStoragePath  = __DIR__.'/../data/token.json';

    public $container = [];

    public function __construct(string $clientId, string $clientSecret, string $baseUrl = 'https://b2b-api.magnit.ru/api/')
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
//
//        $history = Middleware::history($this->container);
//        $handlerStack = HandlerStack::create();
//        $handlerStack->push($history);


        $this->httpClient = new Client([
            'base_uri' => $baseUrl,
//            'handler' => $handlerStack
        ]);
    }

    /**
     * Получить токен аутентификации (с проверкой срока действия).
     */
    private function getAccessToken(): string
    {
        $this->loadTokenFromStorage();

        if (!$this->accessToken || $this->tokenExpiresIn <= time()) {
            $this->refreshAccessToken();
        }

        return $this->accessToken;
    }

    private function loadTokenFromStorage(): void
    {
        if (file_exists($this->tokenStoragePath)) {
            $data = json_decode(file_get_contents($this->tokenStoragePath), true);

            $this->accessToken = $data['access_token'] ?? '';
            $this->tokenExpiresIn = $data['expires_in'] ?? 0;
        }
    }

    public function refreshAccessToken(): void
    {
        try {
            $response = $this->httpClient->request('POST', 'api/v2/oauth/token', [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'application/json',
                ],
                'form_params' => [
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'scope' => 'openid magnit-post:orders magnit-post:pickup-points',
                    'grant_type' => 'client_credentials',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            $this->accessToken = $data['access_token'];
            $this->tokenExpiresIn = time() + ($data['expires_in'] ?? 59 * 60);

            $this->saveTokenToStorage();
        } catch (RequestException $e) {
            $this->callException($e);
        }
    }

    private function saveTokenToStorage(): void
    {
        file_put_contents($this->tokenStoragePath, json_encode([
            'access_token' => $this->accessToken,
            'expires_in' => $this->tokenExpiresIn,
        ]));
    }

    /**
     * @param string $method
     * @param string $endpoint
     * @param array $form_params
     * @return array
     */
    public function request(string $method, string $endpoint, array $form_params = []): array
    {
        try {
            $response = $this->httpClient->request($method, $endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->getAccessToken(),
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'application/json',
                ],
                'form_params' => $form_params
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            $this->callException($e);
        }

//        foreach ($this->container as $transaction) {
//            dump($transaction);
//        }
    }

    /**
     * @param RequestException $e
     * @return void
     * @throws ApiException
     */
    private function callException(RequestException $e): void
    {
        $response = $e->getResponse();
        $statusCode = $response ? $response->getStatusCode() : 0;
        $errorMessage = $response ? $response->getBody()->getContents() : $e->getMessage();

        throw new ApiException($errorMessage, $statusCode);
    }

}
