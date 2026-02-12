<?php

namespace App\Services;

use App\Models\SpotifyAccount;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SpotifyApi
{
    public function exchangeCode(string $code): array
    {
        $response = Http::asForm()->post('https://accounts.spotify.com/api/token', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => config('services.spotify.redirect_uri'),
            'client_id' => config('services.spotify.client_id'),
            'client_secret' => config('services.spotify.client_secret'),
        ]);

        if (!$response->successful()) {
            throw new RuntimeException('Impossible de récupérer le token Spotify.');
        }

        return $response->json();
    }

    public function refreshToken(SpotifyAccount $account): array
    {
        if (!$account->refresh_token) {
            throw new RuntimeException('Refresh token Spotify manquant.');
        }

        $response = Http::asForm()->post('https://accounts.spotify.com/api/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $account->refresh_token,
            'client_id' => config('services.spotify.client_id'),
            'client_secret' => config('services.spotify.client_secret'),
        ]);

        if (!$response->successful()) {
            throw new RuntimeException('Impossible de rafraîchir le token Spotify.');
        }

        return $response->json();
    }

    public function getProfile(string $accessToken): array
    {
        $response = Http::withToken($accessToken)->get('https://api.spotify.com/v1/me');

        if (!$response->successful()) {
            throw new RuntimeException('Impossible de récupérer le profil Spotify.');
        }

        return $response->json();
    }

    public function getCurrentlyPlaying(string $accessToken): array
    {
        $response = Http::withToken($accessToken)->get('https://api.spotify.com/v1/me/player/currently-playing');

        if ($response->status() === 204) {
            return [];
        }

        if (!$response->successful()) {
            throw new RuntimeException('Impossible de récupérer la lecture Spotify.');
        }

        return $response->json();
    }
}
