<?php

namespace App\Http\Controllers;

use App\Models\TwitchAccount;
use App\Services\TwitchApi;
use Illuminate\Http\Request;

class OverlayController extends Controller
{
    public function stats(Request $request, TwitchApi $twitchApi)
    {
        $data = $request->validate([
            'key' => ['required', 'string'],
        ]);

        $account = TwitchAccount::where('overlay_key', $data['key'])->firstOrFail();
        $accessToken = $this->ensureValidToken($account, $twitchApi);

        $streamData = $twitchApi->getStream($accessToken, $account->twitch_user_id);
        $viewerCount = 0;
        if (!empty($streamData['data'])) {
            $viewerCount = (int) ($streamData['data'][0]['viewer_count'] ?? 0);
        }

        $followerCount = $twitchApi->getFollowerCount($accessToken, $account->twitch_user_id);
        $subscriberCount = $twitchApi->getSubscriberCount($accessToken, $account->twitch_user_id);

        return response()->json([
            'twitch_user_id' => $account->twitch_user_id,
            'login' => $account->login,
            'display_name' => $account->display_name,
            'viewer_count' => $viewerCount,
            'follower_count' => $followerCount,
            'subscriber_count' => $subscriberCount,
            'subscribers_available' => $subscriberCount !== null,
            'updated_at' => now()->toIso8601String(),
        ]);
    }

    private function ensureValidToken(TwitchAccount $account, TwitchApi $twitchApi): string
    {
        $expiresSoon = $account->token_expires_at
            ? $account->token_expires_at->isBefore(now()->addSeconds(60))
            : true;

        if (!$expiresSoon) {
            return $account->access_token;
        }

        if (!$account->refresh_token) {
            abort(401, 'Refresh token missing.');
        }

        $tokenData = $twitchApi->refreshToken($account->refresh_token);
        $accessToken = $tokenData['access_token'] ?? null;

        if (!$accessToken) {
            abort(401, 'Unable to refresh token.');
        }

        $scopes = $tokenData['scope'] ?? $account->scopes;
        if (is_string($scopes)) {
            $scopes = preg_split('/\s+/', trim($scopes));
        }

        $account->fill([
            'access_token' => $accessToken,
            'refresh_token' => $tokenData['refresh_token'] ?? $account->refresh_token,
            'token_expires_at' => isset($tokenData['expires_in'])
                ? now()->addSeconds((int) $tokenData['expires_in'])
                : $account->token_expires_at,
            'scopes' => $scopes,
        ]);

        $account->save();

        return $account->access_token;
    }
}
