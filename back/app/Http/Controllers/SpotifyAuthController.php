<?php

namespace App\Http\Controllers;

use App\Models\SpotifyAccount;
use App\Services\SpotifyApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SpotifyAuthController extends Controller
{
    public function redirect(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->twitchAccount) {
            abort(401, 'Unauthenticated.');
        }

        $state = Str::random(40);
        $request->session()->put('spotify_oauth_state', $state);

        if ($request->filled('return_to')) {
            $request->session()->put('spotify_oauth_return_to', $request->string('return_to')->toString());
        }

        $scopes = config('services.spotify.scopes', []);
        $query = http_build_query([
            'client_id' => config('services.spotify.client_id'),
            'redirect_uri' => config('services.spotify.redirect_uri'),
            'response_type' => 'code',
            'scope' => implode(' ', $scopes),
            'state' => $state,
            'show_dialog' => 'true',
        ]);

        return redirect('https://accounts.spotify.com/authorize?'.$query);
    }

    public function callback(Request $request, SpotifyApi $spotifyApi)
    {
        $state = $request->query('state');
        $expectedState = $request->session()->pull('spotify_oauth_state');

        if (!$state || !$expectedState || !hash_equals($expectedState, $state)) {
            abort(400, 'Invalid OAuth state.');
        }

        $code = $request->query('code');
        if (!$code) {
            abort(400, 'Missing authorization code.');
        }

        $user = Auth::user();
        if (!$user || !$user->twitchAccount) {
            abort(401, 'Unauthenticated.');
        }

        $tokenData = $spotifyApi->exchangeCode($code);
        $accessToken = $tokenData['access_token'] ?? null;

        if (!$accessToken) {
            abort(400, 'Missing access token.');
        }

        $profile = $spotifyApi->getProfile($accessToken);

        $account = SpotifyAccount::firstOrNew([
            'twitch_account_id' => $user->twitchAccount->id,
        ]);

        $scopes = $tokenData['scope'] ?? '';
        if (is_string($scopes)) {
            $scopes = preg_split('/\s+/', trim($scopes));
        }

        $account->fill([
            'spotify_user_id' => $profile['id'] ?? null,
            'display_name' => $profile['display_name'] ?? null,
            'access_token' => $accessToken,
            'refresh_token' => $tokenData['refresh_token'] ?? $account->refresh_token,
            'token_expires_at' => isset($tokenData['expires_in'])
                ? now()->addSeconds((int) $tokenData['expires_in'])
                : $account->token_expires_at,
            'scopes' => $scopes,
        ]);

        $account->save();

        $frontBase = rtrim(config('services.spotify.frontend_url', 'http://localhost:5173'), '/');
        $defaultTarget = $frontBase.'/overlays/camera';
        $target = $request->session()->pull('spotify_oauth_return_to', $defaultTarget);

        return redirect($target);
    }
}
