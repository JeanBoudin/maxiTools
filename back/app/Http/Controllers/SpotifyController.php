<?php

namespace App\Http\Controllers;

use App\Models\SpotifyAccount;
use App\Models\TwitchAccount;
use App\Services\SpotifyApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SpotifyController extends Controller
{
    public function status(Request $request, SpotifyApi $spotifyApi)
    {
        $account = $this->accountForUser();
        $spotify = $account->spotifyAccount;

        if (!$spotify) {
            return response()->json(['connected' => false]);
        }

        $payload = [
            'connected' => true,
            'display_name' => $spotify->display_name,
        ];

        $payload['now_playing'] = $this->fetchNowPlaying($spotify, $spotifyApi);

        return response()->json($payload);
    }

    public function overlayNowPlaying(Request $request, SpotifyApi $spotifyApi)
    {
        $data = $request->validate([
            'key' => ['required', 'string'],
        ]);

        $account = TwitchAccount::where('overlay_key', $data['key'])->firstOrFail();
        $spotify = $account->spotifyAccount;

        if (!$spotify) {
            return response()->json([
                'connected' => false,
                'is_playing' => false,
            ]);
        }

        $nowPlaying = $this->fetchNowPlaying($spotify, $spotifyApi);

        return response()->json([
            'connected' => true,
            ...$nowPlaying,
        ]);
    }

    private function fetchNowPlaying(SpotifyAccount $spotify, SpotifyApi $spotifyApi): array
    {
        $accessToken = $this->getValidAccessToken($spotify, $spotifyApi);

        try {
            $payload = $spotifyApi->getCurrentlyPlaying($accessToken);
        } catch (\Throwable $e) {
            return [
                'is_playing' => false,
                'track' => null,
                'artist' => null,
            ];
        }

        if (empty($payload)) {
            return [
                'is_playing' => false,
                'track' => null,
                'artist' => null,
            ];
        }

        $item = $payload['item'] ?? null;
        $artists = $item['artists'] ?? [];
        $artistNames = array_filter(array_map(fn ($artist) => $artist['name'] ?? '', $artists));

        return [
            'is_playing' => (bool) ($payload['is_playing'] ?? false),
            'track' => $item['name'] ?? null,
            'artist' => count($artistNames) > 0 ? implode(', ', $artistNames) : null,
        ];
    }

    private function getValidAccessToken(SpotifyAccount $spotify, SpotifyApi $spotifyApi): string
    {
        if ($spotify->access_token && $spotify->token_expires_at && now()->lt($spotify->token_expires_at->copy()->subSeconds(30))) {
            return $spotify->access_token;
        }

        $tokenData = $spotifyApi->refreshToken($spotify);
        $spotify->access_token = $tokenData['access_token'] ?? $spotify->access_token;
        if (isset($tokenData['refresh_token'])) {
            $spotify->refresh_token = $tokenData['refresh_token'];
        }
        if (isset($tokenData['expires_in'])) {
            $spotify->token_expires_at = now()->addSeconds((int) $tokenData['expires_in']);
        }
        $spotify->save();

        return $spotify->access_token ?? '';
    }

    private function accountForUser(): TwitchAccount
    {
        $user = Auth::user();

        if (!$user || !$user->twitchAccount) {
            abort(401, 'Unauthenticated.');
        }

        return $user->twitchAccount;
    }
}
