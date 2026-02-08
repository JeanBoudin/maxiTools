<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TwitchApi
{
    private string $clientId;
    private string $clientSecret;

    public function __construct(?string $clientId = null, ?string $clientSecret = null)
    {
        $this->clientId = $clientId ?? config('services.twitch.client_id', '');
        $this->clientSecret = $clientSecret ?? config('services.twitch.client_secret', '');
    }

    public function exchangeCode(string $code, string $redirectUri): array
    {
        return $this->oauthRequest([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirectUri,
        ]);
    }

    public function refreshToken(string $refreshToken): array
    {
        return $this->oauthRequest([
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ]);
    }

    public function getUser(string $accessToken): array
    {
        $data = $this->apiRequest('GET', '/users', $accessToken);

        return $data['data'][0] ?? [];
    }

    public function getStream(string $accessToken, string $userId): array
    {
        return $this->apiRequest('GET', '/streams', $accessToken, [
            'user_id' => $userId,
        ]);
    }

    public function getFollowerCount(string $accessToken, string $broadcasterId): int
    {
        $data = $this->apiRequest('GET', '/channels/followers', $accessToken, [
            'broadcaster_id' => $broadcasterId,
        ]);

        return (int) ($data['total'] ?? 0);
    }

    public function getSubscriberCount(string $accessToken, string $broadcasterId): ?int
    {
        try {
            $data = $this->apiRequest('GET', '/subscriptions', $accessToken, [
                'broadcaster_id' => $broadcasterId,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Twitch subscription fetch failed', [
                'broadcaster_id' => $broadcasterId,
                'message' => $e->getMessage(),
            ]);

            return null;
        }

        return isset($data['total']) ? (int) $data['total'] : null;
    }

    private function oauthRequest(array $payload): array
    {
        $response = $this->http()
            ->asForm()
            ->post('https://id.twitch.tv/oauth2/token', array_merge($payload, [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ]));

        return $response->throw()->json();
    }

    private function apiRequest(string $method, string $path, string $accessToken, array $query = []): array
    {
        $response = $this->http()
            ->withHeaders([
                'Client-Id' => $this->clientId,
                'Authorization' => 'Bearer '.$accessToken,
            ])
            ->send($method, 'https://api.twitch.tv/helix'.$path, [
                'query' => $query,
            ]);

        return $response->throw()->json();
    }

    private function http(): PendingRequest
    {
        return Http::timeout(10)->retry(2, 250);
    }
}
