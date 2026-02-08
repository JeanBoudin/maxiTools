<?php

namespace App\Http\Controllers;

use App\Models\TwitchAccount;
use App\Models\User;
use App\Services\TwitchApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TwitchAuthController extends Controller
{
    public function redirect(Request $request)
    {
        $state = Str::random(40);
        $request->session()->put('twitch_oauth_state', $state);

        if ($request->filled('return_to')) {
            $request->session()->put('twitch_oauth_return_to', $request->string('return_to')->toString());
        }

        $scopes = config('services.twitch.scopes', []);
        $query = http_build_query([
            'client_id' => config('services.twitch.client_id'),
            'redirect_uri' => config('services.twitch.redirect_uri'),
            'response_type' => 'code',
            'scope' => implode(' ', $scopes),
            'state' => $state,
            'force_verify' => 'true',
        ]);

        return redirect('https://id.twitch.tv/oauth2/authorize?'.$query);
    }

    public function callback(Request $request, TwitchApi $twitchApi)
    {
        $state = $request->query('state');
        $expectedState = $request->session()->pull('twitch_oauth_state');

        if (!$state || !$expectedState || !hash_equals($expectedState, $state)) {
            abort(400, 'Invalid OAuth state.');
        }

        $code = $request->query('code');
        if (!$code) {
            abort(400, 'Missing authorization code.');
        }

        $tokenData = $twitchApi->exchangeCode($code, config('services.twitch.redirect_uri'));
        $accessToken = $tokenData['access_token'] ?? null;

        if (!$accessToken) {
            abort(400, 'Missing access token.');
        }

        $user = $twitchApi->getUser($accessToken);
        if (empty($user)) {
            abort(400, 'Unable to fetch Twitch user.');
        }

        $account = TwitchAccount::firstOrNew([
            'twitch_user_id' => $user['id'],
        ]);

        $scopes = $tokenData['scope'] ?? [];
        if (is_string($scopes)) {
            $scopes = preg_split('/\s+/', trim($scopes));
        }

        $account->fill([
            'login' => $user['login'] ?? '',
            'display_name' => $user['display_name'] ?? '',
            'profile_image_url' => $user['profile_image_url'] ?? null,
            'access_token' => $accessToken,
            'refresh_token' => $tokenData['refresh_token'] ?? $account->refresh_token,
            'token_expires_at' => isset($tokenData['expires_in'])
                ? now()->addSeconds((int) $tokenData['expires_in'])
                : $account->token_expires_at,
            'scopes' => $scopes,
            'overlay_key' => $account->overlay_key ?: (string) Str::uuid(),
        ]);

        $userModel = User::firstOrNew([
            'twitch_user_id' => $user['id'],
        ]);

        if (!$userModel->exists) {
            $userModel->fill([
                'name' => $user['display_name'] ?? $user['login'] ?? 'Twitch User',
                'email' => 'twitch_'.$user['id'].'@rouestream.local',
                'password' => Str::random(40),
                'twitch_user_id' => $user['id'],
                'twitch_login' => $user['login'] ?? null,
                'twitch_display_name' => $user['display_name'] ?? null,
                'twitch_avatar' => $user['profile_image_url'] ?? null,
            ]);
        } else {
            $userModel->fill([
                'name' => $user['display_name'] ?? $userModel->name,
                'twitch_login' => $user['login'] ?? $userModel->twitch_login,
                'twitch_display_name' => $user['display_name'] ?? $userModel->twitch_display_name,
                'twitch_avatar' => $user['profile_image_url'] ?? $userModel->twitch_avatar,
            ]);
        }

        $userModel->save();

        $account->user_id = $userModel->id;
        $account->save();

        Auth::login($userModel);

        $frontBase = rtrim(config('services.twitch.frontend_url', 'http://localhost:5173'), '/');
        $frontPath = config('services.twitch.frontend_redirect_path', '/auth/callback');
        $defaultTarget = $frontBase.$frontPath;
        $target = $request->session()->pull('twitch_oauth_return_to', $defaultTarget);

        return redirect($target);
    }
}
