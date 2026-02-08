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
        ]);

        $account = $this->accountForUser();

        $setting = CameraFrameSetting::updateOrCreate(
            ['twitch_account_id' => $account->id],
            [
                'show_sub_goal' => (bool) ($data['show_sub_goal'] ?? false),
                'sub_goal_title' => $data['sub_goal_title'] ?? 'Objectif d’abonnement',
                'sub_goal_current' => (int) ($data['sub_goal_current'] ?? 0),
                'sub_goal_target' => (int) ($data['sub_goal_target'] ?? 10),
                'sub_goal_subtitle' => $data['sub_goal_subtitle'] ?? 'Nouveaux abonnements',
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
            ];
        }

        return [
            'show_sub_goal' => (bool) $setting->show_sub_goal,
            'sub_goal_title' => $setting->sub_goal_title,
            'sub_goal_current' => $setting->sub_goal_current,
            'sub_goal_target' => $setting->sub_goal_target,
            'sub_goal_subtitle' => $setting->sub_goal_subtitle,
        ];
    }
}
