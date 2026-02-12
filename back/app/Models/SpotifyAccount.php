<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpotifyAccount extends Model
{
    protected $fillable = [
        'twitch_account_id',
        'spotify_user_id',
        'display_name',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'scopes',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'scopes' => 'array',
    ];

    public function twitchAccount(): BelongsTo
    {
        return $this->belongsTo(TwitchAccount::class);
    }
}
