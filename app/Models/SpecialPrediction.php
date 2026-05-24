<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpecialPrediction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'champion_team_id',
        'runner_up_team_id',
        'top_scorer_player_id',
        'is_locked',
        'pts_champion',
        'pts_runner_up',
        'pts_top_scorer',
        'calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'is_locked' => 'boolean',
            'calculated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function champion(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'champion_team_id');
    }

    public function runnerUp(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'runner_up_team_id');
    }

    public function topScorer(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'top_scorer_player_id');
    }
}
