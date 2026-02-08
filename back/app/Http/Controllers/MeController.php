<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MeController extends Controller
{
    public function show(Request $request)
    {
        $user = Auth::user();

        if (!$user || !$user->twitchAccount) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $account = $user->twitchAccount;

        return response()->json([
            'display_name' => $account->display_name,
            'login' => $account->login,
            'overlay_key' => $account->overlay_key,
            'profile_image_url' => $account->profile_image_url,
        ]);
    }
}
