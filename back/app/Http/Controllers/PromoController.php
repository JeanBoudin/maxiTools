<?php

namespace App\Http\Controllers;

use App\Models\PromoSetting;
use App\Models\TwitchAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PromoController extends Controller
{
    public function show(Request $request)
    {
        $account = $this->accountForUser();
        $setting = $account->promoSetting;

        return response()->json($this->formatSettings($setting));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'lines' => ['required'],
            'interval_minutes' => ['nullable', 'integer', 'min:1', 'max:120'],
            'display_duration_seconds' => ['nullable', 'integer', 'min:2', 'max:60'],
        ]);

        $account = $this->accountForUser();
        $lines = $this->normalizeLines($data['lines']);
        $interval = isset($data['interval_minutes']) ? (int) $data['interval_minutes'] : 10;
        $duration = isset($data['display_duration_seconds']) ? (int) $data['display_duration_seconds'] : 8;

        $setting = PromoSetting::updateOrCreate(
            ['twitch_account_id' => $account->id],
            [
                'lines' => $lines,
                'interval_minutes' => $interval,
                'display_duration_seconds' => $duration,
            ]
        );

        return response()->json($this->formatSettings($setting));
    }

    public function overlay(Request $request)
    {
        $data = $request->validate([
            'key' => ['required', 'string'],
        ]);

        $account = TwitchAccount::where('overlay_key', $data['key'])->firstOrFail();
        $setting = $account->promoSetting;

        return response()->json($this->formatSettings($setting));
    }

    private function normalizeLines($lines): array
    {
        if (is_string($lines)) {
            $lines = preg_split('/\r?\n/', $lines);
        }

        if (!is_array($lines)) {
            return $this->defaultLines();
        }

        $clean = array_values(array_filter(array_map('trim', $lines)));

        return count($clean) > 0 ? $clean : $this->defaultLines();
    }

    private function defaultLines(): array
    {
        return [
            'Suivez-moi sur Instagram @tonpseudo',
            'Retrouvez-moi sur YouTube /tonpseudo',
        ];
    }

    private function accountForUser(): TwitchAccount
    {
        $user = Auth::user();

        if (!$user || !$user->twitchAccount) {
            abort(401, 'Unauthenticated.');
        }

        return $user->twitchAccount;
    }

    private function formatSettings(?PromoSetting $setting): array
    {
        if (!$setting) {
            return [
                'lines' => $this->defaultLines(),
                'interval_minutes' => 10,
                'display_duration_seconds' => 8,
            ];
        }

        return [
            'lines' => $setting->lines ?? $this->defaultLines(),
            'interval_minutes' => $setting->interval_minutes ?? 10,
            'display_duration_seconds' => $setting->display_duration_seconds ?? 8,
        ];
    }
}
