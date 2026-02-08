<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromoSetting extends Model
{
    protected $fillable = [
        'twitch_account_id',
        'lines',
        'interval_minutes',
        'display_duration_seconds',
    ];

    protected $casts = [
        'lines' => 'array',
    ];

    public function twitchAccount(): BelongsTo
    {
        return $this->belongsTo(TwitchAccount::class);
    }
}
