<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ObsSetting extends Model
{
    protected $fillable = [
        'twitch_account_id',
        'host',
        'port',
        'password',
        'use_tls',
    ];

    protected $casts = [
        'use_tls' => 'boolean',
        'port' => 'integer',
    ];

    public function twitchAccount(): BelongsTo
    {
        return $this->belongsTo(TwitchAccount::class);
    }
}
