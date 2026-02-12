<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CameraFrameSetting extends Model
{
    protected $fillable = [
        'twitch_account_id',
        'show_sub_goal',
        'sub_goal_title',
        'sub_goal_current',
        'sub_goal_target',
        'sub_goal_subtitle',
        'rotation_enabled',
        'rotation_interval_seconds',
        'rotation_items',
        'now_playing_title',
        'now_playing_empty',
    ];

    protected $casts = [
        'show_sub_goal' => 'boolean',
        'rotation_enabled' => 'boolean',
        'rotation_items' => 'array',
    ];

    public function twitchAccount(): BelongsTo
    {
        return $this->belongsTo(TwitchAccount::class);
    }
}
