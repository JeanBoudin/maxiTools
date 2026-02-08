<?php

namespace App\Http\Controllers;

use App\Models\ObsSetting;
use App\Models\TwitchAccount;
use App\Services\ObsWebsocketClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ObsController extends Controller
{
    public function show(Request $request)
    {
        $account = $this->accountForUser();
        $setting = $account->obsSetting;

        return response()->json($this->formatSettings($setting));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'password' => ['nullable', 'string', 'max:255'],
            'use_tls' => ['boolean'],
        ]);

        $account = $this->accountForUser();

        $setting = ObsSetting::updateOrCreate(
            ['twitch_account_id' => $account->id],
            [
                'host' => $data['host'],
                'port' => (int) $data['port'],
                'password' => $data['password'] ?: null,
                'use_tls' => (bool) ($data['use_tls'] ?? false),
            ]
        );

        return response()->json($this->formatSettings($setting));
    }

    public function scenes(Request $request)
    {
        $account = $this->accountForUser();
        $setting = $account->obsSetting;

        if (!$setting) {
            return response()->json(['message' => 'OBS non configuré.'], 422);
        }

        try {
            $client = $this->clientForSetting($setting);
            $client->connect();
            $scenes = $client->getSceneList();
            $current = $client->getCurrentProgramScene();
            $client->close();

            $names = array_values(array_map(fn ($scene) => $scene['sceneName'] ?? '', $scenes));
            $names = array_values(array_filter($names));

            return response()->json([
                'scenes' => $names,
                'current_scene' => $current,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function switch(Request $request)
    {
        $data = $request->validate([
            'scene' => ['required', 'string', 'max:200'],
        ]);

        $account = $this->accountForUser();
        $setting = $account->obsSetting;

        if (!$setting) {
            return response()->json(['message' => 'OBS non configuré.'], 422);
        }

        try {
            $client = $this->clientForSetting($setting);
            $client->connect();
            $client->setCurrentScene($data['scene']);
            $client->close();

            return response()->json(['status' => 'ok']);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function publicSwitch(Request $request)
    {
        $data = $request->validate([
            'key' => ['required', 'string'],
            'scene' => ['required', 'string', 'max:200'],
        ]);

        $account = TwitchAccount::where('overlay_key', $data['key'])->firstOrFail();
        $setting = $account->obsSetting;

        if (!$setting) {
            return response()->json(['message' => 'OBS non configuré.'], 422);
        }

        try {
            $client = $this->clientForSetting($setting);
            $client->connect();
            $client->setCurrentScene($data['scene']);
            $client->close();

            return response()->json(['status' => 'ok']);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    private function clientForSetting(ObsSetting $setting): ObsWebsocketClient
    {
        return new ObsWebsocketClient(
            $setting->host,
            (int) $setting->port,
            $setting->password,
            (bool) $setting->use_tls
        );
    }

    private function accountForUser(): TwitchAccount
    {
        $user = Auth::user();

        if (!$user || !$user->twitchAccount) {
            abort(401, 'Unauthenticated.');
        }

        return $user->twitchAccount;
    }

    private function formatSettings(?ObsSetting $setting): array
    {
        if (!$setting) {
            return [
                'host' => 'host.docker.internal',
                'port' => 4455,
                'use_tls' => false,
                'has_password' => false,
            ];
        }

        return [
            'host' => $setting->host,
            'port' => $setting->port,
            'use_tls' => (bool) $setting->use_tls,
            'has_password' => $setting->password !== null && $setting->password !== '',
        ];
    }
}
