<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TwitchAccount extends Model
{
    protected $fillable = [
        'user_id',
        'twitch_user_id',
        'login',
        'display_name',
        'profile_image_url',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'scopes',
        'overlay_key',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'scopes' => 'array',
    ];

    public function promoSetting(): HasOne
    {
        return $this->hasOne(PromoSetting::class);
    }

    public function cameraFrameSetting(): HasOne
    {
        return $this->hasOne(CameraFrameSetting::class);
    }

    public function obsSetting(): HasOne
    {
        return $this->hasOne(ObsSetting::class);
    }

    public function spotifyAccount(): HasOne
    {
        return $this->hasOne(SpotifyAccount::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
