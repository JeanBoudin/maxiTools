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
    ];

    protected $casts = [
        'show_sub_goal' => 'boolean',
    ];

    public function twitchAccount(): BelongsTo
    {
        return $this->belongsTo(TwitchAccount::class);
    }
}
