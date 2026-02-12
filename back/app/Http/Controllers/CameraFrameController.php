<?php

namespace App\Http\Controllers;

use App\Models\CameraFrameSetting;
use App\Models\TwitchAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CameraFrameController extends Controller
{
    public function show(Request $request)
    {
        $account = $this->accountForUser();
        $setting = $account->cameraFrameSetting;

        return response()->json($this->formatSettings($setting));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'show_sub_goal' => ['boolean'],
            'sub_goal_title' => ['nullable', 'string', 'max:100'],
            'sub_goal_current' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'sub_goal_target' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'sub_goal_subtitle' => ['nullable', 'string', 'max:100'],
            'rotation_enabled' => ['boolean'],
            'rotation_interval_seconds' => ['nullable', 'integer', 'min:4', 'max:120'],
            'rotation_items' => ['nullable', 'array'],
            'now_playing_title' => ['nullable', 'string', 'max:100'],
            'now_playing_empty' => ['nullable', 'string', 'max:120'],
        ]);

        $account = $this->accountForUser();

        $rotationItems = $this->normalizeRotationItems($data['rotation_items'] ?? null);

        $setting = CameraFrameSetting::updateOrCreate(
            ['twitch_account_id' => $account->id],
            [
                'show_sub_goal' => (bool) ($data['show_sub_goal'] ?? false),
                'sub_goal_title' => $data['sub_goal_title'] ?? 'Objectif d’abonnement',
                'sub_goal_current' => (int) ($data['sub_goal_current'] ?? 0),
                'sub_goal_target' => (int) ($data['sub_goal_target'] ?? 10),
                'sub_goal_subtitle' => $data['sub_goal_subtitle'] ?? 'Nouveaux abonnements',
                'rotation_enabled' => (bool) ($data['rotation_enabled'] ?? false),
                'rotation_interval_seconds' => (int) ($data['rotation_interval_seconds'] ?? 12),
                'rotation_items' => $rotationItems,
                'now_playing_title' => $data['now_playing_title'] ?? 'Musique en cours',
                'now_playing_empty' => $data['now_playing_empty'] ?? 'Rien en lecture',
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
        $setting = $account->cameraFrameSetting;

        return response()->json($this->formatSettings($setting));
    }

    private function accountForUser(): TwitchAccount
    {
        $user = Auth::user();

        if (!$user || !$user->twitchAccount) {
            abort(401, 'Unauthenticated.');
        }

        return $user->twitchAccount;
    }

    private function formatSettings(?CameraFrameSetting $setting): array
    {
        if (!$setting) {
            return [
                'show_sub_goal' => false,
                'sub_goal_title' => 'Objectif d’abonnement',
                'sub_goal_current' => 0,
                'sub_goal_target' => 10,
                'sub_goal_subtitle' => 'Nouveaux abonnements',
                'rotation_enabled' => false,
                'rotation_interval_seconds' => 12,
                'rotation_items' => ['sub_goal', 'now_playing'],
                'now_playing_title' => 'Musique en cours',
                'now_playing_empty' => 'Rien en lecture',
            ];
        }

        return [
            'show_sub_goal' => (bool) $setting->show_sub_goal,
            'sub_goal_title' => $setting->sub_goal_title,
            'sub_goal_current' => $setting->sub_goal_current,
            'sub_goal_target' => $setting->sub_goal_target,
            'sub_goal_subtitle' => $setting->sub_goal_subtitle,
            'rotation_enabled' => (bool) $setting->rotation_enabled,
            'rotation_interval_seconds' => $setting->rotation_interval_seconds ?? 12,
            'rotation_items' => $setting->rotation_items ?? ['sub_goal', 'now_playing'],
            'now_playing_title' => $setting->now_playing_title ?? 'Musique en cours',
            'now_playing_empty' => $setting->now_playing_empty ?? 'Rien en lecture',
        ];
    }

    private function normalizeRotationItems($items): array
    {
        if (!is_array($items)) {
            return ['sub_goal', 'now_playing'];
        }

        $allowed = ['sub_goal', 'now_playing'];
        $clean = array_values(array_filter(array_map('strval', $items), fn ($item) => in_array($item, $allowed, true)));

        return count($clean) > 0 ? $clean : ['sub_goal', 'now_playing'];
    }
}
